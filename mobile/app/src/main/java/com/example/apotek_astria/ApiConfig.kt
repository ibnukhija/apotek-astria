package com.example.apotek_astria

import com.android.volley.Response
import com.android.volley.VolleyError
import com.android.volley.toolbox.JsonObjectRequest
import org.json.JSONObject

object ApiConfig {
    // GANTI sesuai IP WiFi laptop (sama dengan yang di LoginActivity)
    const val BASE_URL = "http://192.168.1.6:8000/api"

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