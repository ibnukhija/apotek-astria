package com.example.apotek_astria

import android.content.Context
import android.graphics.Color
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.ArrayAdapter
import android.widget.TextView

class KaryawanAdapter(
    context: Context,
    dataList: List<KaryawanModel>
) : ArrayAdapter<KaryawanModel>(context, 0, dataList) {

    override fun getView(position: Int, convertView: View?, parent: ViewGroup): View {
        val view = convertView ?: LayoutInflater.from(context).inflate(R.layout.item_karyawan, parent, false)
        val item = getItem(position) ?: return view

        view.findViewById<TextView>(R.id.tvNama).text = item.nama
        view.findViewById<TextView>(R.id.tvUsername).text = "@${item.username}"

        val tvStatus = view.findViewById<TextView>(R.id.tvStatus)
        if (item.aktif) {
            tvStatus.text = "Aktif"
            tvStatus.setTextColor(Color.parseColor("#2E7D32"))
        } else {
            tvStatus.text = "Nonaktif"
            tvStatus.setTextColor(Color.parseColor("#D32F2F"))
        }
        return view
    }
}