package com.example.apotek_astria.ui.beranda

import android.graphics.Color
import android.graphics.drawable.GradientDrawable
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
    private var periodeGrafik = "harian"
    private val daftarPeringatanStok = mutableListOf<String>()
    private val daftarPeringatanKadaluarsa = mutableListOf<String>()
    private val formatRupiah by lazy { NumberFormat.getNumberInstance(Locale.forLanguageTag("id-ID")) }

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentBerandaBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        binding.swipeRefreshLayout.setOnRefreshListener {
            loadDashboardData()
        }

        // Pilih periode kartu pendapatan (Harian / Mingguan / Bulanan)
        binding.tvFilterPeriode.setOnClickListener {
            tampilkanMenuPeriode(it)
        }

        // Klik 2 kotak ringkasan berjejer untuk menampilkan daftar obatnya
        binding.cardStokMenipis.setOnClickListener {
            tampilkanDaftarPeringatan(
                "Obat Stok Menipis (Stok < 10)",
                daftarPeringatanStok,
                "Semua stok obat dalam batas aman (>= 10)."
            )
        }
        binding.cardKadaluarsa.setOnClickListener {
            tampilkanDaftarPeringatan(
                "Obat Kadaluarsa / Mau Kadaluarsa",
                daftarPeringatanKadaluarsa,
                "Tidak ada obat yang kadaluarsa atau mendekati kadaluarsa (<= 30 hari)."
            )
        }

        // Tab periode grafik penjualan
        binding.tvGrafikHarian.setOnClickListener { pilihPeriodeGrafik("harian") }
        binding.tvGrafikMingguan.setOnClickListener { pilihPeriodeGrafik("mingguan") }
        binding.tvGrafikBulanan.setOnClickListener { pilihPeriodeGrafik("bulanan") }
        perbaruiTampilanTabGrafik()

        binding.grafikPenjualan.onBarSelected = { titik ->
            if (_binding != null) {
                binding.tvInfoGrafik.text =
                    "${titik.keterangan}: Rp ${formatRupiah.format(titik.total)} (${titik.jumlahTransaksi} transaksi)"
            }
        }

        // Direct ke halaman Riwayat saat klik Lihat Semua
        binding.tvLihatSemua.setOnClickListener {
            val bottomNav = requireActivity().findViewById<com.google.android.material.bottomnavigation.BottomNavigationView>(R.id.bottom_navigation)
            bottomNav.selectedItemId = R.id.nav_riwayat
        }

        binding.swipeRefreshLayout.isRefreshing = true
        loadDashboardData()
    }

    private fun pilihPeriodeGrafik(periodeBaru: String) {
        if (periodeBaru == periodeGrafik) return
        periodeGrafik = periodeBaru
        perbaruiTampilanTabGrafik()
        binding.swipeRefreshLayout.isRefreshing = true
        loadDashboardData()
    }

    private fun perbaruiTampilanTabGrafik() {
        val tabs = listOf(
            binding.tvGrafikHarian to "harian",
            binding.tvGrafikMingguan to "mingguan",
            binding.tvGrafikBulanan to "bulanan"
        )
        val radius = 8f * resources.displayMetrics.density
        for ((tab, nilai) in tabs) {
            val aktif = nilai == periodeGrafik
            if (aktif) {
                tab.background = GradientDrawable().apply {
                    setColor(Color.parseColor("#2A9D8F"))
                    cornerRadius = radius
                }
                tab.setTextColor(Color.WHITE)
            } else {
                tab.background = null
                tab.setTextColor(Color.parseColor("#475569"))
            }
        }
    }

    private fun tampilkanMenuPeriode(anchor: View) {
        val popup = PopupMenu(requireContext(), anchor)
        popup.menu.add(0, 1, 0, "Harian")
        popup.menu.add(0, 2, 1, "Mingguan")
        popup.menu.add(0, 3, 2, "Bulanan")
        popup.menu.add(0, 4, 3, "Keseluruhan")
        popup.setOnMenuItemClickListener { item ->
            val pilihan = when (item.itemId) {
                2 -> "mingguan"
                3 -> "bulanan"
                4 -> "keseluruhan"
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
        "keseluruhan" -> "Keseluruhan"
        else -> "Harian"
    }

    private fun labelWaktuPeriode(periode: String): String = when (periode) {
        "mingguan" -> "minggu ini"
        "bulanan" -> "bulan ini"
        "keseluruhan" -> "keseluruhan"
        else -> "hari ini"
    }

    private fun tampilkanDaftarPeringatan(judul: String, daftar: List<String>, pesanKosong: String) {
        val pesan = if (daftar.isEmpty()) pesanKosong else daftar.joinToString("\n")
        AlertDialog.Builder(requireContext())
            .setTitle(judul)
            .setMessage(pesan)
            .setPositiveButton("Lihat Stok") { _, _ ->
                val bottomNav = requireActivity().findViewById<com.google.android.material.bottomnavigation.BottomNavigationView>(R.id.bottom_navigation)
                bottomNav.selectedItemId = R.id.nav_stok
            }
            .setNegativeButton("Tutup", null)
            .show()
    }

    private fun perbaruiKartuRingkasan(
        card: androidx.cardview.widget.CardView,
        tvLabel: TextView,
        tvJumlah: TextView,
        tvSub: TextView,
        jumlah: Int,
        subTeks: String,
        bgHex: String,
        labelHex: String,
        angkaHex: String
    ) {
        card.setCardBackgroundColor(Color.parseColor(bgHex))
        tvLabel.setTextColor(Color.parseColor(labelHex))
        tvJumlah.setTextColor(Color.parseColor(angkaHex))
        tvSub.setTextColor(Color.parseColor(labelHex))
        tvJumlah.text = jumlah.toString()
        tvSub.text = subTeks
    }

    private fun loadDashboardData() {
        val url = "${ApiConfig.URL_DASHBOARD}?periode=$periodeAktif&periode_grafik=$periodeGrafik"
        val request = ApiRequest(requireContext(), Request.Method.GET, url, null,
            { response ->
                if (!isAdded || _binding == null) return@ApiRequest

                if (response.optBoolean("success", false)) {
                    val data = response.getJSONObject("data")

                    // 1. Ringkasan Pendapatan
                    val periodeRespon = data.optString("periode", periodeAktif)
                    val totalPenjualan = data.optLong("total_penjualan", 0L)
                    val jumlahTransaksi = data.optInt("jumlah_transaksi", 0)

                    binding.tvFilterPeriode.text = labelChipPeriode(periodeRespon)
                    binding.tvTotalPenjualan.text = "Rp ${formatRupiah.format(totalPenjualan)}"
                    binding.tvJumlahTransaksi.text = "$jumlahTransaksi transaksi ${labelWaktuPeriode(periodeRespon)}"

                    // 2. Kotak Ringkasan Stok Menipis (< 10)
                    val peringatanObatArray = data.optJSONArray("peringatan_obat")
                    daftarPeringatanStok.clear()
                    if (peringatanObatArray != null && peringatanObatArray.length() > 0) {
                        for (i in 0 until peringatanObatArray.length()) {
                            val o = peringatanObatArray.getJSONObject(i)
                            daftarPeringatanStok.add("• ${o.optString("nama_obat")} (stok: ${o.optInt("stok", 0)})")
                        }
                        perbaruiKartuRingkasan(
                            binding.cardStokMenipis, binding.tvLabelStokMenipis,
                            binding.tvJumlahStokMenipis, binding.tvSubStokMenipis,
                            daftarPeringatanStok.size, "Stok < 10 • Tap lihat",
                            "#FEF3C7", "#92400E", "#B45309"
                        )
                    } else {
                        perbaruiKartuRingkasan(
                            binding.cardStokMenipis, binding.tvLabelStokMenipis,
                            binding.tvJumlahStokMenipis, binding.tvSubStokMenipis,
                            0, "Stok aman",
                            "#F0FDF4", "#166534", "#15803D"
                        )
                    }

                    // 3. Kotak Ringkasan Mau Kadaluarsa
                    val peringatanKadaluarsaArray = data.optJSONArray("peringatan_kadaluarsa")
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
                        val subTeks = if (jumlahKadaluarsa > 0) {
                            "$jumlahKadaluarsa lewat • $jumlahHampir < 30 hr"
                        } else {
                            "<= 30 hari • Tap lihat"
                        }
                        perbaruiKartuRingkasan(
                            binding.cardKadaluarsa, binding.tvLabelKadaluarsa,
                            binding.tvJumlahKadaluarsa, binding.tvSubKadaluarsa,
                            daftarPeringatanKadaluarsa.size, subTeks,
                            "#FEE2E2", "#991B1B", "#DC2626"
                        )
                    } else {
                        perbaruiKartuRingkasan(
                            binding.cardKadaluarsa, binding.tvLabelKadaluarsa,
                            binding.tvJumlahKadaluarsa, binding.tvSubKadaluarsa,
                            0, "Tanggal aman",
                            "#F0FDF4", "#166534", "#15803D"
                        )
                    }

                    // 4. Grafik Penjualan
                    val grafikArray = data.optJSONArray("grafik_penjualan")
                    val titikList = mutableListOf<TitikGrafik>()
                    if (grafikArray != null) {
                        for (i in 0 until grafikArray.length()) {
                            val g = grafikArray.getJSONObject(i)
                            titikList.add(
                                TitikGrafik(
                                    label = g.optString("label", ""),
                                    keterangan = g.optString("keterangan", ""),
                                    total = g.optLong("total", 0L),
                                    jumlahTransaksi = g.optInt("jumlah_transaksi", 0)
                                )
                            )
                        }
                    }
                    binding.grafikPenjualan.setData(titikList)
                    if (titikList.none { it.total > 0L }) {
                        binding.tvInfoGrafik.text = "Belum ada penjualan pada rentang ${labelChipPeriode(periodeGrafik).lowercase(Locale.ROOT)}"
                    }

                    // 5. Muat 5 Transaksi Terbaru
                    val transaksiTerbaruArray = data.optJSONArray("transaksi_terbaru")
                    binding.layoutListTransaksi.removeAllViews()
                    if (transaksiTerbaruArray != null && transaksiTerbaruArray.length() > 0) {
                        for (i in 0 until transaksiTerbaruArray.length()) {
                            val trx = transaksiTerbaruArray.getJSONObject(i)
                            val itemView = layoutInflater.inflate(R.layout.item_transaksi_terbaru, binding.layoutListTransaksi, false)
                            val tvKode = itemView.findViewById<TextView>(R.id.tv_kode_trx)
                            val tvWaktuKasir = itemView.findViewById<TextView>(R.id.tv_waktu_kasir)
                            val tvTotal = itemView.findViewById<TextView>(R.id.tv_total_harga)

                            tvKode.text = trx.getString("no_transaksi")
                            val totalTrx = trx.optLong("total_harga", 0L)
                            tvTotal.text = "Rp ${formatRupiah.format(totalTrx)}"

                            val rawDate = trx.getString("created_at")
                            val jamTrx = ApiConfig.formatWaktuIso(rawDate, "HH:mm")
                            val userObj = trx.optJSONObject("user")
                            val namaKasir = userObj?.optString("nama", "Kasir") ?: "Kasir"
                            tvWaktuKasir.text = "$jamTrx / $namaKasir"

                            binding.layoutListTransaksi.addView(itemView)
                        }
                    } else {
                        val emptyText = TextView(requireContext()).apply {
                            text = "Belum ada transaksi"
                            setPadding(0, 30, 0, 30)
                            gravity = android.view.Gravity.CENTER
                        }
                        binding.layoutListTransaksi.addView(emptyText)
                    }
                }
                binding.swipeRefreshLayout.isRefreshing = false
            },
            { error ->
                if (!isAdded || _binding == null) return@ApiRequest

                binding.swipeRefreshLayout.isRefreshing = false
                if (!ApiConfig.tanganiAuthError(context, error)) {
                    Toast.makeText(context, ApiConfig.pesanError(error), Toast.LENGTH_SHORT).show()
                }
            }
        )
        Volley.newRequestQueue(requireContext()).add(request)
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
