package com.example.apotek_astria.network

import android.content.Context
import android.content.Intent
import android.widget.Toast
import com.android.volley.Response
import com.android.volley.VolleyError
import com.android.volley.toolbox.JsonObjectRequest
import com.example.apotek_astria.ui.login.LoginActivity
import org.json.JSONObject
import java.text.SimpleDateFormat
import java.util.Locale
import java.util.TimeZone

object ApiConfig {
    // Ganti IP Sesuai dengan Laptop
    const val BASE_URL = "http://10.134.74.161:8000/api"

    // Daftar endpoint API yang Anda perlukan
    const val URL_LOGIN = "$BASE_URL/login-mobile"
    const val URL_LOGOUT = "$BASE_URL/logout-mobile"
    const val URL_DASHBOARD = "$BASE_URL/dashboard"
    const val URL_STOK = "$BASE_URL/stok"
    const val URL_STOK_OPNAME = "$BASE_URL/stok-opname"
    const val URL_RIWAYAT = "$BASE_URL/riwayat"
    const val URL_LAPORAN = "$BASE_URL/laporan"
    const val URL_LAPORAN_EXPORT = "$BASE_URL/laporan/export"
    const val URL_PROFIL = "$BASE_URL/profil"
    const val URL_KARYAWAN = "$BASE_URL/karyawan"
    const val URL_NOTIFIKASI = "$BASE_URL/notifikasi"
    const val URL_NOTIFIKASI_TES = "$URL_NOTIFIKASI/tes"
    const val URL_OBAT = "$BASE_URL/obat"

    const val PREF_SESI = "SesiApotek"
    const val KEY_LOGGED_IN = "IS_LOGGED_IN"
    const val KEY_USER_ID = "USER_ID"
    const val KEY_USER_NAMA = "USER_NAMA"
    const val KEY_TOKEN = "AUTH_TOKEN"
    const val URL_CHATBOT = "$BASE_URL/chatbot"

    fun sesiPref(context: Context) =
        context.applicationContext.getSharedPreferences(PREF_SESI, Context.MODE_PRIVATE)

    fun ambilToken(context: Context): String? =
        sesiPref(context).getString(KEY_TOKEN, null)

    fun simpanSesi(context: Context, userId: Int, nama: String, token: String) {
        sesiPref(context).edit().apply {
            putBoolean(KEY_LOGGED_IN, true)
            putInt(KEY_USER_ID, userId)
            putString(KEY_USER_NAMA, nama)
            putString(KEY_TOKEN, token)
            apply()
        }
    }

    fun hapusSesi(context: Context) {
        sesiPref(context).edit().clear().apply()
    }

    // Jika token tidak valid / kedaluwarsa (401) atau akses ditolak (403), bersihkan sesi dan kembali ke Login
    fun tanganiAuthError(context: Context?, error: VolleyError): Boolean {
        val statusCode = error.networkResponse?.statusCode ?: return false
        if (statusCode != 401 && statusCode != 403) return false
        val appContext = context?.applicationContext ?: return false

        hapusSesi(appContext)
        val pesan = if (statusCode == 401) {
            "Sesi berakhir, silakan login ulang"
        } else {
            pesanError(error)
        }
        Toast.makeText(appContext, pesan, Toast.LENGTH_SHORT).show()

        val intent = Intent(appContext, LoginActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
        }
        appContext.startActivity(intent)
        return true
    }

    // Konversi timestamp ISO UTC Laravel (atau yyyy-MM-dd HH:mm:ss) ke zona waktu perangkat
    fun formatWaktuIso(waktuRaw: String, outPattern: String = "dd/MM/yyyy HH:mm"): String {
        if (waktuRaw.isBlank()) return "-"
        val polaUtc = listOf(
            "yyyy-MM-dd'T'HH:mm:ss.SSSSSS'Z'",
            "yyyy-MM-dd'T'HH:mm:ss'Z'"
        )
        val localeId = Locale.forLanguageTag("id-ID")
        for (pola in polaUtc) {
            try {
                val parser = SimpleDateFormat(pola, Locale.US).apply {
                    timeZone = TimeZone.getTimeZone("UTC")
                }
                val date = parser.parse(waktuRaw)
                if (date != null) {
                    return SimpleDateFormat(outPattern, localeId).format(date)
                }
            } catch (_: Exception) {
            }
        }
        try {
            val parserLokal = SimpleDateFormat("yyyy-MM-dd HH:mm:ss", Locale.US)
            val date = parserLokal.parse(waktuRaw)
            if (date != null) {
                return SimpleDateFormat(outPattern, localeId).format(date)
            }
        } catch (_: Exception) {
        }
        return waktuRaw
    }

    // Format tanggal yyyy-MM-dd menjadi dd/MM/yyyy
    fun formatTanggal(tanggalRaw: String, outPattern: String = "dd/MM/yyyy"): String {
        if (tanggalRaw.isBlank() || tanggalRaw == "null") return ""
        val bagianTanggal = tanggalRaw.take(10)
        return try {
            val date = SimpleDateFormat("yyyy-MM-dd", Locale.US).parse(bagianTanggal)
            if (date != null) {
                SimpleDateFormat(outPattern, Locale.forLanguageTag("id-ID")).format(date)
            } else bagianTanggal
        } catch (_: Exception) {
            bagianTanggal
        }
    }

    // Ambil pesan validasi Laravel (422) supaya bisa ditampilkan ke user
    fun pesanError(error: VolleyError): String {
        val data = error.networkResponse?.data ?: return "Koneksi ke server gagal"
        return try {
            val json = JSONObject(String(data))
            val errors = json.optJSONObject("errors")
            if (errors != null && errors.keys().hasNext()) {
                errors.getJSONArray(errors.keys().next()).getString(0)
            } else json.optString("message", "Terjadi kesalahan")
        } catch (e: Exception) {
            "Terjadi kesalahan"
        }
    }
}

// Volley + header Accept JSON & Bearer Token Sanctum
class ApiRequest(
    context: Context,
    method: Int,
    url: String,
    body: JSONObject?,
    listener: Response.Listener<JSONObject>,
    errorListener: Response.ErrorListener
) : JsonObjectRequest(method, url, body, listener, errorListener) {
    private val token: String? = ApiConfig.ambilToken(context)

    override fun getHeaders(): MutableMap<String, String> {
        val h = HashMap<String, String>()
        h.putAll(super.getHeaders())
        h["Accept"] = "application/json"
        if (!token.isNullOrBlank()) {
            h["Authorization"] = "Bearer $token"
        }
        return h
    }
}
