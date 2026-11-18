package com.example.apotek_astria.ui.riwayat

import android.Manifest
import android.app.Notification
import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.ActivityNotFoundException
import android.content.ClipData
import android.content.ClipboardManager
import android.content.ContentValues
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.net.Uri
import android.os.Build
import android.os.Bundle
import android.os.CancellationSignal
import android.os.Environment
import android.os.ParcelFileDescriptor
import android.print.PageRange
import android.print.PrintAttributes
import android.print.PrintDocumentAdapter
import android.print.PrintDocumentInfo
import android.print.PrintManager
import android.provider.MediaStore
import android.widget.Toast
import androidx.core.app.NotificationCompat
import androidx.core.app.NotificationManagerCompat
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import com.example.apotek_astria.R
import com.example.apotek_astria.network.ApiConfig
import org.json.JSONObject
import java.io.File
import java.io.FileInputStream
import java.io.FileOutputStream
import java.net.HttpURLConnection
import java.net.URL
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

data class HasilEkspor(
    val namaFile: String,
    val fileCache: File,
    val uriBagikan: Uri,
    val lokasiSimpan: String,
    val mimeType: String,
    val isPdf: Boolean,
    val supabaseUploaded: Boolean = false,
    val supabaseUrl: String = "",
    val supabasePath: String = "",
    val supabaseStatus: String = ""
)

private data class UnduhanServer(
    val bytes: ByteArray,
    val namaFile: String?,
    val supabaseUploaded: Boolean,
    val supabaseUrl: String,
    val supabasePath: String,
    val supabaseStatus: String
)

object LaporanExportHelper {

    private const val CHANNEL_ID_UNDUH = "unduh_laporan"
    private const val NOTIF_ID_UNDUH = 3001

    fun buatUrlEkspor(
        format: String,
        periode: String,
        tglMulai: String,
        tglSelesai: String,
        kategori: String,
        namaObat: String
    ): String {
        val builder = Uri.parse(ApiConfig.URL_LAPORAN_EXPORT).buildUpon()
            .appendQueryParameter("format", format)
            .appendQueryParameter("periode", periode)

        if (tglMulai.isNotBlank()) builder.appendQueryParameter("tanggal_mulai", tglMulai)
        if (tglSelesai.isNotBlank()) builder.appendQueryParameter("tanggal_selesai", tglSelesai)
        if (kategori.isNotBlank()) builder.appendQueryParameter("kategori", kategori)
        if (namaObat.isNotBlank()) builder.appendQueryParameter("nama_obat", namaObat)

        return builder.build().toString()
    }

    /**
     * Unduh file PDF/XLSX dari Laravel API dan simpan ke penyimpanan perangkat (dijalankan di Dispatchers.IO).
     */
    fun unduhDanSimpan(context: Context, urlString: String, format: String): HasilEkspor {
        val token = ApiConfig.ambilToken(context)
            ?: throw IllegalStateException("Sesi login tidak ditemukan, silakan login ulang.")

        val conn = (URL(urlString).openConnection() as HttpURLConnection).apply {
            requestMethod = "GET"
            connectTimeout = 15000
            readTimeout = 30000
            setRequestProperty("Authorization", "Bearer $token")
            setRequestProperty("Accept", "*/*")
        }

        val unduhan = try {
            val code = conn.responseCode
            if (code != HttpURLConnection.HTTP_OK) {
                val errBody = conn.errorStream?.bufferedReader()?.use { it.readText() }.orEmpty()
                val pesan = try {
                    JSONObject(errBody).optString("message", "Gagal mengunduh laporan (HTTP $code)")
                } catch (_: Exception) {
                    "Gagal mengunduh laporan (HTTP $code)"
                }
                throw IllegalStateException(pesan)
            }

            val serverNama = conn.getHeaderField("X-Nama-File")?.trim()?.takeIf { it.isNotBlank() }
            val supaUploaded = conn.getHeaderField("X-Supabase-Uploaded") == "1"
            val supaUrl = conn.getHeaderField("X-Supabase-Url").orEmpty().trim()
            val supaPath = conn.getHeaderField("X-Supabase-Path").orEmpty().trim()
            val supaStatus = conn.getHeaderField("X-Supabase-Status").orEmpty().trim()
            val rawBytes = conn.inputStream.use { it.readBytes() }

            UnduhanServer(rawBytes, serverNama, supaUploaded, supaUrl, supaPath, supaStatus)
        } finally {
            conn.disconnect()
        }

        val isPdf = format == "pdf"
        val ekstensi = if (isPdf) "pdf" else "xlsx"
        val mimeType = if (isPdf) {
            "application/pdf"
        } else {
            "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
        }
        val capWaktu = SimpleDateFormat("yyyyMMdd_HHmmss", Locale.US).format(Date())
        val namaFile = unduhan.namaFile ?: "Laporan_Penjualan_$capWaktu.$ekstensi"

        val cacheDir = File(context.cacheDir, "laporan").apply { mkdirs() }
        val fileCache = File(cacheDir, namaFile)
        fileCache.writeBytes(unduhan.bytes)

        val uriBagikan = FileProvider.getUriForFile(
            context,
            "${context.packageName}.fileprovider",
            fileCache
        )

        val lokasiSimpan = simpanKeDownloads(context, namaFile, mimeType, unduhan.bytes)

        return HasilEkspor(
            namaFile = namaFile,
            fileCache = fileCache,
            uriBagikan = uriBagikan,
            lokasiSimpan = lokasiSimpan,
            mimeType = mimeType,
            isPdf = isPdf,
            supabaseUploaded = unduhan.supabaseUploaded,
            supabaseUrl = unduhan.supabaseUrl,
            supabasePath = unduhan.supabasePath,
            supabaseStatus = unduhan.supabaseStatus
        )
    }

    private fun siapkanChannelNotifikasi(context: Context) {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val channel = NotificationChannel(
                CHANNEL_ID_UNDUH,
                "Unduhan Laporan",
                NotificationManager.IMPORTANCE_HIGH
            ).apply {
                description = "Pemberitahuan progres dan hasil unduhan laporan penjualan"
            }
            context.getSystemService(NotificationManager::class.java)?.createNotificationChannel(channel)
        }
    }

    private fun kirimNotifikasi(context: Context, notif: Notification) {
        siapkanChannelNotifikasi(context)
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU &&
            ContextCompat.checkSelfPermission(context, Manifest.permission.POST_NOTIFICATIONS) !=
            PackageManager.PERMISSION_GRANTED
        ) {
            return
        }
        NotificationManagerCompat.from(context).notify(NOTIF_ID_UNDUH, notif)
    }

    fun tampilkanNotifMulai(context: Context, format: String) {
        val labelFormat = if (format == "excel") "Excel (.xlsx)" else "PDF"
        val notif = NotificationCompat.Builder(context, CHANNEL_ID_UNDUH)
            .setSmallIcon(android.R.drawable.stat_sys_download)
            .setContentTitle("Mengunduh Laporan $labelFormat")
            .setContentText("Sedang memproses dan menyimpan laporan penjualan...")
            .setOngoing(true)
            .setOnlyAlertOnce(true)
            .setProgress(0, 0, true)
            .build()

        kirimNotifikasi(context, notif)
    }

    fun tampilkanNotifSelesai(context: Context, hasil: HasilEkspor) {
        val openIntent = Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(hasil.uriBagikan, hasil.mimeType)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION or Intent.FLAG_ACTIVITY_NEW_TASK)
        }
        val pi = PendingIntent.getActivity(
            context,
            NOTIF_ID_UNDUH,
            Intent.createChooser(openIntent, "Buka Laporan"),
            PendingIntent.FLAG_IMMUTABLE or PendingIntent.FLAG_UPDATE_CURRENT
        )

        val infoCloud = if (hasil.supabaseUploaded) " & Supabase Storage" else ""
        val teks = "${hasil.namaFile} tersimpan di ${hasil.lokasiSimpan}$infoCloud"
        val notif = NotificationCompat.Builder(context, CHANNEL_ID_UNDUH)
            .setSmallIcon(android.R.drawable.stat_sys_download_done)
            .setContentTitle("Unduhan Laporan Selesai")
            .setContentText(teks)
            .setStyle(NotificationCompat.BigTextStyle().bigText(teks))
            .setContentIntent(pi)
            .setAutoCancel(true)
            .setOngoing(false)
            .setProgress(0, 0, false)
            .build()

        kirimNotifikasi(context, notif)
    }

    fun tampilkanNotifGagal(context: Context, pesan: String) {
        val notif = NotificationCompat.Builder(context, CHANNEL_ID_UNDUH)
            .setSmallIcon(android.R.drawable.stat_notify_error)
            .setContentTitle("Gagal Mengunduh Laporan")
            .setContentText(pesan)
            .setAutoCancel(true)
            .setOngoing(false)
            .setProgress(0, 0, false)
            .build()

        kirimNotifikasi(context, notif)
    }

    private fun simpanKeDownloads(
        context: Context,
        namaFile: String,
        mimeType: String,
        bytes: ByteArray
    ): String {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.Q) {
            val values = ContentValues().apply {
                put(MediaStore.Downloads.DISPLAY_NAME, namaFile)
                put(MediaStore.Downloads.MIME_TYPE, mimeType)
                put(MediaStore.Downloads.RELATIVE_PATH, "${Environment.DIRECTORY_DOWNLOADS}/ApotekAstria")
            }
            val resolver = context.contentResolver
            val uri = resolver.insert(MediaStore.Downloads.EXTERNAL_CONTENT_URI, values)
            if (uri != null) {
                resolver.openOutputStream(uri)?.use { it.write(bytes) }
                return "Downloads/ApotekAstria/$namaFile"
            }
        }

        val docsDir = context.getExternalFilesDir(Environment.DIRECTORY_DOCUMENTS) ?: context.filesDir
        docsDir.mkdirs()
        val dest = File(docsDir, namaFile)
        dest.writeBytes(bytes)
        return dest.absolutePath
    }

    fun bukaFile(context: Context, hasil: HasilEkspor) {
        val intent = Intent(Intent.ACTION_VIEW).apply {
            setDataAndType(hasil.uriBagikan, hasil.mimeType)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
        try {
            context.startActivity(Intent.createChooser(intent, "Buka Laporan"))
        } catch (_: ActivityNotFoundException) {
            Toast.makeText(
                context,
                "Tidak ada aplikasi pembuka file ini. File tersimpan di ${hasil.lokasiSimpan}",
                Toast.LENGTH_LONG
            ).show()
        }
    }

    fun bagikanFile(context: Context, hasil: HasilEkspor) {
        val intent = Intent(Intent.ACTION_SEND).apply {
            type = hasil.mimeType
            putExtra(Intent.EXTRA_STREAM, hasil.uriBagikan)
            addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION)
        }
        context.startActivity(Intent.createChooser(intent, "Bagikan Laporan"))
    }

    fun bukaUrlSupabase(context: Context, url: String) {
        if (url.isBlank()) {
            Toast.makeText(context, "Tautan Supabase belum tersedia", Toast.LENGTH_SHORT).show()
            return
        }
        val intent = Intent(Intent.ACTION_VIEW, Uri.parse(url))
        try {
            context.startActivity(intent)
        } catch (_: ActivityNotFoundException) {
            Toast.makeText(context, "Tidak ada browser untuk membuka tautan", Toast.LENGTH_SHORT).show()
        }
    }

    fun salinLinkSupabase(context: Context, url: String) {
        if (url.isBlank()) return
        val cm = context.getSystemService(Context.CLIPBOARD_SERVICE) as? ClipboardManager
        cm?.setPrimaryClip(ClipData.newPlainText("Tautan Laporan Supabase", url))
        Toast.makeText(context, "Tautan Supabase disalin ke clipboard", Toast.LENGTH_SHORT).show()
    }

    fun cetakPdf(context: Context, hasil: HasilEkspor) {
        val printManager = context.getSystemService(Context.PRINT_SERVICE) as? PrintManager
        if (printManager == null) {
            bukaFile(context, hasil)
            return
        }

        val namaDokumen = hasil.fileCache.nameWithoutExtension
        val adapter = object : PrintDocumentAdapter() {
            override fun onLayout(
                oldAttributes: PrintAttributes?,
                newAttributes: PrintAttributes,
                cancellationSignal: CancellationSignal?,
                callback: LayoutResultCallback?,
                extras: Bundle?
            ) {
                if (cancellationSignal?.isCanceled == true) {
                    callback?.onLayoutCancelled()
                    return
                }
                val info = PrintDocumentInfo.Builder(hasil.fileCache.name)
                    .setContentType(PrintDocumentInfo.CONTENT_TYPE_DOCUMENT)
                    .setPageCount(PrintDocumentInfo.PAGE_COUNT_UNKNOWN)
                    .build()
                callback?.onLayoutFinished(info, true)
            }

            override fun onWrite(
                pages: Array<out PageRange>?,
                destination: ParcelFileDescriptor,
                cancellationSignal: CancellationSignal?,
                callback: WriteResultCallback
            ) {
                try {
                    FileInputStream(hasil.fileCache).use { input ->
                        FileOutputStream(destination.fileDescriptor).use { output ->
                            input.copyTo(output)
                        }
                    }
                    if (cancellationSignal?.isCanceled == true) {
                        callback.onWriteCancelled()
                    } else {
                        callback.onWriteFinished(arrayOf(PageRange.ALL_PAGES))
                    }
                } catch (e: Exception) {
                    callback.onWriteFailed(e.message)
                }
            }
        }

        val attrs = PrintAttributes.Builder()
            .setMediaSize(PrintAttributes.MediaSize.ISO_A4.asLandscape())
            .build()
        printManager.print(namaDokumen, adapter, attrs)
    }
}
