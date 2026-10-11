package com.example.apotek_astria

import android.content.Context
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.TextView
import java.text.NumberFormat
import java.util.Locale

class RiwayatAdapter(
    context: Context,
    private val dataList: List<TransaksiModel>
) : ArrayAdapter<TransaksiModel>(context, 0, dataList) {

    private val rupiahFormat = NumberFormat.getCurrencyInstance(Locale("id", "ID")).apply {
        maximumFractionDigits = 0
    }

    override fun getView(position: Int, convertView: View?, parent: ViewGroup): View {
        val view = convertView ?: LayoutInflater.from(context).inflate(R.layout.item_riwayat, parent, false)
        val item = getItem(position) ?: return view

        val tvNoTransaksi = view.findViewById<TextView>(R.id.tvNoTransaksi)
        val tvWaktuKasir = view.findViewById<TextView>(R.id.tvWaktuKasir)
        val tvTotalHarga = view.findViewById<TextView>(R.id.tvTotalHarga)

        tvNoTransaksi.text = item.noTransaksi
        tvWaktuKasir.text = "${item.waktu} • Kasir: ${item.namaKasir}"
        tvTotalHarga.text = rupiahFormat.format(item.totalHarga).replace("Rp", "Rp ")

        return view
    }
}
