package com.example.apotek_astria

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import android.widget.Toast
import androidx.fragment.app.Fragment
import com.android.volley.Request
import com.android.volley.toolbox.JsonObjectRequest
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.R
import com.example.apotek_astria.databinding.FragmentBerandaBinding
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Locale

class BerandaFragment : Fragment() {

    private var _binding: FragmentBerandaBinding? = null
    private val binding get() = _binding!!

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentBerandaBinding.inflate(inflater, container, false)
        return binding.root
    }

    //
    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        
        // Direct ke halaman Riwayat saat klik Lihat Semua
        binding.tvLihatSemua.setOnClickListener {
            val bottomNav = requireActivity().findViewById<com.google.android.material.bottomnavigation.BottomNavigationView>(R.id.bottom_navigation)
            bottomNav.selectedItemId = R.id.nav_riwayat
        }
        // Tampilkan loading, sembunyikan layout data
        binding.pbLoading.visibility = View.VISIBLE
        // Panggil fungsi untuk mengambil data dari Laravel
        loadDashboardData()
    }
    private fun loadDashboardData() {
        // Gunakan Base URL dari ApiConfig
        val url = com.example.apotek_astria.network.ApiConfig.URL_DASHBOARD

        val request = JsonObjectRequest(Request.Method.GET, url, null,
            { response ->
                // PENGAMAN: Batalkan proses jika pengguna sudah pindah tab/layar
                if (!isAdded || _binding == null) return@JsonObjectRequest

                if (response.optBoolean("success", false)) {
                    val data = response.getJSONObject("data")

                    // 1. Ekstrak Data
                    val totalPenjualan = data.optInt("total_penjualan", 0)
                    val jumlahTransaksi = data.optInt("jumlah_transaksi", 0)
                    val transaksiTerbaruArray = data.getJSONArray("transaksi_terbaru")
                    val peringatanObatArray = data.getJSONArray("peringatan_obat")

                    // 2. Format Angka ke Rupiah
                    val formatRupiah = NumberFormat.getNumberInstance(Locale("in", "ID"))
                    binding.tvTotalPenjualan.text = "Rp ${formatRupiah.format(totalPenjualan)}"
                    binding.tvJumlahTransaksi.text = "$jumlahTransaksi transaksi hari ini"

                    // 3. Tampil Peringatan Stok (Jika Ada)
                    if (peringatanObatArray.length() > 0) {
                        binding.layoutPeringatanStok.visibility = View.VISIBLE
                        binding.tvPeringatanStok.text = "${peringatanObatArray.length()} obat hampir habis (stok <= 10)"
                    } else {
                        binding.layoutPeringatanStok.visibility = View.GONE
                    }

                    // 4. Muat Transaksi Terbaru ke Layout (Secara Dinamis)
                    binding.layoutListTransaksi.removeAllViews() // Bersihkan list

                    if (transaksiTerbaruArray.length() > 0) {
                        for (i in 0 until transaksiTerbaruArray.length()) {
                            val trx = transaksiTerbaruArray.getJSONObject(i)
                            val itemView = layoutInflater.inflate(R.layout.item_transaksi_terbaru, binding.layoutListTransaksi, false)
                            val tvKode = itemView.findViewById<TextView>(R.id.tv_kode_trx)
                            val tvWaktuKasir = itemView.findViewById<TextView>(R.id.tv_waktu_kasir)
                            val tvTotal = itemView.findViewById<TextView>(R.id.tv_total_harga)

                            // Ambil data detail JSON
                            tvKode.text = trx.getString("no_transaksi")
                            val totalTrx = trx.getInt("total_harga")
                            tvTotal.text = "Rp ${formatRupiah.format(totalTrx)}"

                            // Mengolah tanggal & nama kasir
                            val rawDate = trx.getString("created_at") // Contoh: 2026-10-12T07:30:00.000000Z
                            val jamTrx = formatWaktu(rawDate)
                            val userObj = trx.optJSONObject("user")
                            val namaKasir = userObj?.optString("nama", "Kasir") ?: "Kasir"
                            tvWaktuKasir.text = "$jamTrx / $namaKasir"

                            // Tambahkan itemView ke dalam container
                            binding.layoutListTransaksi.addView(itemView)
                        }
                    } else {
                        // Jika belum ada transaksi
                        val emptyText = TextView(requireContext()).apply {
                            text = "Belum ada transaksi hari ini"
                            setPadding(0, 30, 0, 30)
                            gravity = android.view.Gravity.CENTER
                        }
                        binding.layoutListTransaksi.addView(emptyText)
                    }
                    // Sembunyikan loading
                    binding.pbLoading.visibility = View.GONE
                }
            },
            { error ->
                // PENGAMAN: Batalkan proses jika pengguna sudah pindah tab/layar
                if (!isAdded || _binding == null) return@JsonObjectRequest

                binding.pbLoading.visibility = View.GONE
                Toast.makeText(context, "Gagal memuat dashboard", Toast.LENGTH_SHORT).show()
            }
        )
        // Tambahkan antrian Request ke Volley
        Volley.newRequestQueue(requireContext()).add(request)
    }
    // Fungsi kecil untuk mengubah "2026-10-12T07:30:00.000000Z" menjadi "07:30"
    private fun formatWaktu(waktuRaw: String): String {
        return try {
            val parser = SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss.SSSSSS'Z'", Locale.getDefault())
            parser.timeZone = java.util.TimeZone.getTimeZone("UTC") // Laravel biasanya pakai UTC
            val date = parser.parse(waktuRaw)
            val formatter = SimpleDateFormat("HH:mm", Locale.getDefault())
            formatter.format(date ?: "")
        } catch (e: Exception) {
            "00:00"
        }
    }
    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}