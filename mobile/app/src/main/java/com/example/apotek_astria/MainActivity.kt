package com.example.apotek_astria

import android.content.Intent
import android.os.Bundle
import android.widget.ImageView
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.google.android.material.bottomnavigation.BottomNavigationView

class MainActivity : AppCompatActivity() {
    private lateinit var tvHeaderTitle: TextView
    private lateinit var btnLogout: ImageView

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        tvHeaderTitle = findViewById(R.id.tv_header_title)
        btnLogout = findViewById(R.id.btn_logout)
        val bottomNav = findViewById<BottomNavigationView>(R.id.bottom_navigation)

        // Logout click listener
        btnLogout.setOnClickListener {
            // TODO: Tambahkan fungsi hapus session/token sebelum pindah ke Login
            val intent = Intent(this, LoginActivity::class.java)
            startActivity(intent)
            finish()
        }

        // Memuat fragment pertama saat aplikasi berhasil masuk
        loadFragment(BerandaFragment(), "Beranda")

        bottomNav.setOnItemSelectedListener { item ->
            var selectedFragment: Fragment? = null
            var title = ""
            when (item.itemId) {
                R.id.nav_beranda -> {
                    selectedFragment = BerandaFragment()
                    title = "Beranda"
                }
                R.id.nav_riwayat -> {
                    selectedFragment = RiwayatFragment()
                    title = "Riwayat"
                }
                R.id.nav_stok -> {
                    selectedFragment = StokFragment()
                    title = "Stok Obat"
                }
                R.id.nav_profil -> {
                    selectedFragment = ProfilFragment()
                    title = "Profil"
                }
            }
            if (selectedFragment != null) {
                loadFragment(selectedFragment, title)
            }
            true
        }
    }

    private fun loadFragment(fragment: Fragment, title: String) {
        tvHeaderTitle.text = title
        supportFragmentManager.beginTransaction()
            .replace(R.id.fragment_container, fragment)
            .commit()
    }
}