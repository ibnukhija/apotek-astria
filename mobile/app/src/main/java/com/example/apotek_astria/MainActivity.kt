package com.example.apotek_astria

import android.content.Intent
import android.os.Bundle
import android.widget.ImageView
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.fragment.app.Fragment
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.google.android.material.bottomnavigation.BottomNavigationView
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import com.example.apotek_astria.ui.login.LoginActivity
import com.example.apotek_astria.ui.beranda.BerandaFragment
import com.example.apotek_astria.ui.riwayat.RiwayatFragment
import com.example.apotek_astria.ui.stok.StokFragment
import com.example.apotek_astria.ui.profil.ProfilFragment

class MainActivity : AppCompatActivity() {
    private lateinit var tvHeaderTitle: TextView
    private lateinit var btnLogout: ImageView
    private var pendingBukaLaporan = false

    fun bukaLaporan() {
        pendingBukaLaporan = true
        val bottomNav = findViewById<BottomNavigationView>(R.id.bottom_navigation)
        if (bottomNav.selectedItemId == R.id.nav_riwayat) {
            loadFragment(buatRiwayatFragment(), "Riwayat & Laporan")
        } else {
            bottomNav.selectedItemId = R.id.nav_riwayat
        }
    }

    private fun buatRiwayatFragment(): RiwayatFragment {
        val fragment = RiwayatFragment()
        if (pendingBukaLaporan) {
            pendingBukaLaporan = false
            fragment.arguments = Bundle().apply {
                putBoolean(RiwayatFragment.ARG_BUKA_LAPORAN, true)
            }
        }
        return fragment
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        tvHeaderTitle = findViewById(R.id.tv_header_title)
        btnLogout = findViewById(R.id.btn_logout)
        val bottomNav = findViewById<BottomNavigationView>(R.id.bottom_navigation)

        StokMessagingService.buatChannel(this)
        PengaturanNotifikasiRepo.sinkronToken(this)

        btnLogout.setOnClickListener {
            // Cabut token Sanctum di server lalu bersihkan sesi lokal
            val logoutReq = ApiRequest(
                applicationContext,
                Request.Method.POST,
                ApiConfig.URL_LOGOUT,
                null,
                {},
                {}
            )
            Volley.newRequestQueue(applicationContext).add(logoutReq)
            ApiConfig.hapusSesi(this)

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
                    selectedFragment = buatRiwayatFragment()
                    title = "Riwayat & Laporan"
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