package com.example.apotek_astria.ui.beranda

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.PopupMenu
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.Fragment
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.R
import com.example.apotek_astria.databinding.FragmentBerandaBinding
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import java.text.NumberFormat
import java.util.Locale

class BerandaFragment : Fragment() {

    private var _binding: FragmentBerandaBinding? = null
    private val binding get() = _binding!!

    private var periodeAktif = "harian"
    private val daftarPeringatanStok = mutableListOf<String>()
    private val daftarPeringatanKadaluarsa = mutableListOf<String>()

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentBerandaBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        // Setup SwipeRefreshLayout
        binding.swipeRefreshLayout.setOnRefreshListener {
            loadDashboardData()
        }

        // Pilih periode dashboard (Harian / Mingguan / Bulanan)
        binding.tvFilterPeriode.setOnClickListener {
            tampilkanMenuPeriode(it)
        }

        // Klik banner peringatan untuk melihat daftar obatnya
        binding.layoutPeringatanStok.setOnClickListener {
            tampilkanDaftarPeringatan("Obat Hampir Habis (Stok < 10)", daftarPeringatanStok)
        }
        binding.layoutPeringatanKadaluarsa.setOnClickListener {
            tampilkanDaftarPeringatan("Obat Kadaluarsa / Hampir Kadaluarsa", daftarPeringatanKadaluarsa)
        }

        // Direct ke halaman Riwayat saat klik Lihat Semua
        binding.tvLihatSemua.setOnClickListener {
            val bottomNav = requireActivity().findViewById<com.google.android.material.bottomnavigation.BottomNavigationView>(R.id.bottom_navigation)
            bottomNav.selectedItemId = R.id.nav_riwayat
        }

        // Aktifkan animasi swipe refresh saat pertama kali dimuat
        binding.swipeRefreshLayout.isRefreshing = true

        // Panggil fungsi untuk mengambil data dari Laravel
        loadDashboardData()
    }

    private fun tampilkanMenuPeriode(anchor: View) {
        val popup = PopupMenu(requireContext(), anchor)
        popup.menu.add(0, 1, 0, "Harian")
        popup.menu.add(0, 2, 1, "Mingguan")
        popup.menu.add(0, 3, 2, "Bulanan")
        popup.setOnMenuItemClickListener { item ->
            val pilihan = when (item.itemId) {
                2 -> "mingguan"
                3 -> "bulanan"
                else -> "harian"
            }
            if (pilihan != periodeAktif) {
                periodeAktif = pilihan
                binding.tvFilterPeriode.text = labelChipPeriode(periodeAktif)
                binding.swipeRefreshLayout.isRefreshing = true
                loadDashboardData()
            }
            true
        }
        popup.show()
    }

    private fun labelChipPeriode(periode: String): String = when (periode) {
        "mingguan" -> "Mingguan"
        "bulanan" -> "Bulanan"
        else -> "Harian"
    }

    private fun labelWaktuPeriode(periode: String): String = when (periode) {
        "mingguan" -> "minggu ini"
        "bulanan" -> "bulan ini"
        else -> "hari ini"
    }

    private fun tampilkanDaftarPeringatan(judul: String, daftar: List<String>) {
        if (daftar.isEmpty()) return
        AlertDialog.Builder(requireContext())
            .setTitle(judul)
            .setMessage(daftar.joinToString("\n"))
            .setPositiveButton("Lihat Stok") { _, _ ->
                val bottomNav = requireActivity().findViewById<com.google.android.material.bottomnavigation.BottomNavigationView>(R.id.bottom_navigation)
                bottomNav.selectedItemId = R.id.nav_stok
            }
            .setNegativeButton("Tutup", null)
            .show()
    }

    private fun loadDashboardData() {
        // Gunakan Base URL dari ApiConfig + filter periode
        val url = "${ApiConfig.URL_DASHBOARD}?periode=$periodeAktif"
        val request = ApiRequest(requireContext(), Request.Method.GET, url, null,
            { response ->
                // Pengaman membatalkan proses jika pengguna sudah pindah tab/halaman
                if (!isAdded || _binding == null) return@ApiRequest

                if (response.optBoolean("success", false)) {
                    val data = response.getJSONObject("data")

                    // 1. Ekstrak Data
                    val periodeRespon = data.optString("periode", periodeAktif)
                    val totalPenjualan = data.optInt("total_penjualan", 0)
                    val jumlahTransaksi = data.optInt("jumlah_transaksi", 0)
                    val transaksiTerbaruArray = data.optJSONArray("transaksi_terbaru")
                    val peringatanObatArray = data.optJSONArray("peringatan_obat")
                    val peringatanKadaluarsaArray = data.optJSONArray("peringatan_kadaluarsa")

                    // 2. Format Angka ke Rupiah & Periode
                    val formatRupiah = NumberFormat.getNumberInstance(Locale.forLanguageTag("id-ID"))
                    binding.tvFilterPeriode.text = labelChipPeriode(periodeRespon)
                    binding.tvTotalPenjualan.text = "Rp ${formatRupiah.format(totalPenjualan)}"
                    binding.tvJumlahTransaksi.text = "$jumlahTransaksi transaksi ${labelWaktuPeriode(periodeRespon)}"

                    // 3. Tampil Peringatan Stok (Jika Ada)
                    daftarPeringatanStok.clear()
                    if (peringatanObatArray != null && peringatanObatArray.length() > 0) {
                        for (i in 0 until peringatanObatArray.length()) {
                            val o = peringatanObatArray.getJSONObject(i)
                            daftarPeringatanStok.add("• ${o.optString("nama_obat")} (stok: ${o.optInt("stok", 0)})")
                        }
                        binding.layoutPeringatanStok.visibility = View.VISIBLE
                        binding.tvPeringatanStok.text = "${peringatanObatArray.length()} obat hampir habis (stok < 10)"
                    } else {
                        binding.layoutPeringatanStok.visibility = View.GONE
                    }

                    // 3b. Tampil Peringatan Kadaluarsa (Jika Ada)
                    daftarPeringatanKadaluarsa.clear()
                    if (peringatanKadaluarsaArray != null && peringatanKadaluarsaArray.length() > 0) {
                        var jumlahKadaluarsa = 0
                        var jumlahHampir = 0
                        for (i in 0 until peringatanKadaluarsaArray.length()) {
                            val o = peringatanKadaluarsaArray.getJSONObject(i)
                            val statusKad = o.optString("status_kadaluarsa", "")
                            val tglExp = ApiConfig.formatTanggal(
                                if (o.isNull("tgl_kadaluarsa")) "" else o.optString("tgl_kadaluarsa", "")
                            )
                            if (statusKad.equals("Kadaluarsa", ignoreCase = true)) {
                                jumlahKadaluarsa++
                            } else {
                                jumlahHampir++
                            }
                            daftarPeringatanKadaluarsa.add("• ${o.optString("nama_obat")} ($tglExp • $statusKad)")
                        }
                        val bagian = mutableListOf<String>()
                        if (jumlahKadaluarsa > 0) bagian.add("$jumlahKadaluarsa obat kadaluarsa")
                        if (jumlahHampir > 0) bagian.add("$jumlahHampir hampir kadaluarsa")
                        binding.layoutPeringatanKadaluarsa.visibility = View.VISIBLE
                        binding.tvPeringatanKadaluarsa.text = bagian.joinToString(" • ")
                    } else {
                        binding.layoutPeringatanKadaluarsa.visibility = View.GONE
                    }

                    // 4. Muat Transaksi Terbaru ke Layout (Secara Dinamis)
                    binding.layoutListTransaksi.removeAllViews() // Membersihkan list
                    if (transaksiTerbaruArray != null && transaksiTerbaruArray.length() > 0) {
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

                            // Mengolah tanggal & nama kasir ke waktu lokal
                            val rawDate = trx.getString("created_at")
                            val jamTrx = ApiConfig.formatWaktuIso(rawDate, "HH:mm")
                            val userObj = trx.optJSONObject("user")
                            val namaKasir = userObj?.optString("nama", "Kasir") ?: "Kasir"
                            tvWaktuKasir.text = "$jamTrx / $namaKasir"

                            // Tambahkan itemView ke dalam container
                            binding.layoutListTransaksi.addView(itemView)
                        }
                    } else {
                        // Jika belum ada transaksi
                        val emptyText = TextView(requireContext()).apply {
                            text = "Belum ada transaksi"
                            setPadding(0, 30, 0, 30)
                            gravity = android.view.Gravity.CENTER
                        }
                        binding.layoutListTransaksi.addView(emptyText)
                    }
                    // Sembunyikan loading
                    binding.swipeRefreshLayout.isRefreshing = false
                }
            },
            { error ->
                // Pengaman membatalkan proses jika pengguna sudah pindah tab/layar
                if (!isAdded || _binding == null) return@ApiRequest

                binding.swipeRefreshLayout.isRefreshing = false
                if (!ApiConfig.tanganiAuthError(context, error)) {
                    Toast.makeText(context, ApiConfig.pesanError(error), Toast.LENGTH_SHORT).show()
                }
            }
        )
        // Tambahkan antrian Request ke Volley
        Volley.newRequestQueue(requireContext()).add(request)
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}