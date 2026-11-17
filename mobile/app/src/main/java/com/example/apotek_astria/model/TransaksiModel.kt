package com.example.apotek_astria.model

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
    val metodeBayar: String = "cash",
    val metodeBayarLabel: String = "Cash",
    val totalHarga: Int,
    val totalBayar: Int,
    val kembalian: Int,
    val details: List<DetailObat>
)

data class LaporanItemModel(
    val detailId: Int,
    val noTransaksi: String,
    val tanggal: String,
    val namaKasir: String,
    val metodeBayar: String,
    val metodeBayarLabel: String,
    val namaKategori: String,
    val namaObat: String,
    val qty: Int,
    val hargaSatuan: Int,
    val subtotal: Int
)
