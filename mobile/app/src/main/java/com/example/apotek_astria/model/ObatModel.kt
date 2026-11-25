package com.example.apotek_astria.model

data class ObatBatchModel(
    val kodeBatch: String,
    val supplier: String,
    val tglKadaluarsa: String,
    val stok: Int,
    val statusKadaluarsa: String
)

data class ObatModel(
    val id: Int,
    val kodeObat: String,
    val namaObat: String,
    val kategori: String,
    val satuan: String,
    val hargaBeli: Int,
    val margin: Double,
    val tipeMargin: String,       // "persen" / "nominal"
    val hargaJual: Int,
    val stok: Int,
    val status: String,           // "tersedia" / "tidak"
    val statusStok: String,       // "Cukup" / "Menipis" / "Hampir habis" (dari server)
    val tglKadaluarsa: String = "",
    val statusKadaluarsa: String = "",
    val totalRusak: Int = 0,
    val opnameTerakhir: String = "",
    val batches: List<ObatBatchModel> = emptyList()
)

data class KaryawanModel(
    val id: Int,
    val nama: String,
    val username: String,
    val aktif: Boolean
)