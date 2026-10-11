package com.example.apotek_astria

import android.content.Context
import android.graphics.Color
import android.view.Gravity
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.TextView
import java.text.NumberFormat
import java.util.Locale

class StokAdapter(
    context: Context,
    dataList: List<ObatModel>,
    private val onMenuClick: (ObatModel, View) -> Unit
) : ArrayAdapter<ObatModel>(context, 0, dataList) {

    private val rupiah = NumberFormat.getNumberInstance(Locale("in", "ID"))

    override fun getView(position: Int, convertView: View?, parent: ViewGroup): View {
        val view = convertView ?: LayoutInflater.from(context).inflate(R.layout.item_stok, parent, false)
        val item = getItem(position) ?: return view

        view.findViewById<TextView>(R.id.tvNamaObat).text = item.namaObat
        view.findViewById<TextView>(R.id.tvKategoriSatuan).text = "${item.kategori} • ${item.satuan}"

        val tidakTersedia = if (item.status != "tersedia") " • Tidak tersedia" else ""
        view.findViewById<TextView>(R.id.tvHarga).text = "Rp ${rupiah.format(item.hargaJual)}$tidakTersedia"

        // Label dan warna status stok mengikuti hitungan server (sama dengan web)
        val tvStok = view.findViewById<TextView>(R.id.tvStok)
        tvStok.gravity = Gravity.END
        tvStok.text = "Stok: ${item.stok}\n${item.statusStok}"
        val warna = when (item.statusStok.lowercase(Locale.ROOT)) {
            "hampir habis" -> "#D32F2F"   // merah
            "menipis" -> "#F9A825"        // kuning
            else -> "#2E7D32"             // hijau
        }
        tvStok.setTextColor(Color.parseColor(warna))

        view.findViewById<TextView>(R.id.tvMenu).setOnClickListener { onMenuClick(item, it) }
        return view
    }
}