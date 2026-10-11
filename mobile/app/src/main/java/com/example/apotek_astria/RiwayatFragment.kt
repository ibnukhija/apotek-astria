package com.example.apotek_astria

import android.app.AlertDialog
import android.app.DatePickerDialog
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.fragment.app.Fragment
import com.android.volley.Request
import com.android.volley.toolbox.JsonObjectRequest
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.databinding.FragmentRiwayatBinding
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

class RiwayatFragment : Fragment() {

    private var _binding: FragmentRiwayatBinding? = null
    private val binding get() = _binding!!

    private val transaksiList = mutableListOf<TransaksiModel>()
    private lateinit var adapter: RiwayatAdapter

    // Gunakan IP Laptop Anda untuk pengetesan lokal
    private val baseUrl = "http://10.125.116.156:8000/api/riwayat"

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentRiwayatBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        adapter = RiwayatAdapter(requireContext(), transaksiList)
        binding.lvRiwayat.adapter = adapter

        // Setup DatePickerDialog untuk Filter Tanggal
        setupDatePicker()

        // Tombol Filter & Reset
        binding.btnFilter.setOnClickListener {
            val tglMulai = binding.etTanggalMulai.text.toString().trim()
            val tglSelesai = binding.etTanggalSelesai.text.toString().trim()

            if (tglMulai.isNotEmpty() && tglSelesai.isNotEmpty()) {
                loadRiwayatTransaksi("$baseUrl?tanggal_mulai=$tglMulai&tanggal_selesai=$tglSelesai")
            } else {
                Toast.makeText(requireContext(), "Pilih kedua rentang tanggal", Toast.LENGTH_SHORT).show()
            }
        }

        binding.btnReset.setOnClickListener {
            binding.etTanggalMulai.setText("")
            binding.etTanggalSelesai.setText("")
            loadRiwayatTransaksi(baseUrl)
        }

        // Klik salah satu baris di ListView -> Tampilkan Dialog Rincian Transaksi
        binding.lvRiwayat.setOnItemClickListener { _, _, position, _ ->
            val transaksi = transaksiList[position]
            tampilkanDetailTransaksi(transaksi)
        }

        // Muat data awal saat Fragment dibuka
        loadRiwayatTransaksi(baseUrl)
    }

    private fun setupDatePicker() {
        val calendar = Calendar.getInstance()

        binding.etTanggalMulai.setOnClickListener {
            DatePickerDialog(
                requireContext(),
                { _, year, month, dayOfMonth ->
                    val selectedDate = Calendar.getInstance().apply {
                        set(year, month, dayOfMonth)
                    }
                    val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
                    binding.etTanggalMulai.setText(sdf.format(selectedDate.time))
                },
                calendar.get(Calendar.YEAR),
                calendar.get(Calendar.MONTH),
                calendar.get(Calendar.DAY_OF_MONTH)
            ).show()
        }

        binding.etTanggalSelesai.setOnClickListener {
            DatePickerDialog(
                requireContext(),
                { _, year, month, dayOfMonth ->
                    val selectedDate = Calendar.getInstance().apply {
                        set(year, month, dayOfMonth)
                    }
                    val sdf = SimpleDateFormat("yyyy-MM-dd", Locale.getDefault())
                    binding.etTanggalSelesai.setText(sdf.format(selectedDate.time))
                },
                calendar.get(Calendar.YEAR),
                calendar.get(Calendar.MONTH),
                calendar.get(Calendar.DAY_OF_MONTH)
            ).show()
        }
    }

    private fun loadRiwayatTransaksi(url: String) {
        binding.progressBar.visibility = View.VISIBLE
        binding.tvEmpty.visibility = View.GONE

        val request = JsonObjectRequest(
            Request.Method.GET, url, null,
            { response ->
                binding.progressBar.visibility = View.GONE
                transaksiList.clear()

                if (response.optBoolean("success", false)) {
                    val dataArray = response.optJSONArray("data")
                    if (dataArray != null && dataArray.length() > 0) {
                        for (i in 0 until dataArray.length()) {
                            val obj = dataArray.getJSONObject(i)
                            val userObj = obj.optJSONObject("user")
                            val kasir = userObj?.optString("nama") ?: "Kasir"

                            val detailsArray = obj.optJSONArray("details")
                            val detailList = mutableListOf<DetailObat>()

                            if (detailsArray != null) {
                                for (j in 0 until detailsArray.length()) {
                                    val d = detailsArray.getJSONObject(j)
                                    val obatObj = d.optJSONObject("obat")
                                    val namaObat = obatObj?.optString("nama_obat") ?: "Obat"
                                    detailList.add(
                                        DetailObat(
                                            namaObat = namaObat,
                                            qty = d.optInt("qty", 1),
                                            hargaSatuan = d.optInt("harga_satuan", 0),
                                            subtotal = d.optInt("subtotal", 0)
                                        )
                                    )
                                }
                            }

                            // Format waktu created_at
                            val rawWaktu = obj.optString("created_at")
                            val formatWaktu = if (rawWaktu.length >= 16) {
                                rawWaktu.substring(0, 10) + " " + rawWaktu.substring(11, 16)
                            } else rawWaktu

                            transaksiList.add(
                                TransaksiModel(
                                    id = obj.optInt("id"),
                                    noTransaksi = obj.optString("no_transaksi"),
                                    waktu = formatWaktu,
                                    namaKasir = kasir,
                                    totalHarga = obj.optInt("total_harga", 0),
                                    totalBayar = obj.optInt("total_bayar", 0),
                                    kembalian = obj.optInt("kembalian", 0),
                                    details = detailList
                                )
                            )
                        }
                    }
                }

                adapter.notifyDataSetChanged()
                binding.tvEmpty.visibility = if (transaksiList.isEmpty()) View.VISIBLE else View.GONE
            },
            { error ->
                binding.progressBar.visibility = View.GONE
                Toast.makeText(requireContext(), "Gagal memuat riwayat transaksi", Toast.LENGTH_SHORT).show()
            }
        )

        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun tampilkanDetailTransaksi(trx: TransaksiModel) {
        val rupiah = NumberFormat.getCurrencyInstance(Locale("id", "ID")).apply {
            maximumFractionDigits = 0
        }

        val sb = StringBuilder()
        sb.append("Waktu: ${trx.waktu}\n")
        sb.append("Kasir: ${trx.namaKasir}\n\n")
        sb.append("Daftar Obat Dibeli:\n")
        sb.append("--------------------------------\n")

        for (item in trx.details) {
            sb.append("${item.namaObat}\n")
            sb.append("  ${item.qty} x ${rupiah.format(item.hargaSatuan)} = ${rupiah.format(item.subtotal)}\n")
        }

        sb.append("--------------------------------\n")
        sb.append("Total Belanja : ${rupiah.format(trx.totalHarga)}\n")
        sb.append("Total Bayar   : ${rupiah.format(trx.totalBayar)}\n")
        sb.append("Kembalian     : ${rupiah.format(trx.kembalian)}\n")

        AlertDialog.Builder(requireContext())
            .setTitle(trx.noTransaksi)
            .setMessage(sb.toString())
            .setPositiveButton("Tutup", null)
            .show()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
