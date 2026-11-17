package com.example.apotek_astria.ui.riwayat

import android.content.Context
import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.TextView
import com.example.apotek_astria.R
import com.example.apotek_astria.model.TransaksiModel
import java.text.NumberFormat
import java.util.Locale

class RiwayatAdapter(
    context: Context,
    private val dataList: List<TransaksiModel>
) : ArrayAdapter<TransaksiModel>(context, 0, dataList) {

    private val rupiahFormat = NumberFormat.getCurrencyInstance(Locale.forLanguageTag("id-ID")).apply {
        maximumFractionDigits = 0
    }

    override fun getView(position: Int, convertView: View?, parent: ViewGroup): View {
        val view = convertView ?: LayoutInflater.from(context).inflate(R.layout.item_riwayat, parent, false)
        val item = getItem(position) ?: return view

        val tvNoTransaksi = view.findViewById<TextView>(R.id.tvNoTransaksi)
        val tvWaktuKasir = view.findViewById<TextView>(R.id.tvWaktuKasir)
        val tvTotalHarga = view.findViewById<TextView>(R.id.tvTotalHarga)
        val tvMetodeBayar = view.findViewById<TextView>(R.id.tvMetodeBayar)

        tvNoTransaksi.text = item.noTransaksi
        tvWaktuKasir.text = "${item.waktu} • Kasir: ${item.namaKasir}"
        tvTotalHarga.text = rupiahFormat.format(item.totalHarga).replace("Rp", "Rp ")

        val isQris = item.metodeBayar.equals("qris", ignoreCase = true)
        tvMetodeBayar.text = if (isQris) "QRIS" else "Cash"
        tvMetodeBayar.setTextColor(Color.parseColor(if (isQris) "#1D4ED8" else "#1F7A6F"))
        tvMetodeBayar.background = GradientDrawable().apply {
            setColor(Color.parseColor(if (isQris) "#DBEAFE" else "#E8F4F2"))
            cornerRadius = 10f * context.resources.displayMetrics.density
        }

        return view
    }
}
