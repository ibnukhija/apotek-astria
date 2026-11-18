package com.example.apotek_astria.ui.chat

import android.graphics.Color
import android.graphics.drawable.GradientDrawable
import android.os.Bundle
import android.view.Gravity
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.Button
import android.widget.EditText
import android.widget.FrameLayout
import android.widget.TextView
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import androidx.recyclerview.widget.LinearLayoutManager
import androidx.recyclerview.widget.RecyclerView
import com.android.volley.DefaultRetryPolicy
import com.android.volley.Request
import com.android.volley.toolbox.Volley
import com.example.apotek_astria.R
import com.example.apotek_astria.network.ApiConfig
import com.example.apotek_astria.network.ApiRequest
import org.json.JSONArray
import org.json.JSONObject

data class ChatMsg(val role: String, val text: String) // role: "user" atau "bot"

class ChatActivity : AppCompatActivity() {

    private val pesan = mutableListOf<ChatMsg>()
    private lateinit var adapter: ChatAdapter
    private lateinit var rv: RecyclerView
    private lateinit var etPesan: EditText
    private lateinit var btnKirim: Button

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_chat)
        title = "Asisten AI"

        rv = findViewById(R.id.rvChat)
        etPesan = findViewById(R.id.etPesan)
        btnKirim = findViewById(R.id.btnKirim)

        adapter = ChatAdapter(pesan)
        rv.layoutManager = LinearLayoutManager(this).apply { stackFromEnd = true }
        rv.adapter = adapter

        tambah(ChatMsg("bot", "Halo! Tanya ringkasan penjualan atau cara memakai aplikasi."))

        btnKirim.setOnClickListener { kirim() }
    }

    private fun tambah(m: ChatMsg) {
        pesan.add(m)
        adapter.notifyItemInserted(pesan.size - 1)
        rv.scrollToPosition(pesan.size - 1)
    }

    private fun kirim() {
        val teks = etPesan.text.toString().trim()
        if (teks.isEmpty()) return

        // riwayat = percakapan sebelum pesan ini, tanpa sapaan awal
        val riwayat = JSONArray()
        pesan.drop(1).takeLast(10).forEach {
            riwayat.put(JSONObject().put("role", it.role).put("text", it.text))
        }

        tambah(ChatMsg("user", teks))
        etPesan.setText("")
        btnKirim.isEnabled = false

        val body = JSONObject().put("pesan", teks)
        if (riwayat.length() > 0) body.put("riwayat", riwayat)

        val req = ApiRequest(
            this, Request.Method.POST, ApiConfig.URL_CHATBOT, body,
            { res ->
                btnKirim.isEnabled = true
                tambah(ChatMsg("bot", res.optString("jawaban", "Tidak ada jawaban")))
            },
            { err ->
                btnKirim.isEnabled = true
                if (!ApiConfig.tanganiAuthError(this, err)) {
                    Toast.makeText(this, ApiConfig.pesanError(err), Toast.LENGTH_LONG).show()
                }
            }
        )
        req.retryPolicy = DefaultRetryPolicy(40000, 0, 1f)
        Volley.newRequestQueue(this).add(req)
    }
}

class ChatAdapter(private val data: List<ChatMsg>) : RecyclerView.Adapter<ChatAdapter.VH>() {

    class VH(v: View) : RecyclerView.ViewHolder(v) {
        val tv: TextView = v.findViewById(R.id.tvPesan)
    }

    override fun onCreateViewHolder(parent: ViewGroup, viewType: Int): VH =
        VH(LayoutInflater.from(parent.context).inflate(R.layout.item_chat, parent, false))

    override fun getItemCount() = data.size

    override fun onBindViewHolder(h: VH, position: Int) {
        val m = data[position]
        h.tv.text = m.text
        val isUser = m.role == "user"
        h.tv.background = GradientDrawable().apply {
            cornerRadius = 32f
            setColor(if (isUser) Color.parseColor("#B2DFDB") else Color.parseColor("#EEEEEE"))
        }
        (h.tv.layoutParams as FrameLayout.LayoutParams).gravity =
            if (isUser) Gravity.END else Gravity.START
    }
}