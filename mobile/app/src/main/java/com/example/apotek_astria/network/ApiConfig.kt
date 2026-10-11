package com.example.apotek_astria.network

object ApiConfig {
    // Ganti IP Sesuai dengan Laptop
    private const val BASE_URL = "http://192.168.0.3:8000/api"

    // Daftar endpoint API yang Anda perlukan
    const val URL_LOGIN = "$BASE_URL/login-mobile"
    const val URL_DASHBOARD = "$BASE_URL/dashboard"
    const val URL_STOK = "$BASE_URL/stok"
    const val URL_RIWAYAT = "$BASE_URL/riwayat"
    const val URL_PROFIL = "$BASE_URL/profil"
}
