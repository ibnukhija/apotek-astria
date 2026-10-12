package com.example.apotek_astria

import android.content.Context
import android.content.Intent
import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Toast
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

    override fun onCreateView(
        inflater: LayoutInflater, container: ViewGroup?, savedInstanceState: Bundle?
    ): View {
        _binding = FragmentProfilBinding.inflate(inflater, container, false)
        return binding.root
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val pref = requireContext().getSharedPreferences("SesiApotek", Context.MODE_PRIVATE)
        binding.tvOwner.text = "Login sebagai: ${pref.getString("USER_NAMA", "Owner")}"

        binding.btnSimpan.setOnClickListener { simpanProfil() }
        binding.btnKelolaKaryawan.setOnClickListener {
            startActivity(Intent(requireContext(), KaryawanActivity::class.java))
        }

        muatProfil()
    }

    // Baca nilai dari JSON; kalau null di database, jadikan teks kosong
    private fun JSONObject.teks(key: String): String = if (isNull(key)) "" else optString(key, "")

    private fun muatProfil() {
        val request = ApiRequest(Request.Method.GET, url, null,
            { response ->
                if (_binding != null) {
                    val data = response.optJSONObject("data")
                    if (data != null) {
                        binding.etNamaApotek.setText(data.teks("nama_apotek"))
                        binding.etAlamat.setText(data.teks("alamat"))
                        binding.etLatitude.setText(data.teks("latitude"))
                        binding.etLongitude.setText(data.teks("longitude"))
                    }
                }
            },
            { error -> toast(ApiConfig.pesanError(error)) }
        )
        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun simpanProfil() {
        val nama = binding.etNamaApotek.text.toString().trim()
        val alamat = binding.etAlamat.text.toString().trim()
        val latStr = binding.etLatitude.text.toString().trim()
        val lngStr = binding.etLongitude.text.toString().trim()

        if (nama.isEmpty() || alamat.isEmpty()) {
            toast("Nama dan alamat apotek wajib diisi"); return
        }
        val lat = latStr.toDoubleOrNull()
        val lng = lngStr.toDoubleOrNull()
        if ((latStr.isNotEmpty() && lat == null) || (lngStr.isNotEmpty() && lng == null)) {
            toast("Latitude/Longitude harus berupa angka"); return
        }

        val body = JSONObject()
            .put("nama_apotek", nama)
            .put("alamat", alamat)
            .put("latitude", lat ?: JSONObject.NULL)
            .put("longitude", lng ?: JSONObject.NULL)

        val request = ApiRequest(Request.Method.PUT, url, body,
            { response -> toast(response.optString("message", "Profil disimpan")) },
            { error -> toast(ApiConfig.pesanError(error)) }
        )
        Volley.newRequestQueue(requireContext()).add(request)
    }

    private fun toast(pesan: String) {
        context?.let { Toast.makeText(it, pesan, Toast.LENGTH_SHORT).show() }
    }

    override fun onDestroyView() {
        super.onDestroyView()
        _binding = null
    }
}