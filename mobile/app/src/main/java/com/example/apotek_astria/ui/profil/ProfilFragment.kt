package com.example.apotek_astria.ui.profil

import android.Manifest
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.os.Build
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.CheckBox
import android.widget.TextView
import android.widget.Toast
import androidx.activity.OnBackPressedCallback
import androidx.core.content.ContextCompat
import androidx.fragment.app.Fragment
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.PengaturanNotifikasi
import com.example.apotek_astria.PengaturanNotifikasiRepo
import com.example.apotek_astria.StokMessagingService
import com.example.apotek_astria.databinding.FragmentProfilBinding
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import com.example.apotek_astria.ui.karyawan.KaryawanActivity
import org.json.JSONArray
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
        siapkanFormOperasional()

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
        binding.btnTesNotifikasi.setOnClickListener { tesNotifikasi() }

        if (Build.VERSION.SDK_INT >= 33 &&
            ContextCompat.checkSelfPermission(requireContext(), Manifest.permission.POST_NOTIFICATIONS)
            != PackageManager.PERMISSION_GRANTED
        ) {
            izinNotifikasi.launch(Manifest.permission.POST_NOTIFICATIONS)
        }
    }

    private fun terapkan(p: PengaturanNotifikasi) {
        binding.switchNotifikasi.isChecked = p.notifAktif
        binding.switchNotifKadaluarsa.isChecked = p.notifKadaluarsa
        binding.switchNotifRusak.isChecked = p.notifRusak
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

    private fun buatPreferensiDariForm(): PengaturanNotifikasi? {
        val batas = binding.etBatasStok.text.toString().trim().toIntOrNull()
        if (batas == null || batas < 0) {
            toast("Batas minimum stok harus berupa angka")
            return null
        }

        return PengaturanNotifikasi(
            notifAktif = binding.switchNotifikasi.isChecked,
            notifKadaluarsa = binding.switchNotifKadaluarsa.isChecked,
            notifRusak = binding.switchNotifRusak.isChecked,
            batasStok = batas,
            jam = jamTerpilih
        )
    }

    private fun simpanNotifikasi() {
        val p = buatPreferensiDariForm() ?: return

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

    private fun tesNotifikasi() {
        val p = buatPreferensiDariForm() ?: return
        val ctx = requireContext()

        binding.btnTesNotifikasi.isEnabled = false
        PengaturanNotifikasiRepo.simpan(ctx, p) { _ ->
            if (_binding == null) return@simpan
            PengaturanNotifikasiRepo.kirimTes(ctx) { _, pesan ->
                if (_binding == null) return@kirimTes
                binding.btnTesNotifikasi.isEnabled = true
                toast(pesan)
            }
        }
    }

    // ---------------------------------------------------------------
    // Profil apotek, jam operasional & status toko (API)
    // ---------------------------------------------------------------

    private var jamBuka = "07:30"
    private var jamTutup = "21:00"

    private fun petaCheckboxHari(): List<Pair<CheckBox, String>> = listOf(
        binding.cbHariSenin to "Senin",
        binding.cbHariSelasa to "Selasa",
        binding.cbHariRabu to "Rabu",
        binding.cbHariKamis to "Kamis",
        binding.cbHariJumat to "Jumat",
        binding.cbHariSabtu to "Sabtu",
        binding.cbHariMinggu to "Minggu"
    )

    private fun siapkanFormOperasional() {
        binding.btnJamBuka.setOnClickListener {
            pilihJamOperasional(jamBuka) { dipilih ->
                jamBuka = dipilih
                binding.btnJamBuka.text = dipilih
            }
        }
        binding.btnJamTutup.setOnClickListener {
            pilihJamOperasional(jamTutup) { dipilih ->
                jamTutup = dipilih
                binding.btnJamTutup.text = dipilih
            }
        }
        binding.switchBuka24Jam.setOnCheckedChangeListener { _, isChecked ->
            perbaruiTampilan24Jam(isChecked)
        }
        binding.switchTutupPaksa.setOnCheckedChangeListener { _, isChecked ->
            binding.etAlasanTutup.visibility = if (isChecked) View.VISIBLE else View.GONE
            if (isChecked) {
                perbaruiBadgeStatusToko(binding.tvStatusTokoEdit, false, "TUTUP (Paksa)")
            }
        }
    }

    private fun perbaruiTampilan24Jam(is24Jam: Boolean) {
        binding.btnJamBuka.isEnabled = !is24Jam
        binding.btnJamTutup.isEnabled = !is24Jam
        binding.btnJamBuka.alpha = if (is24Jam) 0.45f else 1f
        binding.btnJamTutup.alpha = if (is24Jam) 0.45f else 1f
    }

    private fun pilihJamOperasional(awal: String, setelahPilih: (String) -> Unit) {
        val (h, m) = awal.split(":").map { it.toIntOrNull() ?: 0 }
        android.app.TimePickerDialog(
            requireContext(),
            { _, jam, menit ->
                setelahPilih(String.format(java.util.Locale.US, "%02d:%02d", jam, menit))
            },
            h, m, true
        ).show()
    }

    private fun perbaruiBadgeStatusToko(target: TextView, isBuka: Boolean, teksOverride: String? = null) {
        target.text = teksOverride ?: if (isBuka) "BUKA" else "TUTUP"
        target.setTextColor(Color.parseColor(if (isBuka) "#166534" else "#991B1B"))
        target.background = GradientDrawable().apply {
            setColor(Color.parseColor(if (isBuka) "#DCFCE7" else "#FEE2E2"))
            cornerRadius = 14f * resources.displayMetrics.density
        }
    }

    private fun JSONObject.teks(key: String): String {
        return if (isNull(key)) "" else optString(key, "")
    }

    private fun muatProfil() {
        val request = ApiRequest(
            requireContext(),
            Request.Method.GET,
            url,
            null,
            { response ->
                if (_binding != null) {
                    val data = response.optJSONObject("data")

                    if (data != null) {
                        val namaApotek = data.teks("nama_apotek").ifBlank { "Apotek Astria 2 Farma" }
                        val alamat = data.teks("alamat")

                        binding.tvNamaApotekMenu.text = namaApotek
                        binding.etNamaApotek.setText(namaApotek)
                        binding.etAlamat.setText(alamat)
                        binding.etLatitude.setText(data.teks("latitude"))
                        binding.etLongitude.setText(data.teks("longitude"))

                        binding.tvRingkasanAlamat.text =
                            if (alamat.isBlank()) "Alamat belum diisi" else alamat

                        jamBuka = data.teks("jam_buka").ifBlank { "07:30" }
                        jamTutup = data.teks("jam_tutup").ifBlank { "21:00" }
                        val buka24Jam = data.optBoolean("buka_24_jam", false)
                        val tutupPaksa = data.optBoolean("tutup_paksa", false)
                        val alasanTutup = data.teks("alasan_tutup")
                        val isBuka = data.optBoolean("is_buka", true)
                        val jadwalRingkas = data.teks("jadwal_ringkas").ifBlank { "Setiap hari • $jamBuka - $jamTutup" }
                        val ketStatus = data.teks("keterangan_status")

                        binding.btnJamBuka.text = jamBuka
                        binding.btnJamTutup.text = jamTutup
                        binding.switchBuka24Jam.isChecked = buka24Jam
                        perbaruiTampilan24Jam(buka24Jam)

                        val hariArray = data.optJSONArray("hari_operasional")
                        val hariSet = mutableSetOf<String>()
                        if (hariArray != null) {
                            for (i in 0 until hariArray.length()) {
                                val h = hariArray.optString(i)
                                if (h.isNotBlank()) hariSet.add(h)
                            }
                        }
                        for ((cb, namaHari) in petaCheckboxHari()) {
                            cb.isChecked = hariSet.isEmpty() || hariSet.contains(namaHari)
                        }

                        binding.switchTutupPaksa.isChecked = tutupPaksa
                        binding.etAlasanTutup.setText(alasanTutup)
                        binding.etAlasanTutup.visibility = if (tutupPaksa) View.VISIBLE else View.GONE

                        binding.tvRingkasanJadwal.text = jadwalRingkas
                        binding.tvKeteranganStatusMenu.text = ketStatus
                        perbaruiBadgeStatusToko(binding.tvStatusTokoMenu, isBuka)
                        perbaruiBadgeStatusToko(
                            binding.tvStatusTokoEdit,
                            isBuka,
                            if (tutupPaksa) "TUTUP (Paksa)" else if (isBuka) "BUKA" else "TUTUP"
                        )
                    } else {
                        toast("Data profil tidak ditemukan")
                    }
                }
            },
            { error ->
                if (!ApiConfig.tanganiAuthError(context, error)) {
                    toast(ApiConfig.pesanError(error))
                }
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

        val hariDipilih = JSONArray()
        for ((cb, namaHari) in petaCheckboxHari()) {
            if (cb.isChecked) hariDipilih.put(namaHari)
        }
        if (hariDipilih.length() == 0) {
            toast("Pilih minimal satu hari operasional")
            return
        }

        val tutupPaksa = binding.switchTutupPaksa.isChecked
        val alasanTutup = binding.etAlasanTutup.text.toString().trim()

        val body = JSONObject()
            .put("nama_apotek", nama)
            .put("alamat", alamat)
            .put("latitude", lat ?: JSONObject.NULL)
            .put("longitude", lng ?: JSONObject.NULL)
            .put("jam_buka", jamBuka)
            .put("jam_tutup", jamTutup)
            .put("buka_24_jam", binding.switchBuka24Jam.isChecked)
            .put("hari_operasional", hariDipilih)
            .put("tutup_paksa", tutupPaksa)
            .put("alasan_tutup", if (tutupPaksa && alasanTutup.isNotEmpty()) alasanTutup else JSONObject.NULL)

        val request = ApiRequest(
            requireContext(),
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
                if (!ApiConfig.tanganiAuthError(context, error)) {
                    toast(ApiConfig.pesanError(error))
                }
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