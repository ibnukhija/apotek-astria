package com.example.apotek_astria

data class ObatModel(
    val id: Int,
    val kodeObat: String,
    val namaObat: String,
    val kategori: String,
    val satuan: String,
    val hargaBeli: Int,
    val margin: Double,
    val tipeMargin: String,   // "persen" / "nominal"
    val hargaJual: Int,
    val stok: Int,
    val status: String,       // "tersedia" / "tidak"
    val statusStok: String    // "Cukup" / "Menipis" / "Hampir habis" (dari server)
)

data class KaryawanModel(
    val id: Int,
    val nama: String,
    val username: String,
    val aktif: Boolean
)