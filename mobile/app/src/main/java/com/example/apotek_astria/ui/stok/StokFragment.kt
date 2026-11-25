package com.example.apotek_astria.ui.stok

import android.content.Intent
import android.graphics.Color
import android.net.Uri
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.AdapterView
import android.widget.ArrayAdapter
import android.widget.EditText
import android.widget.PopupMenu
import android.widget.RadioButton
import android.widget.RadioGroup
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.R
import com.example.apotek_astria.databinding.FragmentStokBinding
import com.example.apotek_astria.model.ObatBatchModel
import com.example.apotek_astria.model.ObatModel
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import com.example.apotek_astria.ui.opname.StokOpnameActivity
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject
import java.text.NumberFormat
import java.util.Locale

class StokFragment : Fragment() {

    private var _binding: FragmentStokBinding? = null
    private val binding get() = _binding!!

    private val tampilObat = mutableListOf<ObatModel>()
    private val daftarKategori = mutableListOf<String>()

    private lateinit var adapter: StokAdapter
    private lateinit var kategoriAdapter: ArrayAdapter<String>
    private lateinit var cariAdapter: ArrayAdapter<String>

    private val semuaKategori = "Semua Kategori"
    private var kategoriDipilih = semuaKategori
    private var kataCari = ""
    private var statusFilterKhusus: String? = null
    private var currentPage = 1
    private var lastPage = 1
    private var requestToken = 0
    private var searchJob: Job? = null

    private val rupiah = NumberFormat.getNumberInstance(Locale.forLanguageTag("id-ID"))

    companion object {
        const val ARG_FILTER_STATUS = "arg_filter_status"
        const val STATUS_MENIPIS = "menipis"
        const val STATUS_KADALUARSA = "kadaluarsa"
        const val STATUS_RUSAK = "rusak"
    }

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentStokBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        currentPage = 1
        kategoriDipilih = semuaKategori
        kataCari = ""

        // Tangani filter khusus dari Beranda / Notifikasi jika ada
        statusFilterKhusus = arguments?.getString(ARG_FILTER_STATUS)
        perbaruiTampilanFilterKhusus()

        binding.btnResetFilterKhusus.setOnClickListener {
            statusFilterKhusus = null
            arguments?.remove(ARG_FILTER_STATUS)
            perbaruiTampilanFilterKhusus()
            currentPage = 1
            muatObat()
        }

        binding.tvBukaOpnameStok.setOnClickListener {
            startActivity(Intent(requireContext(), StokOpnameActivity::class.java))
        }

        // ListView
        adapter = StokAdapter(requireContext(), tampilObat) { obat, anchor ->
            tampilkanPopupMenu(obat, anchor)
        }
        binding.lvStok.adapter = adapter
        binding.lvStok.setOnItemClickListener { _, _, position, _ ->
            tampilkanDetail(tampilObat[position])
        }

        // Spinner kategori (inisialisasi dengan "Semua Kategori" agar tidak memicu muat ulang ganda)
        daftarKategori.clear()
        daftarKategori.add(semuaKategori)
        kategoriAdapter = ArrayAdapter(requireContext(), android.R.layout.simple_spinner_dropdown_item, daftarKategori)
        binding.spKategori.adapter = kategoriAdapter
        binding.spKategori.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, v: View?, pos: Int, id: Long) {
                val pilihan = daftarKategori.getOrNull(pos) ?: semuaKategori
                if (pilihan != kategoriDipilih) {
                    kategoriDipilih = pilihan
                    currentPage = 1
                    muatObat()
                }
            }
            override fun onNothingSelected(parent: AdapterView<*>?) {}
        }

        // AutoCompleteTextView nama/kode obat dengan debounce ke server
        cariAdapter = ArrayAdapter(requireContext(), android.R.layout.simple_dropdown_item_1line, mutableListOf<String>())
        binding.actvCari.setAdapter(cariAdapter)
        binding.actvCari.threshold = 1
        binding.actvCari.doAfterTextChanged {
            val kataBaru = it?.toString()?.trim().orEmpty()
            if (kataBaru == kataCari) return@doAfterTextChanged
            searchJob?.cancel()
            searchJob = viewLifecycleOwner.lifecycleScope.launch {
                delay(350L)
                kataCari = kataBaru
                currentPage = 1
                muatObat()
            }
        }

        // Setup SwipeRefreshLayout
        binding.swipeRefreshLayout.setOnRefreshListener {
            currentPage = 1
            muatObat()
        }

        // Tombol Pagination
        binding.btnPrevPage.setOnClickListener {
            if (currentPage > 1) {
                currentPage--
                muatObat()
            }
        }
        binding.btnNextPage.setOnClickListener {
            if (currentPage < lastPage) {
                currentPage++
                muatObat()
            }
        }

        muatObat()
    }

    private fun perbaruiTampilanFilterKhusus() {
        val konfigurasi = when (statusFilterKhusus) {
            STATUS_MENIPIS -> Triple("Filter: Hampir Habis (< 10)", "#FEF3C7", "#92400E" to "#B45309")
            STATUS_KADALUARSA -> Triple("Filter: Hampir / Sudah Kadaluarsa (≤ 7 hari)", "#FEE2E2", "#991B1B" to "#DC2626")
            STATUS_RUSAK -> Triple("Filter: Obat Pernah Rusak", "#FFEDD5", "#9A3412" to "#EA580C")
            else -> null
        }
        if (konfigurasi == null) {
            binding.layoutFilterKhusus.visibility = View.GONE
            return
        }
        val (teks, bgHex, warnaTeks) = konfigurasi
        binding.layoutFilterKhusus.visibility = View.VISIBLE
        binding.layoutFilterKhusus.setBackgroundColor(Color.parseColor(bgHex))
        binding.tvLabelFilterKhusus.text = teks
        binding.tvLabelFilterKhusus.setTextColor(Color.parseColor(warnaTeks.first))
        binding.btnResetFilterKhusus.setTextColor(Color.parseColor(warnaTeks.second))
    }

    private fun muatObat() {
        binding.swipeRefreshLayout.isRefreshing = true
        binding.tvEmpty.visibility = View.GONE
        val token = ++requestToken

        val builder = Uri.parse(ApiConfig.URL_STOK).buildUpon()
            .appendQueryParameter("page", currentPage.toString())
        if (kategoriDipilih != semuaKategori) {
            builder.appendQueryParameter("kategori", kategoriDipilih)
        }
        if (kataCari.isNotEmpty()) {
            builder.appendQueryParameter("search", kataCari)
        }
        if (statusFilterKhusus == STATUS_MENIPIS) {
            builder.appendQueryParameter("status_stok", "hampir_habis")
        } else if (statusFilterKhusus == STATUS_KADALUARSA) {
            builder.appendQueryParameter("status_kadaluarsa", "perhatian")
        } else if (statusFilterKhusus == STATUS_RUSAK) {
            builder.appendQueryParameter("status_rusak", "1")
        }
        val url = builder.build().toString()

        val request = ApiRequest(requireContext(), Request.Method.GET, url, null,
            { response ->
                if (isAdded && _binding != null && token == requestToken) {
                    olahResponse(response, token)
                }
            },
            { error ->
                if (!isAdded || _binding == null || token != requestToken) return@ApiRequest
                binding.swipeRefreshLayout.isRefreshing = false
                if (!ApiConfig.tanganiAuthError(context, error)) {
                    toast(ApiConfig.pesanError(error))
                }
            }
        )
        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun olahResponse(response: JSONObject, token: Int) {
        if (!response.optBoolean("success", false)) {
            binding.swipeRefreshLayout.isRefreshing = false
            return
        }

        val arr = response.optJSONArray("data")
        val katArr = response.optJSONArray("kategoris")
        val halamanSekarang = response.optInt("current_page", 1)
        val halamanTerakhir = response.optInt("last_page", 1)
        val totalObat = response.optInt("total", arr?.length() ?: 0)
        val hampirHabisTotal = response.optInt("hampir_habis_total", 0)
        val perhatianExpTotal = response.optInt("perhatian_kadaluarsa_total", 0)

        // Proses parsing data obat di background thread (Coroutine) agar UI tetap responsif
        viewLifecycleOwner.lifecycleScope.launch(Dispatchers.Default) {
            val temporaryList = mutableListOf<ObatModel>()
            if (arr != null) {
                for (i in 0 until arr.length()) {
                    val o = arr.getJSONObject(i)
                    val rawExp = if (o.isNull("tgl_kadaluarsa")) "" else o.optString("tgl_kadaluarsa", "")
                    val batchList = mutableListOf<ObatBatchModel>()
                    val batchArr = o.optJSONArray("daftar_batch")
                    if (batchArr != null) {
                        for (j in 0 until batchArr.length()) {
                            val b = batchArr.getJSONObject(j)
                            batchList.add(
                                ObatBatchModel(
                                    kodeBatch = b.optString("kode_batch", "-"),
                                    supplier = if (b.isNull("supplier")) "-" else b.optString("supplier", "-"),
                                    tglKadaluarsa = if (b.isNull("tgl_kadaluarsa")) "" else b.optString("tgl_kadaluarsa", ""),
                                    stok = b.optInt("stok", 0),
                                    statusKadaluarsa = b.optString("status_kadaluarsa", "Belum diatur")
                                )
                            )
                        }
                    }
                    temporaryList.add(
                        ObatModel(
                            id = o.optInt("id"),
                            kodeObat = o.optString("kode_obat", "-"),
                            namaObat = o.optString("nama_obat"),
                            kategori = o.optJSONObject("kategori")?.optString("nama_kategori", "-") ?: "-",
                            satuan = o.optString("satuan", "-"),
                            hargaBeli = o.optInt("harga_beli", 0),
                            margin = o.optDouble("margin", 0.0),
                            tipeMargin = o.optString("tipe_margin", "nominal"),
                            hargaJual = o.optInt("harga_jual", 0),
                            stok = o.optInt("stok", 0),
                            status = o.optString("status", "tersedia"),
                            statusStok = o.optString("status_stok", "Cukup"),
                            tglKadaluarsa = rawExp,
                            statusKadaluarsa = o.optString("status_kadaluarsa", "Belum diatur"),
                            totalRusak = o.optInt("total_rusak", 0),
                            opnameTerakhir = o.optString("opname_terakhir", ""),
                            batches = batchList
                        )
                    )
                }
            }

            val kategoriServer = mutableListOf<String>()
            if (katArr != null) {
                for (i in 0 until katArr.length()) {
                    val namaKat = katArr.optString(i)
                    if (namaKat.isNotBlank()) kategoriServer.add(namaKat)
                }
            }
            val kategoriBaru = if (kategoriServer.isNotEmpty()) {
                kategoriServer
            } else {
                temporaryList.map { it.kategori }.distinct().sorted()
            }
            val saranNama = temporaryList.map { it.namaObat }

            withContext(Dispatchers.Main) {
                if (!isAdded || _binding == null || token != requestToken) return@withContext

                binding.swipeRefreshLayout.isRefreshing = false
                currentPage = halamanSekarang
                lastPage = halamanTerakhir

                tampilObat.clear()
                tampilObat.addAll(temporaryList)
                adapter.notifyDataSetChanged()
                if (tampilObat.isNotEmpty()) {
                    binding.lvStok.setSelection(0)
                }

                // Perbarui UI Pagination
                binding.tvPageInfo.text = "Halaman $currentPage dari $lastPage"
                binding.layoutPagination.visibility = if (lastPage > 1) View.VISIBLE else View.GONE
                binding.btnPrevPage.isEnabled = currentPage > 1
                binding.btnNextPage.isEnabled = currentPage < lastPage

                // Perbarui ringkasan total sesuai hasil filter
                val infoExp = if (perhatianExpTotal > 0) " • $perhatianExpTotal kadaluarsa/hampir" else ""
                binding.tvRingkasan.text = "$totalObat obat • $hampirHabisTotal hampir habis$infoExp"
                binding.tvEmpty.visibility = if (tampilObat.isEmpty()) View.VISIBLE else View.GONE

                // Isi Spinner kategori (pertahankan pilihan tanpa memicu muat ulang)
                daftarKategori.clear()
                daftarKategori.add(semuaKategori)
                daftarKategori.addAll(kategoriBaru)
                kategoriAdapter.notifyDataSetChanged()
                val idx = daftarKategori.indexOf(kategoriDipilih)
                if (idx >= 0) {
                    binding.spKategori.setSelection(idx)
                } else {
                    kategoriDipilih = semuaKategori
                    binding.spKategori.setSelection(0)
                }

                // Isi saran AutoComplete
                cariAdapter.clear()
                cariAdapter.addAll(saranNama)
            }
        }
    }

    private fun tampilkanPopupMenu(obat: ObatModel, anchor: View) {
        val popup = PopupMenu(requireContext(), anchor)
        popup.menuInflater.inflate(R.menu.menu_popup_stok, popup.menu)
        popup.setOnMenuItemClickListener { item ->
            when (item.itemId) {
                R.id.menu_detail_obat -> tampilkanDetail(obat)
                R.id.menu_rincian_harga -> tampilkanRincianHarga(obat)
                R.id.menu_ubah_cepat -> tampilkanDialogUbahCepat(obat)
            }
            true
        }
        popup.show()
    }

    private fun tampilkanDetail(obat: ObatModel) {
        val tglExp = ApiConfig.formatTanggal(obat.tglKadaluarsa)
        val statusExp = obat.statusKadaluarsa.ifBlank { "Belum diatur" }
        val infoExp = if (tglExp.isNotBlank()) "$tglExp ($statusExp)" else statusExp
        val saranExp = when {
            statusExp.equals("Kadaluarsa", ignoreCase = true) -> "\nTindakan   : Harus dimusnahkan"
            statusExp.equals("Hampir kadaluarsa", ignoreCase = true) -> "\nTindakan   : Bisa diretur ke supplier"
            else -> ""
        }
        val infoRusak = if (obat.totalRusak > 0) "\nUnit Rusak : ${obat.totalRusak}" else ""
        val infoOpname = if (obat.opnameTerakhir.isNotBlank()) "\nStok Opname: ${obat.opnameTerakhir}" else ""
        val infoBatch = if (obat.batches.isNotEmpty()) {
            val barisBatch = obat.batches.joinToString("\n") { b ->
                val expB = ApiConfig.formatTanggal(b.tglKadaluarsa).ifBlank { "-" }
                "  • ${b.kodeBatch}: ${b.stok} ${obat.satuan} • Exp $expB (${b.statusKadaluarsa})"
            }
            "\nBatch Aktif:\n$barisBatch"
        } else ""

        val pesan = "Kode       : ${obat.kodeObat}\n" +
                "Kategori   : ${obat.kategori}\n" +
                "Satuan     : ${obat.satuan}\n" +
                "Stok       : ${obat.stok} (${obat.statusStok})\n" +
                "Kadaluarsa : $infoExp$saranExp$infoRusak$infoOpname$infoBatch\n" +
                "Status     : ${if (obat.status == "tersedia") "Tersedia" else "Tidak tersedia"}\n" +
                "Harga      : Rp ${rupiah.format(obat.hargaJual)}"
        AlertDialog.Builder(requireContext())
            .setTitle(obat.namaObat)
            .setMessage(pesan)
            .setPositiveButton("Tutup", null)
            .show()
    }

    private fun tampilkanRincianHarga(obat: ObatModel) {
        val marginTeks = if (obat.tipeMargin == "persen") {
            val m = if (obat.margin % 1.0 == 0.0) obat.margin.toInt().toString() else obat.margin.toString()
            "$m%"
        } else {
            "Rp ${rupiah.format(obat.margin.toInt())}"
        }
        val pesan = "Harga beli : Rp ${rupiah.format(obat.hargaBeli)}\n" +
                "Margin     : $marginTeks\n" +
                "Harga jual : Rp ${rupiah.format(obat.hargaJual)}"
        AlertDialog.Builder(requireContext())
            .setTitle("Rincian ${obat.namaObat}")
            .setMessage(pesan)
            .setPositiveButton("Tutup", null)
            .show()
    }

    private fun tampilkanDialogUbahCepat(obat: ObatModel) {
        val dialogView = LayoutInflater.from(requireContext())
            .inflate(R.layout.dialog_ubah_cepat_obat, null)

        val tvNama       = dialogView.findViewById<TextView>(R.id.tvNamaObatDialog)
        val etHarga      = dialogView.findViewById<EditText>(R.id.etHargaJual)
        val rgStatus     = dialogView.findViewById<RadioGroup>(R.id.rgStatus)
        val rbTersedia   = dialogView.findViewById<RadioButton>(R.id.rbTersedia)
        val rbTidak      = dialogView.findViewById<RadioButton>(R.id.rbTidakTersedia)

        // Isi nilai awal
        tvNama.text = obat.namaObat
        etHarga.setText(obat.hargaJual.toString())
        etHarga.setSelection(etHarga.text.length) // cursor di akhir
        if (obat.status == "tersedia") rbTersedia.isChecked = true else rbTidak.isChecked = true

        AlertDialog.Builder(requireContext())
            .setTitle("Ubah Cepat Obat")
            .setView(dialogView)
            .setPositiveButton("Simpan") { _, _ ->
                val hargaBaru = etHarga.text.toString().trim().toLongOrNull()
                if (hargaBaru == null || hargaBaru <= 0) {
                    toast("Harga jual tidak valid")
                    return@setPositiveButton
                }
                val statusBaru = if (rbTersedia.isChecked) "tersedia" else "tidak"
                kirimUbahCepat(obat, hargaBaru.toInt(), statusBaru)
            }
            .setNegativeButton("Batal", null)
            .show()
    }

    private fun kirimUbahCepat(obat: ObatModel, hargaJualBaru: Int, statusBaru: String) {
        val url = "${ApiConfig.URL_OBAT}/${obat.id}"
        val body = JSONObject().apply {
            put("harga_jual", hargaJualBaru)
            put("status", statusBaru)
        }

        val request = ApiRequest(requireContext(), Request.Method.PUT, url, body,
            { response ->
                if (!isAdded) return@ApiRequest
                val sukses = response.optBoolean("success", false)
                val pesan  = response.optString("message", "")
                if (sukses) {
                    toast("✓ ${obat.namaObat} berhasil diperbarui")
                    muatObat() // refresh list
                } else {
                    toast(pesan.ifBlank { "Gagal memperbarui obat" })
                }
            },
            { error ->
                if (!isAdded) return@ApiRequest
                if (!ApiConfig.tanganiAuthError(context, error)) {
                    toast(ApiConfig.pesanError(error))
                }
            }
        )
        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun toast(pesan: String) {
        context?.let { Toast.makeText(it, pesan, Toast.LENGTH_SHORT).show() }
    }

    override fun onDestroyView() {
        searchJob?.cancel()
        super.onDestroyView()
        _binding = null
    }
}
