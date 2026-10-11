package com.example.apotek_astria

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.text.method.HideReturnsTransformationMethod
import android.text.method.PasswordTransformationMethod
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.android.volley.Request
import com.android.volley.toolbox.JsonObjectRequest
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.databinding.ActivityLoginBinding
import org.json.JSONObject

class LoginActivity : AppCompatActivity() {

    // Deklarasi variabel binding
    private lateinit var binding: ActivityLoginBinding

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        // Inisialisasi View Binding
        binding = ActivityLoginBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Cek Sesi Login di SharedPreferences
        val sharedPref = getSharedPreferences("SesiApotek", Context.MODE_PRIVATE)
        if (sharedPref.getBoolean("IS_LOGGED_IN", false)) {
            startActivity(Intent(this, MainActivity::class.java))
            finish()
        }

        // Logika Checkbox Tampilkan Password (langsung pakai binding.namaId)
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
        val url = "http://192.168.0.3:8000/api/login-mobile"

        val jsonBody = JSONObject()
        jsonBody.put("username", user)
        jsonBody.put("password", pass)

        val request = JsonObjectRequest(Request.Method.POST, url, jsonBody,
            { response ->
                if (response.optBoolean("success", false)) {
                    val data = response.getJSONObject("data")

                    // Simpan sesi
                    getSharedPreferences("SesiApotek", Context.MODE_PRIVATE).edit().apply {
                        putBoolean("IS_LOGGED_IN", true)
                        putInt("USER_ID", data.getInt("id"))
                        putString("USER_NAMA", data.getString("nama"))
                        apply()
                    }

                    Toast.makeText(this, "Selamat datang, ${data.getString("nama")}", Toast.LENGTH_SHORT).show()
                    startActivity(Intent(this, MainActivity::class.java))
                    finish()
                } else {
                    Toast.makeText(this, response.getString("message"), Toast.LENGTH_SHORT).show()
                }
            },
            { error ->
                // Mengecek apakah koneksi ke server berhasil atau tidak
                android.util.Log.e("API_ERROR", "Gagal menghubungi server: ${error.message}", error)
                Toast.makeText(this, "Koneksi ke Laravel Gagal", Toast.LENGTH_SHORT).show()
            }
        )
        Volley.newRequestQueue(this).add(request)
    }
}