package com.example.apotek_astria

import android.content.Context
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import com.google.firebase.Timestamp
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.SetOptions
import com.google.firebase.messaging.FirebaseMessaging
import org.json.JSONObject

data class PengaturanNotifikasi(
    val notifAktif: Boolean = true,
    val batasStok: Int = 10,
    val jam: String = "08:00"
)

object PengaturanNotifikasiRepo {
    const val KOLEKSI = "pengaturan_notifikasi"

    private fun userId(ctx: Context) =
        ApiConfig.sesiPref(ctx).getInt(ApiConfig.KEY_USER_ID, -1)

    fun muat(ctx: Context, hasil: (PengaturanNotifikasi) -> Unit) {
        val lokal = bacaLokal(ctx)
        if (ApiConfig.ambilToken(ctx).isNullOrBlank()) {
            muatFirestore(ctx, lokal, hasil)
            return
        }

        val req = ApiRequest(
            ctx,
            Request.Method.GET,
            ApiConfig.URL_NOTIFIKASI,
            null,
            { response ->
                val data = response.optJSONObject("data")
                if (response.optBoolean("success", false) && data != null) {
                    val jamServer = if (data.isNull("jam_ringkasan")) "" else data.optString("jam_ringkasan", "")
                    val p = PengaturanNotifikasi(
                        notifAktif = data.optBoolean("aktif", lokal.notifAktif),
                        batasStok = data.optInt("batas_stok", lokal.batasStok),
                        jam = jamServer.ifBlank { lokal.jam }
                    )
                    simpanLokal(ctx, p)
                    hasil(p)
                } else {
                    muatFirestore(ctx, lokal, hasil)
                }
            },
            { error ->
                if (!ApiConfig.tanganiAuthError(ctx, error)) {
                    muatFirestore(ctx, lokal, hasil)
                }
            }
        )
        Volley.newRequestQueue(ctx.applicationContext).add(req)
    }

    private fun muatFirestore(ctx: Context, lokal: PengaturanNotifikasi, hasil: (PengaturanNotifikasi) -> Unit) {
        val id = userId(ctx)
        if (id < 0) {
            hasil(lokal)
            return
        }

        FirebaseFirestore.getInstance().collection(KOLEKSI).document(id.toString())
            .get()
            .addOnSuccessListener { d ->
                if (!d.exists()) {
                    hasil(lokal)
                    return@addOnSuccessListener
                }
                val p = PengaturanNotifikasi(
                    notifAktif = d.getBoolean("notif_aktif") ?: lokal.notifAktif,
                    batasStok = d.getLong("batas_stok")?.toInt() ?: lokal.batasStok,
                    jam = d.getString("jam_notifikasi") ?: lokal.jam
                )
                simpanLokal(ctx, p)
                hasil(p)
            }
            .addOnFailureListener { hasil(lokal) }
    }

    fun simpan(ctx: Context, p: PengaturanNotifikasi, selesai: (Boolean) -> Unit) {
        simpanLokal(ctx, p)
        val appCtx = ctx.applicationContext
        val id = userId(appCtx)
        if (id < 0) {
            selesai(false)
            return
        }

        FirebaseMessaging.getInstance().token.addOnCompleteListener { t ->
            val fcmToken = if (t.isSuccessful) t.result else null

            // Mirror ke Firestore (best-effort)
            val dataFirestore = hashMapOf<String, Any>(
                "user_id" to id,
                "notif_aktif" to p.notifAktif,
                "batas_stok" to p.batasStok,
                "jam_notifikasi" to p.jam,
                "updated_at" to Timestamp.now()
            )
            if (!fcmToken.isNullOrBlank()) dataFirestore["fcm_token"] = fcmToken
            FirebaseFirestore.getInstance().collection(KOLEKSI).document(id.toString())
                .set(dataFirestore, SetOptions.merge())

            if (ApiConfig.ambilToken(appCtx).isNullOrBlank()) {
                selesai(false)
                return@addOnCompleteListener
            }

            val body = JSONObject()
                .put("aktif", p.notifAktif)
                .put("batas_stok", p.batasStok)
                .put("jam_ringkasan", p.jam)
            if (!fcmToken.isNullOrBlank()) {
                body.put("fcm_token", fcmToken)
            }

            val req = ApiRequest(
                appCtx,
                Request.Method.PUT,
                ApiConfig.URL_NOTIFIKASI,
                body,
                { res -> selesai(res.optBoolean("success", false)) },
                { error ->
                    ApiConfig.tanganiAuthError(appCtx, error)
                    selesai(false)
                }
            )
            Volley.newRequestQueue(appCtx).add(req)
        }
    }

    fun sinkronToken(ctx: Context) {
        val appCtx = ctx.applicationContext
        if (ApiConfig.ambilToken(appCtx).isNullOrBlank()) return
        FirebaseMessaging.getInstance().token.addOnSuccessListener { fcmToken ->
            if (!fcmToken.isNullOrBlank()) {
                updateToken(appCtx, fcmToken)
            }
        }
    }

    fun updateToken(ctx: Context, token: String) {
        val appCtx = ctx.applicationContext
        val id = userId(appCtx)
        if (id >= 0) {
            FirebaseFirestore.getInstance().collection(KOLEKSI).document(id.toString())
                .set(mapOf("user_id" to id, "fcm_token" to token), SetOptions.merge())
        }

        if (ApiConfig.ambilToken(appCtx).isNullOrBlank()) return
        val body = JSONObject().put("fcm_token", token)
        val req = ApiRequest(appCtx, Request.Method.PUT, ApiConfig.URL_NOTIFIKASI, body, {}, {})
        Volley.newRequestQueue(appCtx).add(req)
    }

    private fun pref(ctx: Context) =
        ctx.getSharedPreferences("PengaturanApotek", Context.MODE_PRIVATE)

    fun bacaLokal(ctx: Context): PengaturanNotifikasi {
        val p = pref(ctx)
        return PengaturanNotifikasi(
            p.getBoolean("NOTIF_STOK", true),
            p.getInt("BATAS_STOK", 10),
            p.getString("JAM_NOTIF", "08:00") ?: "08:00"
        )
    }

    private fun simpanLokal(ctx: Context, p: PengaturanNotifikasi) {
        pref(ctx).edit()
            .putBoolean("NOTIF_STOK", p.notifAktif)
            .putInt("BATAS_STOK", p.batasStok)
            .putString("JAM_NOTIF", p.jam)
            .apply()
    }
}
