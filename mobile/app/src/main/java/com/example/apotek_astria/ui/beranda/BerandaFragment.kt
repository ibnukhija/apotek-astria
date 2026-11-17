package com.example.apotek_astria.ui.beranda

import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.os.Bundle
import android.view.Gravity
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.LinearLayout
import android.widget.PopupMenu
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.fragment.app.Fragment
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.MainActivity
import com.example.apotek_astria.R
import com.example.apotek_astria.databinding.FragmentBerandaBinding
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import com.google.android.material.bottomnavigation.BottomNavigationView
import org.json.JSONArray
import java.text.NumberFormat
import java.util.Locale

class BerandaFragment : Fragment() {

    private var _binding: FragmentBerandaBinding? = null
    private val binding get() = _binding!!

    private var periodeAktif = "harian"
    private var periodeGrafik = "harian"
    private val daftarPeringatanStok = mutableListOf<String>()
    private val daftarNamaStokMenipis = mutableListOf<String>()
    private val daftarPeringatanKadaluarsa = mutableListOf<String>()
    private val daftarPeringatanRusak = mutableListOf<String>()
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

        // Pilih periode kartu pendapatan (Harian / Mingguan / Bulanan / Keseluruhan)
        binding.tvFilterPeriode.setOnClickListener {
            tampilkanMenuPeriode(it)
        }

        // Tautan langsung dari Penjualan ke Laporan dan dari Stok Obat ke halaman Stok
        binding.tvLihatLaporan.setOnClickListener {
            val mainActivity = activity as? MainActivity
            if (mainActivity != null) {
                mainActivity.bukaLaporan()
            } else {
                bukaTabNavigasi(R.id.nav_riwayat)
            }
        }
        binding.tvLihatStok.setOnClickListener {
            bukaTabNavigasi(R.id.nav_stok)
        }
        binding.tvStatusTokoBeranda.setOnClickListener {
            bukaTabNavigasi(R.id.nav_profil)
        }

        // Banner pemberitahuan stok menipis & 2 kotak ringkasan stok
        val bukaDialogStokMenipis: (View) -> Unit = {
            if (daftarPeringatanStok.isEmpty()) {
                bukaTabNavigasi(R.id.nav_stok)
            } else {
                tampilkanDaftarPeringatan(
                    "Obat Stok Menipis (Stok < 10)",
                    daftarPeringatanStok,
                    "Semua stok obat dalam batas aman (>= 10)."
                )
            }
        }
        binding.cardBannerStok.setOnClickListener(bukaDialogStokMenipis)
        binding.cardStokMenipis.setOnClickListener {
            tampilkanDaftarPeringatan(
                "Obat Stok Menipis (Stok < 10)",
                daftarPeringatanStok,
                "Semua stok obat dalam batas aman (>= 10)."
            )
        }
        binding.cardKadaluarsa.setOnClickListener {
            tampilkanDaftarPeringatan(
                "Obat Kadaluarsa / Mendekati Expired (<= 7 Hari)",
                daftarPeringatanKadaluarsa,
                "Tidak ada obat yang kadaluarsa atau mendekati expired (<= 7 hari)."
            )
        }
        binding.cardObatRusak.setOnClickListener {
            tampilkanDaftarPeringatan(
                "Catatan Obat Rusak & Penyebab",
                daftarPeringatanRusak,
                "Belum ada catatan obat rusak."
            )
        }

        // 4 Tab periode grafik penjualan (Harian, Mingguan, Bulanan, Tahunan)
        binding.tvGrafikHarian.setOnClickListener { pilihPeriodeGrafik("harian") }
        binding.tvGrafikMingguan.setOnClickListener { pilihPeriodeGrafik("mingguan") }
        binding.tvGrafikBulanan.setOnClickListener { pilihPeriodeGrafik("bulanan") }
        binding.tvGrafikTahunan.setOnClickListener { pilihPeriodeGrafik("tahunan") }
        perbaruiTampilanTabGrafik()

        binding.grafikPenjualan.onBarSelected = { titik ->
            if (_binding != null) {
                binding.tvInfoGrafik.text =
                    "${titik.keterangan}: Rp ${formatRupiah.format(titik.total)} (${titik.jumlahTransaksi} transaksi)"
            }
        }

        binding.swipeRefreshLayout.isRefreshing = true
        loadDashboardData()
    }

    private fun bukaTabNavigasi(itemId: Int) {
        val bottomNav = requireActivity().findViewById<BottomNavigationView>(R.id.bottom_navigation)
        bottomNav.selectedItemId = itemId
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
            binding.tvGrafikBulanan to "bulanan",
            binding.tvGrafikTahunan to "tahunan"
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
        "tahunan" -> "Tahunan"
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
                bukaTabNavigasi(R.id.nav_stok)
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

    private fun perbaruiBannerStok(namaMenipis: List<String>) {
        val ikonBg = GradientDrawable().apply {
            shape = GradientDrawable.OVAL
        }
        if (namaMenipis.isNotEmpty()) {
            binding.cardBannerStok.setCardBackgroundColor(Color.parseColor("#FEF3C7"))
            ikonBg.setColor(Color.parseColor("#D97706"))
            binding.tvBannerStokIkon.background = ikonBg
            binding.tvBannerStokIkon.text = "!"
            binding.tvBannerStokJudul.setTextColor(Color.parseColor("#92400E"))
            binding.tvBannerStokJudul.text = "Peringatan: ${namaMenipis.size} obat stok menipis (< 10)"
            val pratinjau = namaMenipis.take(2).joinToString(", ")
            val sisa = namaMenipis.size - 2
            val tambahan = if (sisa > 0) ", +$sisa lainnya" else ""
            binding.tvBannerStokIsi.setTextColor(Color.parseColor("#78350F"))
            binding.tvBannerStokIsi.text = "$pratinjau$tambahan • Tap lihat"
        } else {
            binding.cardBannerStok.setCardBackgroundColor(Color.parseColor("#F0FDF4"))
            ikonBg.setColor(Color.parseColor("#16A34A"))
            binding.tvBannerStokIkon.background = ikonBg
            binding.tvBannerStokIkon.text = "✓"
            binding.tvBannerStokJudul.setTextColor(Color.parseColor("#166534"))
            binding.tvBannerStokJudul.text = "Semua stok obat aman (>= 10)"
            binding.tvBannerStokIsi.setTextColor(Color.parseColor("#15803D"))
            binding.tvBannerStokIsi.text = "Tidak ada obat yang perlu segera di-restock"
        }
    }

    private fun tampilkanObatTerlaris(arrayTerlaris: JSONArray?) {
        binding.tvPeriodeTerlaris.text = "Keseluruhan"
        binding.layoutObatTerlaris.removeAllViews()

        if (arrayTerlaris == null || arrayTerlaris.length() == 0) {
            val emptyText = TextView(requireContext()).apply {
                text = "Belum ada data penjualan obat"
                setTextColor(Color.parseColor("#94A3B8"))
                textSize = 12f
                setPadding(0, 24, 0, 12)
                gravity = Gravity.CENTER
            }
            binding.layoutObatTerlaris.addView(emptyText)
            return
        }

        val density = resources.displayMetrics.density
        val maxItems = minOf(arrayTerlaris.length(), 5)
        for (i in 0 until maxItems) {
            val obj = arrayTerlaris.getJSONObject(i)
            val itemView = layoutInflater.inflate(R.layout.item_obat_terlaris, binding.layoutObatTerlaris, false)

            val tvPeringkat = itemView.findViewById<TextView>(R.id.tv_peringkat_terlaris)
            val tvNama = itemView.findViewById<TextView>(R.id.tv_nama_obat_terlaris)
            val tvQty = itemView.findViewById<TextView>(R.id.tv_qty_terlaris)

            val peringkat = i + 1
            tvPeringkat.text = peringkat.toString()
            tvPeringkat.background = GradientDrawable().apply {
                shape = GradientDrawable.OVAL
                setColor(Color.parseColor(if (peringkat <= 3) "#E8F4F2" else "#F1F5F9"))
            }
            tvPeringkat.setTextColor(Color.parseColor(if (peringkat <= 3) "#1F7A6F" else "#64748B"))

            tvNama.text = obj.optString("nama_obat", "-")
            tvQty.text = "${obj.optInt("total_terjual", 0)} terjual"

            binding.layoutObatTerlaris.addView(itemView)

            if (i < maxItems - 1) {
                val divider = View(requireContext()).apply {
                    layoutParams = LinearLayout.LayoutParams(
                        LinearLayout.LayoutParams.MATCH_PARENT,
                        maxOf(1, density.toInt())
                    )
                    setBackgroundColor(Color.parseColor("#F1F5F9"))
                }
                binding.layoutObatTerlaris.addView(divider)
            }
        }
    }

    private fun loadDashboardData() {
        val url = "${ApiConfig.URL_DASHBOARD}?periode=$periodeAktif&periode_grafik=$periodeGrafik"
        val request = ApiRequest(requireContext(), Request.Method.GET, url, null,
            { response ->
                if (!isAdded || _binding == null) return@ApiRequest

                if (response.optBoolean("success", false)) {
                    val data = response.getJSONObject("data")

                    // 0. Header: Nama & Status Toko (Buka / Tutup)
                    val profilObj = data.optJSONObject("profil_apotek")
                    if (profilObj != null) {
                        binding.tvNamaApotekHeader.text = profilObj.optString("nama_apotek", "Apotek Astria 2 Farma")
                        val isBuka = profilObj.optBoolean("is_buka", true)
                        val tutupPaksa = profilObj.optBoolean("tutup_paksa", false)
                        val keteranganStatus = profilObj.optString("keterangan_status", "")
                        val jadwal = profilObj.optString("jadwal_ringkas", "Setiap hari • 07:30 - 21:00")

                        binding.tvJadwalBeranda.text = if (tutupPaksa && keteranganStatus.isNotBlank()) {
                            keteranganStatus
                        } else {
                            jadwal
                        }
                        binding.tvStatusTokoBeranda.text = if (isBuka) "BUKA" else "TUTUP"
                        binding.tvStatusTokoBeranda.setTextColor(
                            Color.parseColor(if (isBuka) "#166534" else "#991B1B")
                        )
                        binding.tvStatusTokoBeranda.background = GradientDrawable().apply {
                            setColor(Color.parseColor(if (isBuka) "#DCFCE7" else "#FEE2E2"))
                            cornerRadius = 14f * resources.displayMetrics.density
                        }
                    }

                    // 1. Bagian Penjualan: Ringkasan Total
                    val periodeRespon = data.optString("periode", periodeAktif)
                    val totalPenjualan = data.optLong("total_penjualan", 0L)
                    val jumlahTransaksi = data.optInt("jumlah_transaksi", 0)

                    binding.tvFilterPeriode.text = labelChipPeriode(periodeRespon)
                    binding.tvTotalPenjualan.text = "Rp ${formatRupiah.format(totalPenjualan)}"
                    binding.tvJumlahTransaksi.text = "$jumlahTransaksi transaksi ${labelWaktuPeriode(periodeRespon)}"

                    // 2. Bagian Penjualan: Grafik Analisis Penjualan (4 periode)
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

                    // 3. Bagian Penjualan: Top 5 Obat Paling Laku (Keseluruhan)
                    tampilkanObatTerlaris(data.optJSONArray("obat_terlaris"))

                    // 4. Bagian Stok: Banner & Ringkasan Stok Menipis (< 10)
                    val peringatanObatArray = data.optJSONArray("peringatan_obat")
                    daftarPeringatanStok.clear()
                    daftarNamaStokMenipis.clear()
                    if (peringatanObatArray != null && peringatanObatArray.length() > 0) {
                        for (i in 0 until peringatanObatArray.length()) {
                            val o = peringatanObatArray.getJSONObject(i)
                            val namaObat = o.optString("nama_obat", "-")
                            val stokObat = o.optInt("stok", 0)
                            daftarPeringatanStok.add("• $namaObat (stok: $stokObat)")
                            daftarNamaStokMenipis.add("$namaObat ($stokObat)")
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
                    perbaruiBannerStok(daftarNamaStokMenipis)

                    // 5. Bagian Stok: Ringkasan Mau Kadaluarsa (<= 7 hari) & Obat Rusak
                    val peringatanKadaluarsaArray = data.optJSONArray("peringatan_kadaluarsa")
                    daftarPeringatanKadaluarsa.clear()
                    if (peringatanKadaluarsaArray != null && peringatanKadaluarsaArray.length() > 0) {
                        var jumlahKadaluarsa = 0
                        var jumlahHampir = 0
                        for (i in 0 until peringatanKadaluarsaArray.length()) {
                            val o = peringatanKadaluarsaArray.getJSONObject(i)
                            val statusKad = o.optString("status_kadaluarsa", "")
                            val saran = o.optString("saran_tindakan", "")
                            val tglExp = ApiConfig.formatTanggal(
                                if (o.isNull("tgl_kadaluarsa")) "" else o.optString("tgl_kadaluarsa", "")
                            )
                            if (statusKad.equals("Kadaluarsa", ignoreCase = true)) {
                                jumlahKadaluarsa++
                            } else {
                                jumlahHampir++
                            }
                            val teksSaran = if (saran.isNotBlank()) " → $saran" else ""
                            daftarPeringatanKadaluarsa.add("• ${o.optString("nama_obat")} ($tglExp • $statusKad$teksSaran)")
                        }
                        val subTeks = if (jumlahKadaluarsa > 0) {
                            "$jumlahKadaluarsa exp • $jumlahHampir H-7"
                        } else {
                            "<= 7 hr • Retur"
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

                    // 6. Bagian Stok: Ringkasan Obat Rusak
                    val peringatanRusakArray = data.optJSONArray("peringatan_rusak")
                    val ringkasanRusak = data.optJSONObject("ringkasan_rusak")
                    val totalUnitRusak = ringkasanRusak?.optInt("total_unit", 0) ?: 0
                    daftarPeringatanRusak.clear()
                    if (peringatanRusakArray != null && peringatanRusakArray.length() > 0) {
                        for (i in 0 until peringatanRusakArray.length()) {
                            val r = peringatanRusakArray.getJSONObject(i)
                            val namaObat = r.optString("nama_obat", "-")
                            val jml = r.optInt("jumlah", 0)
                            val penyebab = r.optString("penyebab", "-")
                            val tgl = r.optString("tanggal", "-")
                            daftarPeringatanRusak.add("• $namaObat ($jml unit) — $penyebab [$tgl]")
                        }
                        perbaruiKartuRingkasan(
                            binding.cardObatRusak, binding.tvLabelObatRusak,
                            binding.tvJumlahObatRusak, binding.tvSubObatRusak,
                            totalUnitRusak, "${daftarPeringatanRusak.size} kasus • Tap",
                            "#FFEDD5", "#9A3412", "#EA580C"
                        )
                    } else {
                        perbaruiKartuRingkasan(
                            binding.cardObatRusak, binding.tvLabelObatRusak,
                            binding.tvJumlahObatRusak, binding.tvSubObatRusak,
                            0, "Tidak ada rusak",
                            "#F0FDF4", "#166534", "#15803D"
                        )
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
