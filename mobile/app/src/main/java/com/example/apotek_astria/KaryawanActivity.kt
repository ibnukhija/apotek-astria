package com.example.apotek_astria

import android.os.Bundle
import android.view.ContextMenu
import android.view.MenuItem
import android.view.View
import android.widget.AdapterView
import android.widget.Toast
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.databinding.ActivityKaryawanBinding
import com.example.apotek_astria.databinding.DialogKaryawanBinding
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import org.json.JSONObject

class KaryawanActivity : AppCompatActivity() {

    private lateinit var binding: ActivityKaryawanBinding
    private val daftar = mutableListOf<KaryawanModel>()
    private lateinit var adapter: KaryawanAdapter
    private val baseUrl = ApiConfig.URL_KARYAWAN

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityKaryawanBinding.inflate(layoutInflater)
        setContentView(binding.root)

        adapter = KaryawanAdapter(this, daftar)
        binding.lvKaryawan.adapter = adapter
        registerForContextMenu(binding.lvKaryawan)   // aktifkan ContextMenu (tekan lama)

        binding.tvBack.setOnClickListener { finish() }
        binding.btnTambah.setOnClickListener { tampilkanForm(null) }

        muatData()
    }

    // ===== ContextMenu =====
    override fun onCreateContextMenu(
        menu: ContextMenu?, v: View?, menuInfo: ContextMenu.ContextMenuInfo?
    ) {
        super.onCreateContextMenu(menu, v, menuInfo)
        if (menu == null || v?.id != R.id.lvKaryawan) return

        val info = menuInfo as AdapterView.AdapterContextMenuInfo
        val k = daftar[info.position]
        menu.setHeaderTitle(k.nama)
        menuInflater.inflate(R.menu.menu_context_karyawan, menu)
        menu.findItem(R.id.ctx_status).title = if (k.aktif) "Nonaktifkan Akun" else "Aktifkan Akun"
    }

    override fun onContextItemSelected(item: MenuItem): Boolean {
        val info = item.menuInfo as AdapterView.AdapterContextMenuInfo
        val k = daftar[info.position]
        when (item.itemId) {
            R.id.ctx_edit -> tampilkanForm(k)
            R.id.ctx_status -> konfirmasiStatus(k)
        }
        return true
    }

    // ===== Ambil data =====
    private fun muatData() {
        binding.progressBar.visibility = View.VISIBLE
        val request = ApiRequest(Request.Method.GET, baseUrl, null,
            { response ->
                binding.progressBar.visibility = View.GONE
                daftar.clear()
                val arr = response.optJSONArray("data")
                if (arr != null) {
                    for (i in 0 until arr.length()) {
                        val o = arr.getJSONObject(i)
                        daftar.add(
                            KaryawanModel(
                                id = o.optInt("id"),
                                nama = o.optString("nama"),
                                username = o.optString("username"),
                                aktif = o.optString("status", "aktif").equals("aktif", true)
                            )
                        )
                    }
                }
                adapter.notifyDataSetChanged()
                binding.tvEmpty.visibility = if (daftar.isEmpty()) View.VISIBLE else View.GONE
            },
            { error ->
                binding.progressBar.visibility = View.GONE
                toast(ApiConfig.pesanError(error))
            }
        )
        Volley.newRequestQueue(this).add(request)
    }

    // ===== Form tambah / edit =====
    private fun tampilkanForm(k: KaryawanModel?) {
        val form = DialogKaryawanBinding.inflate(layoutInflater)
        if (k != null) {
            form.etNama.setText(k.nama)
            form.etUsername.setText(k.username)
            form.etPassword.hint = "Password baru (kosongkan jika tidak diubah)"
        }

        val dialog = AlertDialog.Builder(this)
            .setTitle(if (k == null) "Tambah Karyawan" else "Edit Karyawan")
            .setView(form.root)
            .setPositiveButton("Simpan", null)   // di-override di bawah supaya dialog tidak langsung tertutup
            .setNegativeButton("Batal", null)
            .create()
        dialog.show()

        dialog.getButton(AlertDialog.BUTTON_POSITIVE).setOnClickListener {
            val nama = form.etNama.text.toString().trim()
            val username = form.etUsername.text.toString().trim()
            val password = form.etPassword.text.toString()

            if (nama.isEmpty() || username.isEmpty()) {
                toast("Nama dan username wajib diisi"); return@setOnClickListener
            }
            if (k == null && password.length < 6) {
                toast("Password minimal 6 karakter"); return@setOnClickListener
            }

            val body = JSONObject().put("nama", nama).put("username", username)
            if (password.isNotEmpty()) body.put("password", password)

            val method = if (k == null) Request.Method.POST else Request.Method.PUT
            val url = if (k == null) baseUrl else "$baseUrl/${k.id}"

            val request = ApiRequest(method, url, body,
                { response ->
                    toast(response.optString("message", "Berhasil"))
                    dialog.dismiss()
                    muatData()
                },
                { error -> toast(ApiConfig.pesanError(error)) }   // dialog tetap terbuka
            )
            Volley.newRequestQueue(this).add(request)
        }
    }

    // ===== Aktif / nonaktif =====
    private fun konfirmasiStatus(k: KaryawanModel) {
        val aksi = if (k.aktif) "menonaktifkan" else "mengaktifkan"
        AlertDialog.Builder(this)
            .setTitle("Konfirmasi")
            .setMessage("Yakin ingin $aksi akun ${k.nama}?")
            .setPositiveButton("Ya") { _, _ ->
                val request = ApiRequest(Request.Method.PUT, "$baseUrl/${k.id}/status", null,
                    { response ->
                        toast(response.optString("message", "Status diubah"))
                        muatData()
                    },
                    { error -> toast(ApiConfig.pesanError(error)) }
                )
                Volley.newRequestQueue(this).add(request)
            }
            .setNegativeButton("Batal", null)
            .show()
    }

    private fun toast(pesan: String) = Toast.makeText(this, pesan, Toast.LENGTH_SHORT).show()
}