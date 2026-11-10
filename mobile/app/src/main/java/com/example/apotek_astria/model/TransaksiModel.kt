package com.example.apotek_astria

data class DetailObat(
    val namaObat: String,
    val qty: Int,
    val hargaSatuan: Int,
    val subtotal: Int
)

data class TransaksiModel(
    val id: Int,
    val noTransaksi: String,
    val waktu: String,
    val namaKasir: String,
    val totalHarga: Int,
    val totalBayar: Int,
    val kembalian: Int,
    val details: List<DetailObat>
)
