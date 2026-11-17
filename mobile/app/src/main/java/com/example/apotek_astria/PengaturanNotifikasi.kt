package com.example.apotek_astria

import android.content.Context
import com.google.firebase.Timestamp
import com.google.firebase.firestore.FirebaseFirestore
import com.google.firebase.firestore.SetOptions
import com.google.firebase.messaging.FirebaseMessaging

data class PengaturanNotifikasi(
    val notifAktif: Boolean = true,
    val batasStok: Int = 10,
    val jam: String = "08:00"
)

object PengaturanNotifikasiRepo {
    const val KOLEKSI = "pengaturan_notifikasi"

    private fun userId(ctx: Context) =
        ctx.getSharedPreferences("SesiApotek", Context.MODE_PRIVATE).getInt("USER_ID", -1)

    fun muat(ctx: Context, hasil: (PengaturanNotifikasi) -> Unit) {
        val lokal = bacaLokal(ctx)
        val id = userId(ctx)
        if (id < 0) { hasil(lokal); return }

        FirebaseFirestore.getInstance().collection(KOLEKSI).document(id.toString())
            .get()
            .addOnSuccessListener { d ->
                if (!d.exists()) { hasil(lokal); return@addOnSuccessListener }
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
        val id = userId(ctx)
        if (id < 0) { selesai(false); return }

        FirebaseMessaging.getInstance().token.addOnCompleteListener { t ->
            val data = hashMapOf<String, Any>(
                "user_id" to id,
                "notif_aktif" to p.notifAktif,
                "batas_stok" to p.batasStok,
                "jam_notifikasi" to p.jam,
                "updated_at" to Timestamp.now()
            )
            if (t.isSuccessful && t.result != null) data["fcm_token"] = t.result!!

            FirebaseFirestore.getInstance().collection(KOLEKSI).document(id.toString())
                .set(data, SetOptions.merge())
                .addOnSuccessListener { selesai(true) }
                .addOnFailureListener { selesai(false) }
        }
    }

    fun updateToken(ctx: Context, token: String) {
        val id = userId(ctx)
        if (id < 0) return
        FirebaseFirestore.getInstance().collection(KOLEKSI).document(id.toString())
            .set(mapOf("user_id" to id, "fcm_token" to token), SetOptions.merge())
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