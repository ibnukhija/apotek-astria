package com.example.apotek_astria.ui.opname

import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.appcompat.app.AppCompatActivity
import androidx.core.widget.doAfterTextChanged
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.R
import com.example.apotek_astria.databinding.ActivityStokOpnameBinding
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import java.util.Locale

data class OpnameSesiItem(
    val id: Int,
    val kode: String,
    val periode: String,
    val tanggal: String,
    val petugas: String,
    val jumlahObat: Int,
    val obatSelisih: Int,
    val unitKurang: Int,
    val unitLebih: Int,
    val rusak: Int,
    val hilang: Int
)

data class OpnameDetailRow(
    val kodeObat: String,
    val namaObat: String,
    val satuan: String,
    val stokSistem: Int,
    val stokFisik: Int?,
    val selisih: Int?,
    val rusak: Int,
    val hilang: Int,
    val catatan: String,
    val state: String
)

class StokOpnameActivity : AppCompatActivity() {

    private lateinit var binding: ActivityStokOpnameBinding
    private val requestQueue by lazy { Volley.newRequestQueue(applicationContext) }

    private val daftarSesi = mutableListOf<OpnameSesiItem>()
    private val semuaDetail = mutableListOf<OpnameDetailRow>()
    private val tampilDetail = mutableListOf<OpnameDetailRow>()

    private lateinit var sesiAdapter: OpnameSesiAdapter
    private lateinit var detailAdapter: OpnameDetailAdapter

    private var opnameIdAktif: Int? = null
    private var kataCari = ""

    companion object {
        const val EXTRA_OPNAME_ID = "extra_opname_id"
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityStokOpnameBinding.inflate(layoutInflater)
        setContentView(binding.root)

        sesiAdapter = OpnameSesiAdapter(daftarSesi) { sesi ->
            bukaDetail(sesi.id, sesi.kode, sesi.periode)
        }
        binding.rvDaftarOpname.layoutManager = LinearLayoutManager(this)
        binding.rvDaftarOpname.adapter = sesiAdapter

        detailAdapter = OpnameDetailAdapter(tampilDetail)
        binding.rvDetailOpname.layoutManager = LinearLayoutManager(this)
        binding.rvDetailOpname.adapter = detailAdapter

        binding.tvBack.setOnClickListener { tanganiKembali() }
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                tanganiKembali()
            }
        })

        binding.swipeRefreshOpname.setOnRefreshListener {
            val idAktif = opnameIdAktif
            if (idAktif != null) {
                muatDetail(idAktif)
            } else {
                muatDaftar()
            }
        }

        binding.etCariObatOpname.doAfterTextChanged {
            kataCari = it?.toString()?.trim().orEmpty()
            terapkanFilterDetail()
        }
        binding.cbHanyaSelisih.setOnCheckedChangeListener { _, _ ->
            terapkanFilterDetail()
        }

        val extraId = intent.getIntExtra(EXTRA_OPNAME_ID, 0)
        if (extraId > 0) {
            bukaDetail(extraId, "Detail Opname", "")
        } else {
            tampilkanModeDaftar()
            muatDaftar()
        }
    }

    private fun tanganiKembali() {
        if (opnameIdAktif != null && intent.getIntExtra(EXTRA_OPNAME_ID, 0) <= 0) {
            tampilkanModeDaftar()
            if (daftarSesi.isEmpty()) muatDaftar()
        } else {
            finish()
        }
    }

    private fun tampilkanModeDaftar() {
        opnameIdAktif = null
        binding.tvJudulOpname.text = "Stok Opname Bulanan"
        binding.tvSubJudulOpname.text = "Riwayat hasil opname yang difinalisasi"
        binding.layoutDaftarOpname.visibility = View.VISIBLE
        binding.layoutDetailOpname.visibility = View.GONE
    }

    private fun bukaDetail(id: Int, kode: String, periode: String) {
        opnameIdAktif = id
        binding.tvJudulOpname.text = kode
        binding.tvSubJudulOpname.text = if (periode.isNotBlank()) "Periode $periode" else "Memuat rincian opname..."
        binding.layoutDaftarOpname.visibility = View.GONE
        binding.layoutDetailOpname.visibility = View.VISIBLE
        binding.etCariObatOpname.setText("")
        binding.cbHanyaSelisih.isChecked = true
        kataCari = ""
        muatDetail(id)
    }

    private fun muatDaftar() {
        binding.swipeRefreshOpname.isRefreshing = true
        binding.tvEmptyDaftar.visibility = View.GONE

        val req = ApiRequest(
            this, Request.Method.GET, ApiConfig.URL_STOK_OPNAME, null,
            { res ->
                binding.swipeRefreshOpname.isRefreshing = false
                daftarSesi.clear()
                val arr = res.optJSONArray("data")
                if (arr != null) {
                    for (i in 0 until arr.length()) {
                        val o = arr.getJSONObject(i)
                        val r = o.optJSONObject("ringkasan")
                        daftarSesi.add(
                            OpnameSesiItem(
                                id = o.optInt("id"),
                                kode = o.optString("kode", "-"),
                                periode = o.optString("periode", "-"),
                                tanggal = o.optString("tanggal", ""),
                                petugas = o.optString("petugas", "Karyawan"),
                                jumlahObat = o.optInt("jumlah_obat", 0),
                                obatSelisih = r?.optInt("obat_selisih", 0) ?: 0,
                                unitKurang = r?.optInt("unit_kurang", 0) ?: 0,
                                unitLebih = r?.optInt("unit_lebih", 0) ?: 0,
                                rusak = r?.optInt("rusak", 0) ?: 0,
                                hilang = r?.optInt("hilang", 0) ?: 0
                            )
                        )
                    }
                }
                sesiAdapter.notifyDataSetChanged()
                binding.tvEmptyDaftar.visibility = if (daftarSesi.isEmpty()) View.VISIBLE else View.GONE
            },
            { error ->
                binding.swipeRefreshOpname.isRefreshing = false
                if (!ApiConfig.tanganiAuthError(this, error)) {
                    Toast.makeText(this, ApiConfig.pesanError(error), Toast.LENGTH_SHORT).show()
                }
            }
        )
        requestQueue.add(req)
    }

    private fun muatDetail(id: Int) {
        binding.swipeRefreshOpname.isRefreshing = true
        binding.tvEmptyDetail.visibility = View.GONE

        val url = "${ApiConfig.URL_STOK_OPNAME}/$id"
        val req = ApiRequest(
            this, Request.Method.GET, url, null,
            { res ->
                binding.swipeRefreshOpname.isRefreshing = false
                val data = res.optJSONObject("data") ?: return@ApiRequest
                val kode = data.optString("kode", "-")
                val periode = data.optString("periode", "-")
                binding.tvJudulOpname.text = kode
                binding.tvSubJudulOpname.text = "Periode $periode • ${data.optString("status", "final").uppercase(Locale.ROOT)}"

                val ringkasan = data.optJSONObject("ringkasan")
                val totalObat = ringkasan?.optInt("total_obat", 0) ?: 0
                val sudahDihitung = ringkasan?.optInt("sudah_dihitung", 0) ?: 0
                val obatSelisih = ringkasan?.optInt("obat_selisih", 0) ?: 0
                val unitKurang = ringkasan?.optInt("unit_kurang", 0) ?: 0
                val unitLebih = ringkasan?.optInt("unit_lebih", 0) ?: 0
                val totalRusak = ringkasan?.optInt("rusak", 0) ?: 0
                val totalHilang = ringkasan?.optInt("hilang", 0) ?: 0

                binding.tvSumDihitung.text = "$sudahDihitung / $totalObat"
                binding.tvSumSelisih.text = obatSelisih.toString()
                binding.tvSumKurang.text = unitKurang.toString()
                binding.tvSumLebih.text = unitLebih.toString()
                binding.tvSubRingkasanRusakHilang.text = "Rincian kurang — Rusak: $totalRusak unit • Hilang: $totalHilang unit"

                semuaDetail.clear()
                val arr = data.optJSONArray("selisih")
                if (arr != null) {
                    for (i in 0 until arr.length()) {
                        val row = arr.getJSONObject(i)
                        semuaDetail.add(
                            OpnameDetailRow(
                                kodeObat = row.optString("kode_obat", "-"),
                                namaObat = row.optString("nama_obat", "-"),
                                satuan = row.optString("satuan", "-"),
                                stokSistem = row.optInt("stok_sistem", 0),
                                stokFisik = if (row.isNull("stok_fisik")) null else row.optInt("stok_fisik"),
                                selisih = if (row.isNull("selisih")) null else row.optInt("selisih"),
                                rusak = row.optInt("rusak", 0),
                                hilang = row.optInt("hilang", 0),
                                catatan = if (row.isNull("catatan")) "" else row.optString("catatan", ""),
                                state = row.optString("state", "sama")
                            )
                        )
                    }
                }
                terapkanFilterDetail()
            },
            { error ->
                binding.swipeRefreshOpname.isRefreshing = false
                if (!ApiConfig.tanganiAuthError(this, error)) {
                    Toast.makeText(this, ApiConfig.pesanError(error), Toast.LENGTH_SHORT).show()
                }
            }
        )
        requestQueue.add(req)
    }

    private fun terapkanFilterDetail() {
        val hanyaSelisih = binding.cbHanyaSelisih.isChecked
        val q = kataCari.lowercase(Locale.ROOT)
        tampilDetail.clear()
        for (item in semuaDetail) {
            if (hanyaSelisih && item.state == "sama") continue
            if (q.isNotEmpty() &&
                !item.namaObat.lowercase(Locale.ROOT).contains(q) &&
                !item.kodeObat.lowercase(Locale.ROOT).contains(q)
            ) {
                continue
            }
            tampilDetail.add(item)
        }
        detailAdapter.notifyDataSetChanged()
        binding.tvEmptyDetail.visibility = if (tampilDetail.isEmpty()) View.VISIBLE else View.GONE
    }
}

private fun aturPil(tv: TextView, teks: String, bgHex: String, textHex: String) {
    val bg = GradientDrawable().apply {
        cornerRadius = 16f
        setColor(Color.parseColor(bgHex))
    }
    tv.text = teks
    tv.background = bg
    tv.setTextColor(Color.parseColor(textHex))
}

private class OpnameSesiAdapter(
    private val items: List<OpnameSesiItem>,
    private val onClick: (OpnameSesiItem) -> Unit
) : RecyclerView.Adapter<OpnameSesiAdapter.VH>() {

    class VH(v: View) : RecyclerView.ViewHolder(v) {
        val tvPeriode: TextView = v.findViewById(R.id.tvPeriode)
        val tvBadgeSelisih: TextView = v.findViewById(R.id.tvBadgeSelisih)
        val tvKodeTanggal: TextView = v.findViewById(R.id.tvKodeTanggal)
        val tvRingkasanPeriode: TextView = v.findViewById(R.id.tvRingkasanPeriode)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VH =
        VH(LayoutInflater.from(parent.context).inflate(R.layout.item_opname_periode, parent, false))

    override fun getItemCount() = items.size

    override fun onBindViewHolder(h: VH, position: Int) {
        val item = items[position]
        h.tvPeriode.text = "Periode ${item.periode}"
        val tglTampil = if (item.tanggal.isNotBlank()) ApiConfig.formatTanggal(item.tanggal) else "-"
        h.tvKodeTanggal.text = "${item.kode} • $tglTampil • ${item.petugas}"
        h.tvRingkasanPeriode.text =
            "${item.jumlahObat} obat • Kurang ${item.unitKurang} (Rsk ${item.rusak}, Hlg ${item.hilang}) • Lebih ${item.unitLebih}"

        if (item.obatSelisih == 0) {
            aturPil(h.tvBadgeSelisih, "Sesuai Sistem", "#DCFCE7", "#166534")
        } else {
            aturPil(h.tvBadgeSelisih, "${item.obatSelisih} Selisih", "#FEF3C7", "#92400E")
        }

        h.itemView.setOnClickListener { onClick(item) }
    }
}

private class OpnameDetailAdapter(
    private val items: List<OpnameDetailRow>
) : RecyclerView.Adapter<OpnameDetailAdapter.VH>() {

    class VH(v: View) : RecyclerView.ViewHolder(v) {
        val tvNamaObat: TextView = v.findViewById(R.id.tvNamaObat)
        val tvKodeSatuan: TextView = v.findViewById(R.id.tvKodeSatuan)
        val tvBadgeState: TextView = v.findViewById(R.id.tvBadgeState)
        val tvAngkaStok: TextView = v.findViewById(R.id.tvAngkaStok)
        val tvRincianRusakHilang: TextView = v.findViewById(R.id.tvRincianRusakHilang)
        val tvCatatanObat: TextView = v.findViewById(R.id.tvCatatanObat)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VH =
        VH(LayoutInflater.from(parent.context).inflate(R.layout.item_opname_detail, parent, false))

    override fun getItemCount() = items.size

    override fun onBindViewHolder(h: VH, position: Int) {
        val item = items[position]
        h.tvNamaObat.text = item.namaObat
        h.tvKodeSatuan.text = "${item.kodeObat} • ${item.satuan}"

        val fisikTeks = item.stokFisik?.toString() ?: "-"
        h.tvAngkaStok.text = "Stok Sistem: ${item.stokSistem} • Stok Fisik: $fisikTeks"

        when (item.state) {
            "kurang", "rincian" -> aturPil(h.tvBadgeState, "Selisih ${item.selisih ?: 0}", "#FEE2E2", "#991B1B")
            "lebih" -> aturPil(h.tvBadgeState, "Lebih +${item.selisih ?: 0}", "#DCFCE7", "#166534")
            "kosong" -> aturPil(h.tvBadgeState, "Kosong", "#F1F5F9", "#475569")
            else -> aturPil(h.tvBadgeState, "Sesuai", "#E8F4F2", "#1F7A6F")
        }

        if (item.rusak > 0 || item.hilang > 0) {
            h.tvRincianRusakHilang.visibility = View.VISIBLE
            h.tvRincianRusakHilang.text = "Rincian — Rusak: ${item.rusak} ${item.satuan} • Hilang: ${item.hilang} ${item.satuan}"
        } else {
            h.tvRincianRusakHilang.visibility = View.GONE
        }

        if (item.catatan.isNotBlank()) {
            h.tvCatatanObat.visibility = View.VISIBLE
            h.tvCatatanObat.text = "Catatan: ${item.catatan}"
        } else {
            h.tvCatatanObat.visibility = View.GONE
        }
    }
}
