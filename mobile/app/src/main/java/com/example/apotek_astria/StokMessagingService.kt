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

class StokMessagingService : FirebaseMessagingService() {

    override fun onNewToken(token: String) {
        PengaturanNotifikasiRepo.updateToken(applicationContext, token)
    }

    override fun onMessageReceived(message: RemoteMessage) {
        if (!PengaturanNotifikasiRepo.bacaLokal(this).notifAktif) return
        val judul = message.notification?.title ?: message.data["title"] ?: "Stok menipis"
        val isi = message.notification?.body ?: message.data["body"] ?: "Ada obat yang hampir habis."
        tampilkan(this, judul, isi)
    }

    companion object {
        const val CHANNEL_ID = "stok_menipis"

        fun buatChannel(ctx: Context) {
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
                val ch = NotificationChannel(
                    CHANNEL_ID, "Stok Menipis", NotificationManager.IMPORTANCE_HIGH
                ).apply { description = "Peringatan obat dengan stok di bawah batas" }
                ctx.getSystemService(NotificationManager::class.java).createNotificationChannel(ch)
            }
        }

        fun tampilkan(ctx: Context, judul: String, isi: String) {
            buatChannel(ctx)
            if (Build.VERSION.SDK_INT >= 33 &&
                ContextCompat.checkSelfPermission(ctx, Manifest.permission.POST_NOTIFICATIONS)
                != PackageManager.PERMISSION_GRANTED
            ) return

            val pi = PendingIntent.getActivity(
                ctx, 0, Intent(ctx, MainActivity::class.java),
                PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
            )
            val n = NotificationCompat.Builder(ctx, CHANNEL_ID)
                .setSmallIcon(R.mipmap.ic_launcher)
                .setContentTitle(judul)
                .setContentText(isi)
                .setStyle(NotificationCompat.BigTextStyle().bigText(isi))
                .setContentIntent(pi)
                .setAutoCancel(true)
                .build()
            NotificationManagerCompat.from(ctx).notify(System.currentTimeMillis().toInt(), n)
        }
    }
}