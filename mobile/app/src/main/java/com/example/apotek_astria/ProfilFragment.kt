package com.example.apotek_astria

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.fragment.app.Fragment
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.databinding.FragmentProfilBinding
import org.json.JSONObject

class ProfilFragment : Fragment() {

    private var _binding: FragmentProfilBinding? = null
    private val binding get() = _binding!!

    private val url = "${ApiConfig.BASE_URL}/profil"

    private enum class Halaman { MENU, EDIT_PROFIL, NOTIFIKASI, AKUN }

    private val backCallback = object : OnBackPressedCallback(false) {
        override fun handleOnBackPressed() {
            tampilkan(Halaman.MENU)
        }
    }

    override fun onCreateView(
        inflater: LayoutInflater,
        container: ViewGroup?,
        savedInstanceState: Bundle?
    ): View {
        _binding = FragmentProfilBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        requireActivity().onBackPressedDispatcher
            .addCallback(viewLifecycleOwner, backCallback)

        val pref = requireContext().getSharedPreferences(
            "SesiApotek",
            Context.MODE_PRIVATE
        )

        binding.tvOwner.text =
            "Login sebagai: ${pref.getString("USER_NAMA", "Owner")}"

        siapkanNavigasi()
        siapkanNotifikasi()

        binding.btnSimpan.setOnClickListener { simpanProfil() }

        binding.btnKelolaKaryawan.setOnClickListener {
            startActivity(
                Intent(requireContext(), KaryawanActivity::class.java)
            )
        }

        tampilkan(Halaman.MENU)
        muatProfil()
    }

    // ---------------------------------------------------------------
    // Navigasi antar halaman di dalam satu fragment
    // ---------------------------------------------------------------

    private fun siapkanNavigasi() {
        binding.btnBukaEditProfil.setOnClickListener { tampilkan(Halaman.EDIT_PROFIL) }
        binding.btnMenuEditProfil.setOnClickListener { tampilkan(Halaman.EDIT_PROFIL) }
        binding.btnMenuNotifikasi.setOnClickListener { tampilkan(Halaman.NOTIFIKASI) }
        binding.btnMenuAkun.setOnClickListener { tampilkan(Halaman.AKUN) }

        binding.btnKembaliEdit.setOnClickListener { tampilkan(Halaman.MENU) }
        binding.btnKembaliNotifikasi.setOnClickListener { tampilkan(Halaman.MENU) }
        binding.btnKembaliAkun.setOnClickListener { tampilkan(Halaman.MENU) }
    }

    private fun tampilkan(halaman: Halaman) {
        binding.layoutMenuProfil.visibility =
            if (halaman == Halaman.MENU) View.VISIBLE else View.GONE
        binding.layoutEditProfil.visibility =
            if (halaman == Halaman.EDIT_PROFIL) View.VISIBLE else View.GONE
        binding.layoutNotifikasi.visibility =
            if (halaman == Halaman.NOTIFIKASI) View.VISIBLE else View.GONE
        binding.layoutAkun.visibility =
            if (halaman == Halaman.AKUN) View.VISIBLE else View.GONE

        // Tombol back sistem hanya dicegat saat bukan di menu utama
        backCallback.isEnabled = halaman != Halaman.MENU
    }

    // ---------------------------------------------------------------
    // Preferensi notifikasi stok (disimpan lokal)
    // ---------------------------------------------------------------

    private fun prefPengaturan() = requireContext().getSharedPreferences(
        "PengaturanApotek",
        Context.MODE_PRIVATE
    )

    private fun siapkanNotifikasi() {
        val pref = prefPengaturan()

        binding.switchNotifikasi.isChecked = pref.getBoolean("NOTIF_STOK", true)
        binding.etBatasStok.setText(pref.getInt("BATAS_STOK", 10).toString())

        binding.btnSimpanNotifikasi.setOnClickListener { simpanNotifikasi() }
    }

    private fun simpanNotifikasi() {
        val batas = binding.etBatasStok.text.toString().trim().toIntOrNull()

        if (batas == null || batas < 0) {
            toast("Batas minimum stok harus berupa angka")
            return
        }

        prefPengaturan().edit()
            .putBoolean("NOTIF_STOK", binding.switchNotifikasi.isChecked)
            .putInt("BATAS_STOK", batas)
            .apply()

        toast("Preferensi notifikasi disimpan")
        tampilkan(Halaman.MENU)
    }

    // ---------------------------------------------------------------
    // Profil apotek (API)
    // ---------------------------------------------------------------

    private fun JSONObject.teks(key: String): String {
        return if (isNull(key)) "" else optString(key, "")
    }

    private fun muatProfil() {
        val request = ApiRequest(
            Request.Method.GET,
            url,
            null,
            { response ->
                if (_binding != null) {
                    val data = response.optJSONObject("data")

                    if (data != null) {
                        val alamat = data.teks("alamat")

                        binding.etNamaApotek.setText(data.teks("nama_apotek"))
                        binding.etAlamat.setText(alamat)
                        binding.etLatitude.setText(data.teks("latitude"))
                        binding.etLongitude.setText(data.teks("longitude"))

                        binding.tvRingkasanAlamat.text =
                            if (alamat.isBlank()) "Alamat belum diisi" else alamat
                    } else {
                        toast("Data profil tidak ditemukan")
                    }
                }
            },
            { error ->
                toast(ApiConfig.pesanError(error))
            }
        )

        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun simpanProfil() {
        val nama = binding.etNamaApotek.text.toString().trim()
        val alamat = binding.etAlamat.text.toString().trim()
        val latStr = binding.etLatitude.text.toString().trim()
        val lngStr = binding.etLongitude.text.toString().trim()

        if (nama.isEmpty() || alamat.isEmpty()) {
            toast("Nama dan alamat apotek wajib diisi")
            return
        }

        val lat = latStr.toDoubleOrNull()
        val lng = lngStr.toDoubleOrNull()

        if ((latStr.isNotEmpty() && lat == null) ||
            (lngStr.isNotEmpty() && lng == null)
        ) {
            toast("Latitude/Longitude harus berupa angka")
            return
        }

        val body = JSONObject()
            .put("nama_apotek", nama)
            .put("alamat", alamat)
            .put("latitude", lat ?: JSONObject.NULL)
            .put("longitude", lng ?: JSONObject.NULL)

        val request = ApiRequest(
            Request.Method.PUT,
            url,
            body,
            { response ->
                val berhasil = response.optBoolean("success", false)

                if (berhasil) {
                    toast(response.optString("message", "Profil berhasil disimpan"))
                    muatProfil()
                    if (_binding != null) tampilkan(Halaman.MENU)
                } else {
                    toast(response.optString("message", "Profil gagal disimpan"))
                }
            },
            { error ->
                toast(ApiConfig.pesanError(error))
            }
        )

        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun toast(pesan: String) {
        context?.let {
            Toast.makeText(it, pesan, Toast.LENGTH_SHORT).show()
        }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}