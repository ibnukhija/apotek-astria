package com.example.apotek_astria

import android.Manifest
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import com.google.firebase.messaging.FirebaseMessagingService
import com.google.firebase.messaging.RemoteMessage
import java.util.concurrent.atomic.AtomicInteger

class StokMessagingService : FirebaseMessagingService() {

    override fun onNewToken(token: String) {
        PengaturanNotifikasiRepo.updateToken(applicationContext, token)
    }

    override fun onMessageReceived(message: RemoteMessage) {
        val pref = PengaturanNotifikasiRepo.bacaLokal(this)
        val jenis = message.data["jenis"] ?: "stok_menipis"

        val aktif = when (jenis) {
            "kadaluarsa" -> pref.notifKadaluarsa
            "obat_rusak" -> pref.notifRusak
            else -> pref.notifAktif
        }
        if (!aktif) return

        val channelId = when (jenis) {
            "kadaluarsa" -> CHANNEL_KADALUARSA
            "obat_rusak" -> CHANNEL_RUSAK
            else -> CHANNEL_STOK
        }
        val defaultJudul = when (jenis) {
            "kadaluarsa" -> "Peringatan Kadaluarsa"
            "obat_rusak" -> "Laporan Obat Rusak"
            else -> "Stok Menipis"
        }

        val judul = message.notification?.title ?: message.data["title"] ?: defaultJudul
        val isi = message.notification?.body ?: message.data["body"] ?: "Periksa pembaruan stok apotek."
        val tujuan = message.data["tujuan"] ?: "stok"
        tampilkan(this, judul, isi, channelId, tujuan)
    }

    companion object {
        const val CHANNEL_STOK = "stok_menipis"
        const val CHANNEL_KADALUARSA = "kadaluarsa"
        const val CHANNEL_RUSAK = "obat_rusak"
        private val notifCounter = AtomicInteger(100)

        fun buatChannel(ctx: Context) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                val nm = ctx.getSystemService(NotificationManager::class.java)
                val channels = listOf(
                    NotificationChannel(
                        CHANNEL_STOK,
                        "Stok Menipis",
                        NotificationManager.IMPORTANCE_HIGH
                    ).apply { description = "Peringatan obat dengan stok di bawah batas minimum" },
                    NotificationChannel(
                        CHANNEL_KADALUARSA,
                        "Kadaluarsa & H-7",
                        NotificationManager.IMPORTANCE_HIGH
                    ).apply { description = "Pengingat obat mendekati expired (retur) dan sudah kadaluarsa (musnahkan)" },
                    NotificationChannel(
                        CHANNEL_RUSAK,
                        "Obat Rusak & Stok Opname",
                        NotificationManager.IMPORTANCE_HIGH
                    ).apply { description = "Laporan kondisi obat rusak dan penyesuaian stok opname" }
                )
                nm.createNotificationChannels(channels)
            }
        }

        fun tampilkan(
            ctx: Context,
            judul: String,
            isi: String,
            channelId: String = CHANNEL_STOK,
            tujuan: String = "stok"
        ) {
            buatChannel(ctx)
            if (Build.VERSION.SDK_INT >= 33 &&
                ContextCompat.checkSelfPermission(ctx, Manifest.permission.POST_NOTIFICATIONS)
                != PackageManager.PERMISSION_GRANTED
            ) return

            val notifId = notifCounter.incrementAndGet()
            val intent = Intent(ctx, MainActivity::class.java).apply {
                flags = Intent.FLAG_ACTIVITY_SINGLE_TOP or Intent.FLAG_ACTIVITY_CLEAR_TOP
                putExtra("tujuan", tujuan)
            }
            val pi = PendingIntent.getActivity(
                ctx, notifId, intent,
                PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
            )
            val n = NotificationCompat.Builder(ctx, channelId)
                .setSmallIcon(R.mipmap.ic_launcher)
                .setContentTitle(judul)
                .setContentText(isi)
                .setStyle(NotificationCompat.BigTextStyle().bigText(isi))
                .setContentIntent(pi)
                .setAutoCancel(true)
                .build()
            NotificationManagerCompat.from(ctx).notify(notifId, n)
        }
    }
}
