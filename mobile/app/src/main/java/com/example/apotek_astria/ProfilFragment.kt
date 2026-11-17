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
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import org.json.JSONObject

class ProfilFragment : Fragment() {

    private var _binding: FragmentProfilBinding? = null
    private val binding get() = _binding!!
    private val url = ApiConfig.URL_PROFIL

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
    // Preferensi notifikasi stok (Firestore: pengaturan_notifikasi)
    // ---------------------------------------------------------------

    private var jamTerpilih = "08:00"

    private val izinNotifikasi = registerForActivityResult(
        androidx.activity.result.contract.ActivityResultContracts.RequestPermission()
    ) { diberi ->
        if (!diberi) toast("Izin notifikasi ditolak, notifikasi tidak akan tampil")
    }

    private fun siapkanNotifikasi() {
        StokMessagingService.buatChannel(requireContext())

        terapkan(PengaturanNotifikasiRepo.bacaLokal(requireContext()))
        PengaturanNotifikasiRepo.muat(requireContext()) { p ->
            if (_binding != null) terapkan(p)
        }

        binding.btnJamNotifikasi.setOnClickListener { pilihJam() }
        binding.btnSimpanNotifikasi.setOnClickListener { simpanNotifikasi() }

        if (android.os.Build.VERSION.SDK_INT >= 33) {
            izinNotifikasi.launch(android.Manifest.permission.POST_NOTIFICATIONS)
        }
    }

    private fun terapkan(p: PengaturanNotifikasi) {
        binding.switchNotifikasi.isChecked = p.notifAktif
        binding.etBatasStok.setText(p.batasStok.toString())
        jamTerpilih = p.jam
        binding.btnJamNotifikasi.text = p.jam
    }

    private fun pilihJam() {
        val (h, m) = jamTerpilih.split(":").map { it.toIntOrNull() ?: 0 }
        android.app.TimePickerDialog(
            requireContext(),
            { _, jam, menit ->
                jamTerpilih = String.format(java.util.Locale.US, "%02d:%02d", jam, menit)
                binding.btnJamNotifikasi.text = jamTerpilih
            },
            h, m, true
        ).show()
    }

    private fun simpanNotifikasi() {
        val batas = binding.etBatasStok.text.toString().trim().toIntOrNull()
        if (batas == null || batas < 0) {
            toast("Batas minimum stok harus berupa angka")
            return
        }

        val p = PengaturanNotifikasi(
            notifAktif = binding.switchNotifikasi.isChecked,
            batasStok = batas,
            jam = jamTerpilih
        )

        binding.btnSimpanNotifikasi.isEnabled = false
        PengaturanNotifikasiRepo.simpan(requireContext(), p) { berhasil ->
            if (_binding == null) return@simpan
            binding.btnSimpanNotifikasi.isEnabled = true
            toast(
                if (berhasil) "Preferensi notifikasi disimpan"
                else "Tersimpan di perangkat, gagal sinkron ke server"
            )
            tampilkan(Halaman.MENU)
        }
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