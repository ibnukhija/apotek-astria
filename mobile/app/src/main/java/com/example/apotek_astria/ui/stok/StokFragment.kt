package com.example.apotek_astria

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.AdapterView
import android.widget.ArrayAdapter
import android.widget.PopupMenu
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.databinding.FragmentStokBinding
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import org.json.JSONObject
import java.text.NumberFormat
import java.util.Locale

class StokFragment : Fragment() {

    private var _binding: FragmentStokBinding? = null
    private val binding get() = _binding!!

    private val semuaObat = mutableListOf<ObatModel>()   // data lengkap dari server
    private val tampilObat = mutableListOf<ObatModel>()  // data setelah difilter
    private val daftarKategori = mutableListOf<String>()

    private lateinit var adapter: StokAdapter
    private lateinit var kategoriAdapter: ArrayAdapter<String>
    private lateinit var cariAdapter: ArrayAdapter<String>

    private val semuaKategori = "Semua Kategori"
    private val rupiah = NumberFormat.getNumberInstance(Locale("in", "ID"))

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentStokBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        // ListView
        adapter = StokAdapter(requireContext(), tampilObat) { obat, anchor ->
            tampilkanPopupMenu(obat, anchor)   // PopupMenu
        }
        binding.lvStok.adapter = adapter
        binding.lvStok.setOnItemClickListener { _, _, position, _ ->
            tampilkanDetail(tampilObat[position])
        }

        // Spinner kategori
        kategoriAdapter = ArrayAdapter(requireContext(), android.R.layout.simple_spinner_dropdown_item, daftarKategori)
        binding.spKategori.adapter = kategoriAdapter
        binding.spKategori.onItemSelectedListener = object : AdapterView.OnItemSelectedListener {
            override fun onItemSelected(parent: AdapterView<*>?, v: View?, pos: Int, id: Long) {
                terapkanFilter()
            }
            override fun onNothingSelected(parent: AdapterView<*>?) {}
        }

        // AutoCompleteTextView nama obat
        cariAdapter = ArrayAdapter(requireContext(), android.R.layout.simple_dropdown_item_1line, mutableListOf<String>())
        binding.actvCari.setAdapter(cariAdapter)
        binding.actvCari.threshold = 1
        binding.actvCari.doAfterTextChanged { terapkanFilter() }

        muatObat()
    }

    private fun muatObat() {
        binding.progressBar.visibility = View.VISIBLE

        val request = ApiRequest(Request.Method.GET, ApiConfig.URL_STOK, null,
            { response -> if (_binding != null) olahResponse(response) },
            { error ->
                if (_binding != null) binding.progressBar.visibility = View.GONE
                toast(ApiConfig.pesanError(error))
            }
        )
        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun olahResponse(response: JSONObject) {
        binding.progressBar.visibility = View.GONE
        if (!response.optBoolean("success", false)) return

        semuaObat.clear()
        val arr = response.optJSONArray("data")
        if (arr != null) {
            for (i in 0 until arr.length()) {
                val o = arr.getJSONObject(i)
                semuaObat.add(
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
                        statusStok = o.optString("status_stok", "Cukup")
                    )
                )
            }
        }

        // Isi Spinner (pertahankan pilihan sebelumnya)
        val sebelumnya = binding.spKategori.selectedItem as? String
        daftarKategori.clear()
        daftarKategori.add(semuaKategori)
        daftarKategori.addAll(semuaObat.map { it.kategori }.distinct().sorted())
        kategoriAdapter.notifyDataSetChanged()
        val idx = daftarKategori.indexOf(sebelumnya)
        binding.spKategori.setSelection(if (idx >= 0) idx else 0)

        // Isi saran AutoComplete
        cariAdapter.clear()
        cariAdapter.addAll(semuaObat.map { it.namaObat })

        terapkanFilter()
    }

    private fun terapkanFilter() {
        if (_binding == null) return
        val kategori = binding.spKategori.selectedItem as? String ?: semuaKategori
        val kata = binding.actvCari.text.toString().trim()

        tampilObat.clear()
        tampilObat.addAll(semuaObat.filter {
            (kategori == semuaKategori || it.kategori == kategori) &&
                    it.namaObat.contains(kata, ignoreCase = true)
        })
        adapter.notifyDataSetChanged()

        val hampirHabis = tampilObat.count { it.statusStok.equals("Hampir habis", ignoreCase = true) }
        binding.tvRingkasan.text = "${tampilObat.size} obat • $hampirHabis hampir habis"
        binding.tvEmpty.visibility = if (tampilObat.isEmpty()) View.VISIBLE else View.GONE
    }

    private fun tampilkanPopupMenu(obat: ObatModel, anchor: View) {
        val popup = PopupMenu(requireContext(), anchor)
        popup.menuInflater.inflate(R.menu.menu_popup_stok, popup.menu)
        popup.setOnMenuItemClickListener { item ->
            when (item.itemId) {
                R.id.menu_detail_obat -> tampilkanDetail(obat)
                R.id.menu_rincian_harga -> tampilkanRincianHarga(obat)
            }
            true
        }
        popup.show()
    }

    private fun tampilkanDetail(obat: ObatModel) {
        val pesan = "Kode     : ${obat.kodeObat}\n" +
                "Kategori : ${obat.kategori}\n" +
                "Satuan   : ${obat.satuan}\n" +
                "Stok     : ${obat.stok} (${obat.statusStok})\n" +
                "Status   : ${if (obat.status == "tersedia") "Tersedia" else "Tidak tersedia"}\n" +
                "Harga    : Rp ${rupiah.format(obat.hargaJual)}"
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

    private fun toast(pesan: String) {
        context?.let { Toast.makeText(it, pesan, Toast.LENGTH_SHORT).show() }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}