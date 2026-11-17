package com.example.apotek_astria.ui.login

import android.content.Intent
import android.os.Bundle
import android.text.method.HideReturnsTransformationMethod
import android.text.method.PasswordTransformationMethod
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.MainActivity
import com.example.apotek_astria.databinding.ActivityLoginBinding
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import org.json.JSONObject

class LoginActivity : AppCompatActivity() {

    // Deklarasi variabel binding
    private lateinit var binding: ActivityLoginBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Cek Sesi Login & Token Sanctum di SharedPreferences
        val sharedPref = ApiConfig.sesiPref(this)
        val token = sharedPref.getString(ApiConfig.KEY_TOKEN, null)
        if (sharedPref.getBoolean(ApiConfig.KEY_LOGGED_IN, false) && !token.isNullOrBlank()) {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
            return
        } else if (sharedPref.getBoolean(ApiConfig.KEY_LOGGED_IN, false)) {
            ApiConfig.hapusSesi(this)
        }

        // Logika Checkbox Tampilkan Password pakai binding.namaId
        binding.cbShowPassword.setOnCheckedChangeListener { _, isChecked ->
            if (isChecked) {
                binding.etPassword.transformationMethod = HideReturnsTransformationMethod.getInstance()
            } else {
                binding.etPassword.transformationMethod = PasswordTransformationMethod.getInstance()
            }
            binding.etPassword.setSelection(binding.etPassword.text.length)
        }

        // Logika Tombol Login
        binding.btnLogin.setOnClickListener {
            val username = binding.etUsername.text.toString().trim()
            val password = binding.etPassword.text.toString().trim()

            if (username.isNotEmpty() && password.isNotEmpty()) {
                prosesLogin(username, password)
            } else {
                Toast.makeText(this, "Username & Password wajib diisi", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun prosesLogin(user: String, pass: String) {
        val url = ApiConfig.URL_LOGIN

        val jsonBody = JSONObject()
        jsonBody.put("username", user)
        jsonBody.put("password", pass)

        val request = ApiRequest(this, Request.Method.POST, url, jsonBody,
            { response ->
                if (response.optBoolean("success", false)) {
                    val data = response.getJSONObject("data")
                    val token = data.optString("token", "")
                    if (token.isBlank()) {
                        Toast.makeText(this, "Token login tidak ditemukan", Toast.LENGTH_SHORT).show()
                        return@ApiRequest
                    }

                    // Simpan sesi + token Sanctum
                    ApiConfig.simpanSesi(
                        context = this,
                        userId = data.getInt("id"),
                        nama = data.getString("nama"),
                        token = token
                    )

                    Toast.makeText(this, "Selamat datang, ${data.getString("nama")}", Toast.LENGTH_SHORT).show()
                    startActivity(Intent(this, MainActivity::class.java))
                    finish()
                } else {
                    Toast.makeText(this, response.optString("message", "Login gagal"), Toast.LENGTH_SHORT).show()
                }
            },
            { error ->
                android.util.Log.e("API_ERROR", "Gagal menghubungi server: ${error.message}", error)
                Toast.makeText(this, ApiConfig.pesanError(error), Toast.LENGTH_SHORT).show()
            }
        )
        Volley.newRequestQueue(this).add(request)
    }
}