package com.example.apotek_astria.ui.riwayat

import android.app.AlertDialog
import android.app.DatePickerDialog
import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.view.inputmethod.EditorInfo
import android.widget.AdapterView
import android.widget.ArrayAdapter
import android.widget.LinearLayout
import android.widget.RadioButton
import android.widget.TextView
import android.widget.Toast
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.R
import com.example.apotek_astria.databinding.FragmentRiwayatBinding
import com.example.apotek_astria.model.DetailObat
import com.example.apotek_astria.model.LaporanItemModel
import com.example.apotek_astria.model.TransaksiModel
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONArray
import java.text.NumberFormat
import java.text.SimpleDateFormat
import java.util.Calendar
import java.util.Locale

class RiwayatFragment : Fragment() {

    companion object {
        const val ARG_BUKA_LAPORAN = "buka_laporan"
    }

    private var _binding: FragmentRiwayatBinding? = null
    private val binding get() = _binding!!

    private val formatRupiah by lazy { NumberFormat.getNumberInstance(Locale.forLanguageTag("id-ID")) }
    private val sdfApi by lazy { SimpleDateFormat("yyyy-MM-dd", Locale.US) }

    // State Sub-tab
    private var tabAktif = "riwayat"
    private var riwayatSudahDimuat = false
    private var laporanSudahDimuat = false

    // State Riwayat
    private val transaksiList = mutableListOf<TransaksiModel>()
    private lateinit var adapter: RiwayatAdapter
    private var currentPage = 1
    private var lastPage = 1

    // State Laporan
    private val daftarKategori = mutableListOf("Semua Kategori")
    private val daftarSaranObat = mutableListOf<String>()
    private lateinit var kategoriAdapter: ArrayAdapter<String>
    private lateinit var saranObatAdapter: ArrayAdapter<String>
    private var periodeLaporan = "hari_ini"
    private var kategoriLaporan = ""
    private var lapCurrentPage = 1
    private var lapLastPage = 1
    private var sedangAturSpinner = false
    private var urlSupabaseTerakhir = ""
    private var filterLaporanTerbuka = false
    private var modeTampilLaporan = "rincian" // "rincian" | "produk_terlaris" | "kategori_terlaris"
    private var lapItemsCache: List<LaporanItemModel> = emptyList()
    private var lapTotalItemsCache = 0
    private var lapProdukTerlarisCache: JSONArray? = null
    private var lapKategoriTerlarisCache: JSONArray? = null

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

        setupPanelRiwayat()
        setupPanelLaporan()

        binding.tabRiwayat.setOnClickListener { pilihSubTab("riwayat") }
        binding.tabLaporan.setOnClickListener { pilihSubTab("laporan") }

        val langsungLaporan = arguments?.getBoolean(ARG_BUKA_LAPORAN, false) == true
        arguments?.remove(ARG_BUKA_LAPORAN)
        pilihSubTab(if (langsungLaporan) "laporan" else "riwayat")
    }

    private fun pilihSubTab(tabBaru: String) {
        tabAktif = tabBaru
        val isLaporan = tabBaru == "laporan"

        binding.layoutPanelRiwayat.visibility = if (isLaporan) View.GONE else View.VISIBLE
        binding.layoutPanelLaporan.visibility = if (isLaporan) View.VISIBLE else View.GONE

        val radius = 8f * resources.displayMetrics.density
        val tabs = listOf(binding.tabRiwayat to !isLaporan, binding.tabLaporan to isLaporan)
        for ((tab, aktif) in tabs) {
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

        if (isLaporan && !laporanSudahDimuat) {
            laporanSudahDimuat = true
            loadLaporan()
        } else if (!isLaporan && !riwayatSudahDimuat) {
            riwayatSudahDimuat = true
            muatDataSesuaiFilter()
        }
    }

    // ==================== BAGIAN RIWAYAT ====================
    private fun setupPanelRiwayat() {
        adapter = RiwayatAdapter(requireContext(), transaksiList)
        binding.lvRiwayat.adapter = adapter

        pasangDatePicker(binding.etTanggalMulai)
        pasangDatePicker(binding.etTanggalSelesai)

        binding.btnFilter.setOnClickListener {
            val tglMulai = binding.etTanggalMulai.text.toString().trim()
            val tglSelesai = binding.etTanggalSelesai.text.toString().trim()
            if (tglMulai.isNotEmpty() || tglSelesai.isNotEmpty()) {
                currentPage = 1
                muatDataSesuaiFilter()
            } else {
                Toast.makeText(requireContext(), "Pilih tanggal mulai atau tanggal selesai", Toast.LENGTH_SHORT).show()
            }
        }

        binding.btnReset.setOnClickListener {
            binding.etTanggalMulai.setText("")
            binding.etTanggalSelesai.setText("")
            currentPage = 1
            muatDataSesuaiFilter()
        }

        binding.lvRiwayat.setOnItemClickListener { _, _, position, _ ->
            tampilkanDetailTransaksi(transaksiList[position])
        }

        binding.swipeRefreshLayout.setOnRefreshListener {
            currentPage = 1
            muatDataSesuaiFilter()
        }

        binding.btnPrevPage.setOnClickListener {
            if (currentPage > 1) {
                currentPage--
                muatDataSesuaiFilter()
            }
        }

        binding.btnNextPage.setOnClickListener {
            if (currentPage < lastPage) {
                currentPage++
                muatDataSesuaiFilter()
            }
        }
    }

    private fun muatDataSesuaiFilter() {
        val tglMulai = binding.etTanggalMulai.text.toString().trim()
        val tglSelesai = binding.etTanggalSelesai.text.toString().trim()

        val builder = Uri.parse(ApiConfig.URL_RIWAYAT).buildUpon()
            .appendQueryParameter("page", currentPage.toString())
        if (tglMulai.isNotEmpty()) {
            builder.appendQueryParameter("tanggal_mulai", tglMulai)
        }
        if (tglSelesai.isNotEmpty()) {
            builder.appendQueryParameter("tanggal_selesai", tglSelesai)
        }
        loadRiwayatTransaksi(builder.build().toString())
    }

    private fun loadRiwayatTransaksi(url: String) {
        binding.swipeRefreshLayout.isRefreshing = true
        binding.tvEmpty.visibility = View.GONE

        val request = ApiRequest(
            requireContext(),
            Request.Method.GET, url, null,
            { response ->
                if (!isAdded || _binding == null) return@ApiRequest
                binding.swipeRefreshLayout.isRefreshing = false

                if (response.optBoolean("success", false)) {
                    val dataArray = response.optJSONArray("data")
                    currentPage = response.optInt("current_page", 1)
                    lastPage = response.optInt("last_page", 1)
                    val totalTrx = response.optInt("total", 0)
                    val totalOmset = response.optLong("total_omset", 0L)

                    val tglMulai = binding.etTanggalMulai.text.toString().trim()
                    val tglSelesai = binding.etTanggalSelesai.text.toString().trim()
                    val labelRentang = when {
                        tglMulai.isNotEmpty() && tglMulai == tglSelesai -> ApiConfig.formatTanggal(tglMulai)
                        tglMulai.isNotEmpty() || tglSelesai.isNotEmpty() -> "Terfilter"
                        else -> "Semua"
                    }
                    binding.tvRiwayatJumlahTransaksi.text = "$totalTrx transaksi ($labelRentang)"
                    binding.tvRiwayatTotalOmset.text = "Omzet: Rp ${formatRupiah.format(totalOmset)}"

                    binding.tvPageInfo.text = "Halaman $currentPage dari $lastPage"
                    binding.layoutPagination.visibility = if (lastPage > 1) View.VISIBLE else View.GONE
                    binding.btnPrevPage.isEnabled = currentPage > 1
                    binding.btnNextPage.isEnabled = currentPage < lastPage

                    viewLifecycleOwner.lifecycleScope.launch(Dispatchers.Default) {
                        val temporaryList = mutableListOf<TransaksiModel>()
                        if (dataArray != null && dataArray.length() > 0) {
                            for (i in 0 until dataArray.length()) {
                                val obj = dataArray.getJSONObject(i)
                                val userObj = obj.optJSONObject("user")
                                val kasir = userObj?.optString("nama") ?: "Kasir"
                                val metode = obj.optString("metode_bayar", "cash").ifBlank { "cash" }
                                val metodeLabel = obj.optString(
                                    "metode_bayar_label",
                                    if (metode.equals("qris", ignoreCase = true)) "QRIS" else "Cash"
                                )

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

                                val formatWaktu = ApiConfig.formatWaktuIso(obj.optString("created_at"), "dd/MM/yyyy HH:mm")
                                temporaryList.add(
                                    TransaksiModel(
                                        id = obj.optInt("id"),
                                        noTransaksi = obj.optString("no_transaksi"),
                                        waktu = formatWaktu,
                                        namaKasir = kasir,
                                        metodeBayar = metode,
                                        metodeBayarLabel = metodeLabel,
                                        totalHarga = obj.optInt("total_harga", 0),
                                        totalBayar = obj.optInt("total_bayar", 0),
                                        kembalian = obj.optInt("kembalian", 0),
                                        details = detailList
                                    )
                                )
                            }
                        }

                        // Update UI
                        withContext(Dispatchers.Main) {
                            if (!isAdded || _binding == null) return@withContext
                            transaksiList.clear()
                            transaksiList.addAll(temporaryList)
                            adapter.notifyDataSetChanged()
                            binding.tvEmpty.visibility = if (transaksiList.isEmpty()) View.VISIBLE else View.GONE
                        }
                    }
                } else {
                    binding.tvRiwayatJumlahTransaksi.text = "0 transaksi"
                    binding.tvRiwayatTotalOmset.text = "Omzet: Rp 0"
                    transaksiList.clear()
                    adapter.notifyDataSetChanged()
                    binding.tvEmpty.visibility = View.VISIBLE
                }
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

    private fun tampilkanDetailTransaksi(trx: TransaksiModel) {
        val sb = StringBuilder()
        sb.append("Waktu   : ${trx.waktu}\n")
        sb.append("Kasir   : ${trx.namaKasir}\n")
        sb.append("Metode  : ${trx.metodeBayarLabel}\n\n")
        sb.append("Daftar Obat Dibeli:\n")
        sb.append("--------------------------------\n")

        for (item in trx.details) {
            sb.append("${item.namaObat}\n")
            sb.append("  ${item.qty} x Rp ${formatRupiah.format(item.hargaSatuan)} = Rp ${formatRupiah.format(item.subtotal)}\n")
        }

        sb.append("--------------------------------\n")
        sb.append("Total Belanja : Rp ${formatRupiah.format(trx.totalHarga)}\n")
        sb.append("Bayar (${trx.metodeBayarLabel}) : Rp ${formatRupiah.format(trx.totalBayar)}\n")
        if (!trx.metodeBayar.equals("qris", ignoreCase = true)) {
            sb.append("Kembalian     : Rp ${formatRupiah.format(trx.kembalian)}\n")
        }

        AlertDialog.Builder(requireContext())
            .setTitle(trx.noTransaksi)
            .setMessage(sb.toString())
            .setPositiveButton("Tutup", null)
            .show()
    }

    // ==================== BAGIAN LAPORAN ====================
    private fun semuaRadioPeriode(): List<Pair<RadioButton, String>> = listOf(
        binding.rbPeriodeHariIni to "hari_ini",
        binding.rbPeriodeSemua to "semua",
        binding.rbPeriodeKustom to "kustom"
    )

    private fun pilihPeriodeRadio(nilaiBaru: String, muatUlang: Boolean) {
        periodeLaporan = nilaiBaru
        for ((rb, kode) in semuaRadioPeriode()) {
            rb.isChecked = (kode == nilaiBaru)
        }
        if (nilaiBaru != "kustom") {
            isiTanggalSesuaiPeriode(nilaiBaru)
            if (muatUlang) {
                lapCurrentPage = 1
                if (laporanSudahDimuat) loadLaporan()
            }
        } else if (muatUlang) {
            Toast.makeText(requireContext(), "Pilih rentang tanggal lalu tekan Terapkan Filter", Toast.LENGTH_SHORT).show()
        }
    }

    private fun aturPanelFilterLaporan(terbuka: Boolean) {
        filterLaporanTerbuka = terbuka
        binding.layoutIsiFilterLaporan.visibility = if (terbuka) View.VISIBLE else View.GONE
        binding.tvToggleFilterLap.text = if (terbuka) "Tutup ▴" else "Atur Filter ▾"
    }

    private fun perbaruiRingkasanFilterLap() {
        val labelPeriode = when (periodeLaporan) {
            "semua" -> "Semua Periode"
            "kustom" -> {
                val m = binding.etLapTanggalMulai.text.toString().trim()
                val s = binding.etLapTanggalSelesai.text.toString().trim()
                when {
                    m.isNotEmpty() && s.isNotEmpty() -> "${ApiConfig.formatTanggal(m)} - ${ApiConfig.formatTanggal(s)}"
                    m.isNotEmpty() -> "Mulai ${ApiConfig.formatTanggal(m)}"
                    s.isNotEmpty() -> "Sampai ${ApiConfig.formatTanggal(s)}"
                    else -> "Kustom"
                }
            }
            else -> "Hari Ini"
        }
        val bagian = mutableListOf(labelPeriode)
        bagian.add(if (kategoriLaporan.isNotBlank()) kategoriLaporan else "Semua Kategori")
        val obatCari = binding.actvLapNamaObat.text.toString().trim()
        if (obatCari.isNotBlank()) bagian.add("\"$obatCari\"")
        binding.tvRingkasanFilterLap.text = bagian.joinToString(" • ")
    }

    private fun setupPanelLaporan() {
        aturPanelFilterLaporan(false)
        binding.headerFilterLaporan.setOnClickListener {
            aturPanelFilterLaporan(!filterLaporanTerbuka)
        }
        pilihPeriodeRadio("hari_ini", muatUlang = false)
        perbaruiRingkasanFilterLap()
        for ((rb, kode) in semuaRadioPeriode()) {
            rb.setOnClickListener { pilihPeriodeRadio(kode, muatUlang = true) }
        }

        kategoriAdapter = ArrayAdapter(requireContext(), android.R.layout.simple_spinner_item, daftarKategori).apply {
            setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item)
        }
        binding.spKategoriLaporan.adapter = kategoriAdapter

        saranObatAdapter = ArrayAdapter(requireContext(), android.R.layout.simple_dropdown_item_1line, daftarSaranObat)
        binding.actvLapNamaObat.setAdapter(saranObatAdapter)

        binding.spKategoriLaporan.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, view: View?, position: Int, id: Long) {
                if (sedangAturSpinner) return
                kategoriLaporan = if (position == 0) "" else daftarKategori[position]
                lapCurrentPage = 1
                if (laporanSudahDimuat) loadLaporan()
            }
            override fun onNothingSelected(parent: AdapterView<*>?) {}
        }

        val saatTanggalManualDipilih = {
            pilihPeriodeRadio("kustom", muatUlang = false)
        }
        pasangDatePicker(binding.etLapTanggalMulai) { saatTanggalManualDipilih() }
        pasangDatePicker(binding.etLapTanggalSelesai) { saatTanggalManualDipilih() }

        binding.actvLapNamaObat.setOnEditorActionListener { _, actionId, _ ->
            if (actionId == EditorInfo.IME_ACTION_SEARCH || actionId == EditorInfo.IME_ACTION_DONE) {
                aturPanelFilterLaporan(false)
                lapCurrentPage = 1
                loadLaporan()
                true
            } else {
                false
            }
        }

        binding.btnLapTerapkan.setOnClickListener {
            aturPanelFilterLaporan(false)
            lapCurrentPage = 1
            loadLaporan()
        }

        binding.btnLapReset.setOnClickListener {
            sedangAturSpinner = true
            kategoriLaporan = ""
            binding.spKategoriLaporan.setSelection(0)
            binding.actvLapNamaObat.setText("")
            sedangAturSpinner = false
            pilihPeriodeRadio("hari_ini", muatUlang = false)
            perbaruiRingkasanFilterLap()
            lapCurrentPage = 1
            loadLaporan()
        }

        binding.btnBukaSupabase.setOnClickListener {
            LaporanExportHelper.bukaUrlSupabase(requireContext(), urlSupabaseTerakhir)
        }

        binding.btnSalinLinkSupabase.setOnClickListener {
            LaporanExportHelper.salinLinkSupabase(requireContext(), urlSupabaseTerakhir)
        }

        binding.swipeRefreshLaporan.setOnRefreshListener {
            lapCurrentPage = 1
            loadLaporan()
        }

        binding.btnLapPrev.setOnClickListener {
            if (lapCurrentPage > 1) {
                lapCurrentPage--
                loadLaporan()
            }
        }

        binding.btnLapNext.setOnClickListener {
            if (lapCurrentPage < lapLastPage) {
                lapCurrentPage++
                loadLaporan()
            }
        }

        binding.btnUnduhLaporan.setOnClickListener {
            unduhLaporanAktif()
        }

        binding.tvLapTabRincian.setOnClickListener { pilihModeLaporan("rincian") }
        binding.tvLapTabProdukTerlaris.setOnClickListener { pilihModeLaporan("produk_terlaris") }
        binding.tvLapTabKategoriTerlaris.setOnClickListener { pilihModeLaporan("kategori_terlaris") }
        perbaruiTabModeLaporan()
    }

    private fun pilihModeLaporan(modeBaru: String) {
        if (modeBaru == modeTampilLaporan) return
        modeTampilLaporan = modeBaru
        perbaruiTabModeLaporan()
        tampilkanTampilanLaporanAktif()
    }

    private fun perbaruiTabModeLaporan() {
        val tabs = listOf(
            binding.tvLapTabRincian to "rincian",
            binding.tvLapTabProdukTerlaris to "produk_terlaris",
            binding.tvLapTabKategoriTerlaris to "kategori_terlaris"
        )
        val radius = 8f * resources.displayMetrics.density
        for ((tab, mode) in tabs) {
            val aktif = mode == modeTampilLaporan
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

    private fun isiTanggalSesuaiPeriode(periode: String) {
        val cal = Calendar.getInstance()
        val hariIniStr = sdfApi.format(cal.time)
        when (periode) {
            "hari_ini" -> {
                binding.etLapTanggalMulai.setText(hariIniStr)
                binding.etLapTanggalSelesai.setText(hariIniStr)
            }
            "semua" -> {
                binding.etLapTanggalMulai.setText("")
                binding.etLapTanggalSelesai.setText("")
            }
        }
    }

    private fun loadLaporan() {
        perbaruiRingkasanFilterLap()
        val tglMulai = binding.etLapTanggalMulai.text.toString().trim()
        val tglSelesai = binding.etLapTanggalSelesai.text.toString().trim()
        val namaObat = binding.actvLapNamaObat.text.toString().trim()

        val builder = Uri.parse(ApiConfig.URL_LAPORAN).buildUpon()
            .appendQueryParameter("page", lapCurrentPage.toString())
            .appendQueryParameter("periode", periodeLaporan)
        if (tglMulai.isNotEmpty()) builder.appendQueryParameter("tanggal_mulai", tglMulai)
        if (tglSelesai.isNotEmpty()) builder.appendQueryParameter("tanggal_selesai", tglSelesai)
        if (kategoriLaporan.isNotEmpty()) builder.appendQueryParameter("kategori", kategoriLaporan)
        if (namaObat.isNotEmpty()) builder.appendQueryParameter("nama_obat", namaObat)

        binding.swipeRefreshLaporan.isRefreshing = true

        val request = ApiRequest(
            requireContext(),
            Request.Method.GET,
            builder.build().toString(),
            null,
            { response ->
                if (!isAdded || _binding == null) return@ApiRequest
                binding.swipeRefreshLaporan.isRefreshing = false

                if (!response.optBoolean("success", false)) return@ApiRequest

                val filterObj = response.optJSONObject("filter")
                val ringkasanObj = response.optJSONObject("ringkasan")
                val dataArr = response.optJSONArray("data")
                val produkTerlarisArr = response.optJSONArray("produk_terlaris")
                val kategoriTerlarisArr = response.optJSONArray("kategori_terlaris")
                val katArr = response.optJSONArray("kategoris")
                val obatArr = response.optJSONArray("daftar_obat")

                lapCurrentPage = response.optInt("current_page", 1)
                lapLastPage = response.optInt("last_page", 1)
                val totalData = response.optInt("total", 0)
                lapTotalItemsCache = totalData
                lapProdukTerlarisCache = produkTerlarisArr
                lapKategoriTerlarisCache = kategoriTerlarisArr

                binding.tvLapPeriodeLabel.text = filterObj?.optString("periode_label", "Laporan Penjualan") ?: "Laporan Penjualan"
                val totalPendapatan = ringkasanObj?.optLong("total_pendapatan", 0L) ?: 0L
                val totalQty = ringkasanObj?.optInt("total_qty", 0) ?: 0
                val jmlTrx = ringkasanObj?.optInt("jumlah_transaksi", 0) ?: 0
                val totalCash = ringkasanObj?.optLong("total_cash", 0L) ?: 0L
                val totalQris = ringkasanObj?.optLong("total_qris", 0L) ?: 0L

                binding.tvLapTotalPendapatan.text = "Rp ${formatRupiah.format(totalPendapatan)}"
                binding.tvLapRingkasanDetail.text = "$totalQty unit terjual • $jmlTrx transaksi"
                binding.tvLapMetodeRingkas.text =
                    "Cash: Rp ${formatRupiah.format(totalCash)} • QRIS: Rp ${formatRupiah.format(totalQris)}"

                binding.tvLapPageInfo.text = "Hal $lapCurrentPage / $lapLastPage"
                binding.btnLapPrev.isEnabled = lapCurrentPage > 1
                binding.btnLapNext.isEnabled = lapCurrentPage < lapLastPage

                // Sinkronkan opsi kategori & saran nama obat
                if (katArr != null) {
                    val opsiBaru = mutableListOf("Semua Kategori")
                    for (i in 0 until katArr.length()) {
                        val k = katArr.optString(i)
                        if (k.isNotBlank()) opsiBaru.add(k)
                    }
                    if (opsiBaru != daftarKategori) {
                        sedangAturSpinner = true
                        daftarKategori.clear()
                        daftarKategori.addAll(opsiBaru)
                        kategoriAdapter.notifyDataSetChanged()
                        val idx = if (kategoriLaporan.isEmpty()) 0 else daftarKategori.indexOf(kategoriLaporan).coerceAtLeast(0)
                        binding.spKategoriLaporan.setSelection(idx)
                        sedangAturSpinner = false
                    }
                }
                if (obatArr != null) {
                    val baru = mutableListOf<String>()
                    for (i in 0 until obatArr.length()) {
                        val n = obatArr.optString(i)
                        if (n.isNotBlank()) baru.add(n)
                    }
                    if (baru != daftarSaranObat) {
                        daftarSaranObat.clear()
                        daftarSaranObat.addAll(baru)
                        saranObatAdapter.notifyDataSetChanged()
                    }
                }

                viewLifecycleOwner.lifecycleScope.launch(Dispatchers.Default) {
                    val items = mutableListOf<LaporanItemModel>()
                    if (dataArr != null) {
                        for (i in 0 until dataArr.length()) {
                            val o = dataArr.getJSONObject(i)
                            items.add(
                                LaporanItemModel(
                                    detailId = o.optInt("detail_id"),
                                    noTransaksi = o.optString("no_transaksi", "-"),
                                    tanggal = o.optString("tanggal", "-"),
                                    namaKasir = o.optString("nama_kasir", "Kasir"),
                                    metodeBayar = o.optString("metode_bayar", "cash"),
                                    metodeBayarLabel = o.optString("metode_bayar_label", "Cash"),
                                    namaKategori = o.optString("nama_kategori", "-"),
                                    namaObat = o.optString("nama_obat", "-"),
                                    qty = o.optInt("qty", 0),
                                    hargaSatuan = o.optInt("harga_satuan", 0),
                                    subtotal = o.optInt("subtotal", 0)
                                )
                            )
                        }
                    }

                    withContext(Dispatchers.Main) {
                        if (!isAdded || _binding == null) return@withContext
                        lapItemsCache = items
                        tampilkanTampilanLaporanAktif()
                    }
                }
            },
            { error ->
                if (!isAdded || _binding == null) return@ApiRequest
                binding.swipeRefreshLaporan.isRefreshing = false
                if (!ApiConfig.tanganiAuthError(context, error)) {
                    Toast.makeText(context, ApiConfig.pesanError(error), Toast.LENGTH_SHORT).show()
                }
            }
        )
        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun tampilkanTampilanLaporanAktif() {
        when (modeTampilLaporan) {
            "produk_terlaris" -> {
                binding.tvLapJudulDaftar.text = "Produk Terlaris"
                binding.layoutLapPagination.visibility = View.GONE
                tampilkanPeringkatLaporan(lapProdukTerlarisCache, isKategori = false)
            }
            "kategori_terlaris" -> {
                binding.tvLapJudulDaftar.text = "Kategori Terlaris"
                binding.layoutLapPagination.visibility = View.GONE
                tampilkanPeringkatLaporan(lapKategoriTerlarisCache, isKategori = true)
            }
            else -> {
                binding.tvLapJudulDaftar.text = "Rincian Penjualan"
                binding.tvLapJumlahData.text = "$lapTotalItemsCache baris"
                binding.layoutLapPagination.visibility = if (lapLastPage > 1) View.VISIBLE else View.GONE
                tampilkanDaftarLaporan(lapItemsCache)
            }
        }
    }

    private fun tampilkanPeringkatLaporan(arr: JSONArray?, isKategori: Boolean) {
        binding.layoutLapDaftar.removeAllViews()
        val jumlah = arr?.length() ?: 0
        binding.tvLapJumlahData.text = if (isKategori) "$jumlah kategori" else "$jumlah produk"
        binding.tvLapEmpty.text = "Tidak ada data penjualan pada filter ini"
        binding.tvLapEmpty.visibility = if (jumlah == 0) View.VISIBLE else View.GONE
        if (arr == null || jumlah == 0) return

        val density = resources.displayMetrics.density
        for (i in 0 until jumlah) {
            val o = arr.getJSONObject(i)
            val peringkat = i + 1
            val rowView = layoutInflater.inflate(R.layout.item_laporan, binding.layoutLapDaftar, false)

            if (isKategori) {
                val namaKat = o.optString("nama_kategori", "Tanpa Kategori")
                val jmlProduk = o.optInt("jumlah_produk", 0)
                val totalTerjual = o.optInt("total_terjual", 0)
                val totalPendapatan = o.optLong("total_pendapatan", 0L)

                rowView.findViewById<TextView>(R.id.tvLapNamaObat).text = namaKat
                rowView.findViewById<TextView>(R.id.tvLapKategoriNota).text = "$jmlProduk jenis produk terjual"
                rowView.findViewById<TextView>(R.id.tvLapQtyHarga).text = "$totalTerjual unit terjual • Tap lihat produk"
                rowView.findViewById<TextView>(R.id.tvLapSubtotal).text = "Rp ${formatRupiah.format(totalPendapatan)}"

                rowView.setOnClickListener {
                    val idxKat = daftarKategori.indexOf(namaKat)
                    if (idxKat > 0) {
                        sedangAturSpinner = true
                        kategoriLaporan = namaKat
                        binding.spKategoriLaporan.setSelection(idxKat)
                        sedangAturSpinner = false
                        modeTampilLaporan = "produk_terlaris"
                        perbaruiTabModeLaporan()
                        lapCurrentPage = 1
                        loadLaporan()
                    }
                }
            } else {
                val namaObat = o.optString("nama_obat", "-")
                val kodeObat = o.optString("kode_obat", "-")
                val namaKat = o.optString("nama_kategori", "-")
                val satuan = o.optString("satuan", "unit")
                val totalTerjual = o.optInt("total_terjual", 0)
                val totalPendapatan = o.optLong("total_pendapatan", 0L)

                rowView.findViewById<TextView>(R.id.tvLapNamaObat).text = namaObat
                rowView.findViewById<TextView>(R.id.tvLapKategoriNota).text = "$namaKat • $kodeObat"
                rowView.findViewById<TextView>(R.id.tvLapQtyHarga).text = "$totalTerjual $satuan terjual"
                rowView.findViewById<TextView>(R.id.tvLapSubtotal).text = "Rp ${formatRupiah.format(totalPendapatan)}"
            }

            val tvBadge = rowView.findViewById<TextView>(R.id.tvLapMetode)
            tvBadge.text = "#$peringkat"
            tvBadge.setTextColor(Color.parseColor(if (peringkat <= 3) "#1F7A6F" else "#64748B"))
            tvBadge.background = GradientDrawable().apply {
                setColor(Color.parseColor(if (peringkat <= 3) "#E8F4F2" else "#F1F5F9"))
                cornerRadius = 10f * density
            }

            binding.layoutLapDaftar.addView(rowView)
            if (i < jumlah - 1) {
                binding.layoutLapDaftar.addView(buatPemisahLapBaris(density))
            }
        }
    }

    private fun buatPemisahLapBaris(density: Float): View = View(requireContext()).apply {
        layoutParams = LinearLayout.LayoutParams(
            LinearLayout.LayoutParams.MATCH_PARENT,
            maxOf(1, density.toInt())
        )
        setBackgroundColor(Color.parseColor("#F1F5F9"))
    }

    private fun tampilkanDaftarLaporan(items: List<LaporanItemModel>) {
        binding.layoutLapDaftar.removeAllViews()
        binding.tvLapEmpty.visibility = if (items.isEmpty()) View.VISIBLE else View.GONE

        val density = resources.displayMetrics.density
        for ((idx, item) in items.withIndex()) {
            val rowView = layoutInflater.inflate(R.layout.item_laporan, binding.layoutLapDaftar, false)
            rowView.findViewById<TextView>(R.id.tvLapNamaObat).text = item.namaObat
            rowView.findViewById<TextView>(R.id.tvLapKategoriNota).text =
                "${item.namaKategori} • ${item.noTransaksi} • ${item.tanggal}"
            rowView.findViewById<TextView>(R.id.tvLapQtyHarga).text =
                "${item.qty} x Rp ${formatRupiah.format(item.hargaSatuan)}"
            rowView.findViewById<TextView>(R.id.tvLapSubtotal).text =
                "Rp ${formatRupiah.format(item.subtotal)}"

            val tvMetode = rowView.findViewById<TextView>(R.id.tvLapMetode)
            val isQris = item.metodeBayar.equals("qris", ignoreCase = true)
            tvMetode.text = item.metodeBayarLabel
            tvMetode.setTextColor(Color.parseColor(if (isQris) "#1D4ED8" else "#1F7A6F"))
            tvMetode.background = GradientDrawable().apply {
                setColor(Color.parseColor(if (isQris) "#DBEAFE" else "#E8F4F2"))
                cornerRadius = 10f * density
            }

            binding.layoutLapDaftar.addView(rowView)

            if (idx < items.lastIndex) {
                binding.layoutLapDaftar.addView(buatPemisahLapBaris(density))
            }
        }
    }

    private fun unduhLaporanAktif() {
        val format = if (binding.rbFormatExcel.isChecked) "excel" else "pdf"
        val tglMulai = binding.etLapTanggalMulai.text.toString().trim()
        val tglSelesai = binding.etLapTanggalSelesai.text.toString().trim()
        val namaObat = binding.actvLapNamaObat.text.toString().trim()

        val urlEkspor = LaporanExportHelper.buatUrlEkspor(
            format = format,
            periode = periodeLaporan,
            tglMulai = tglMulai,
            tglSelesai = tglSelesai,
            kategori = kategoriLaporan,
            namaObat = namaObat
        )

        val ctx = requireContext().applicationContext
        val labelFormat = if (format == "excel") "Excel (.xlsx)" else "PDF"
        urlSupabaseTerakhir = ""
        binding.layoutAksiSupabase.visibility = View.GONE
        binding.btnUnduhLaporan.isEnabled = false
        binding.btnUnduhLaporan.text = "Mengunduh..."
        binding.layoutProgressUnduh.visibility = View.VISIBLE
        binding.pbUnduhLaporan.visibility = View.VISIBLE
        binding.tvStatusUnduh.setTextColor(Color.parseColor("#1F7A6F"))
        binding.tvStatusUnduh.text = "Membuat laporan $labelFormat & mengunggah ke Supabase Storage..."

        Toast.makeText(requireContext(), "Mengunduh laporan $labelFormat...", Toast.LENGTH_SHORT).show()
        LaporanExportHelper.tampilkanNotifMulai(ctx, format)

        viewLifecycleOwner.lifecycleScope.launch {
            val hasilRes = withContext(Dispatchers.IO) {
                runCatching { LaporanExportHelper.unduhDanSimpan(ctx, urlEkspor, format) }
            }

            hasilRes.onSuccess { hasil ->
                LaporanExportHelper.tampilkanNotifSelesai(ctx, hasil)
            }.onFailure { err ->
                LaporanExportHelper.tampilkanNotifGagal(ctx, err.message ?: "Gagal mengunduh laporan")
            }

            if (!isAdded || _binding == null) return@launch
            binding.btnUnduhLaporan.isEnabled = true
            binding.btnUnduhLaporan.text = "Unduh Laporan"
            binding.pbUnduhLaporan.visibility = View.GONE

            hasilRes.onSuccess { hasil ->
                urlSupabaseTerakhir = hasil.supabaseUrl
                val ketSupabase = if (hasil.supabaseUploaded) {
                    "Supabase Storage: Berhasil diunggah (${hasil.supabasePath})"
                } else {
                    "Supabase Storage: ${hasil.supabaseStatus.ifBlank { "Belum dikonfigurasi di server" }}"
                }

                binding.tvStatusUnduh.setTextColor(Color.parseColor(if (hasil.supabaseUploaded) "#166534" else "#B45309"))
                binding.tvStatusUnduh.text = "Perangkat: ${hasil.lokasiSimpan}\n$ketSupabase"
                binding.layoutAksiSupabase.visibility = if (hasil.supabaseUrl.isNotBlank()) View.VISIBLE else View.GONE

                Toast.makeText(
                    requireContext(),
                    if (hasil.supabaseUploaded) "Tersimpan di perangkat & Supabase Storage"
                    else "Laporan tersimpan ke ${hasil.lokasiSimpan}",
                    Toast.LENGTH_LONG
                ).show()

                val builder = AlertDialog.Builder(requireContext())
                if (hasil.isPdf) {
                    builder.setTitle("Laporan PDF Siap")
                        .setMessage("Perangkat:\n${hasil.lokasiSimpan}\n\n$ketSupabase")
                        .setPositiveButton("Cetak / Simpan PDF") { _, _ ->
                            LaporanExportHelper.cetakPdf(requireContext(), hasil)
                        }
                        .setNeutralButton("Buka PDF") { _, _ ->
                            LaporanExportHelper.bukaFile(requireContext(), hasil)
                        }
                        .setNegativeButton("Tutup", null)
                } else {
                    builder.setTitle("Laporan Excel Siap")
                        .setMessage("Perangkat:\n${hasil.lokasiSimpan}\n\n$ketSupabase")
                        .setPositiveButton("Buka Excel") { _, _ ->
                            LaporanExportHelper.bukaFile(requireContext(), hasil)
                        }
                        .setNeutralButton("Bagikan") { _, _ ->
                            LaporanExportHelper.bagikanFile(requireContext(), hasil)
                        }
                        .setNegativeButton("Tutup", null)
                }
                builder.show()
            }.onFailure { err ->
                binding.layoutAksiSupabase.visibility = View.GONE
                binding.tvStatusUnduh.setTextColor(Color.parseColor("#DC2626"))
                binding.tvStatusUnduh.text = err.message ?: "Gagal mengunduh laporan"
                Toast.makeText(
                    requireContext(),
                    err.message ?: "Gagal mengunduh laporan",
                    Toast.LENGTH_LONG
                ).show()
            }
        }
    }

    private fun pasangDatePicker(target: TextView, setelahPilih: (() -> Unit)? = null) {
        target.setOnClickListener {
            val cal = Calendar.getInstance()
            val teks = target.text.toString().trim()
            if (teks.isNotEmpty()) {
                try {
                    val d = sdfApi.parse(teks)
                    if (d != null) cal.time = d
                } catch (_: Exception) {
                }
            }
            DatePickerDialog(
                requireContext(),
                { _, year, month, dayOfMonth ->
                    val dipilih = Calendar.getInstance().apply { set(year, month, dayOfMonth) }
                    target.text = sdfApi.format(dipilih.time)
                    setelahPilih?.invoke()
                },
                cal.get(Calendar.YEAR),
                cal.get(Calendar.MONTH),
                cal.get(Calendar.DAY_OF_MONTH)
            ).show()
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}
