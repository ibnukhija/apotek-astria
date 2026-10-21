package com.example.apotek_astria.network

import com.android.volley.Response
import com.android.volley.VolleyError
import com.android.volley.toolbox.JsonObjectRequest
import org.json.JSONObject

object ApiConfig {
    // Ganti IP Sesuai dengan Laptop
    const val BASE_URL = "http://192.168.0.2:8000/api"

    // Daftar endpoint API yang Anda perlukan
    const val URL_LOGIN = "$BASE_URL/login-mobile"
    const val URL_DASHBOARD = "$BASE_URL/dashboard"
    const val URL_STOK = "$BASE_URL/stok"
    const val URL_RIWAYAT = "$BASE_URL/riwayat"
    const val URL_PROFIL = "$BASE_URL/profil"
    const val URL_KARYAWAN = "$BASE_URL/karyawan"

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

// Volley + header Accept JSON (wajib, kalau tidak Laravel redirect saat validasi gagal)
class ApiRequest(
    method: Int,
    url: String,
    body: JSONObject?,
    listener: Response.Listener<JSONObject>,
    errorListener: Response.ErrorListener
) : JsonObjectRequest(method, url, body, listener, errorListener) {
    override fun getHeaders(): MutableMap<String, String> {
        val h = HashMap<String, String>()
        h.putAll(super.getHeaders())
        h["Accept"] = "application/json"
        return h
    }
}
