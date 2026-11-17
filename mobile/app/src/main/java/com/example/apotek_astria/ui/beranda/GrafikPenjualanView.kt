package com.example.apotek_astria.ui.beranda

import android.content.Context
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.Path
import android.util.AttributeSet
import android.util.TypedValue
import android.view.MotionEvent
import android.view.View
import android.view.ViewConfiguration
import java.util.Locale
import kotlin.math.abs
import kotlin.math.ceil
import kotlin.math.log10
import kotlin.math.max
import kotlin.math.min
import kotlin.math.pow

data class TitikGrafik(
    val label: String,
    val keterangan: String,
    val total: Long,
    val jumlahTransaksi: Int
)

class GrafikPenjualanView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null,
    defStyleAttr: Int = 0
) : View(context, attrs, defStyleAttr) {

    private val items = mutableListOf<TitikGrafik>()
    private var selectedIndex = -1
    var onBarSelected: ((TitikGrafik) -> Unit)? = null

    private var downX = 0f
    private var downY = 0f
    private var chartLeft = 0f
    private var slotWidth = 0f
    private val touchSlop = ViewConfiguration.get(context).scaledTouchSlop

    private val gridPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.parseColor("#E2E8F0")
        strokeWidth = dp(1f)
    }

    private val yLabelPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.parseColor("#64748B")
        textSize = sp(10f)
        textAlign = Paint.Align.RIGHT
    }

    private val xLabelPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.parseColor("#64748B")
        textSize = sp(11f)
        textAlign = Paint.Align.CENTER
    }

    private val xLabelSelectedPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.parseColor("#1E293B")
        textSize = sp(11f)
        textAlign = Paint.Align.CENTER
        isFakeBoldText = true
    }

    private val valuePaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.parseColor("#1F7A6F")
        textSize = sp(10f)
        textAlign = Paint.Align.CENTER
        isFakeBoldText = true
    }

    private val barPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.parseColor("#99D5CE")
        style = Paint.Style.FILL
    }

    private val barSelectedPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.parseColor("#2A9D8F")
        style = Paint.Style.FILL
    }

    private val emptyPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = Color.parseColor("#94A3B8")
        textSize = sp(13f)
        textAlign = Paint.Align.CENTER
    }

    private val barPath = Path()

    fun setData(newItems: List<TitikGrafik>) {
        items.clear()
        items.addAll(newItems)
        selectedIndex = when {
            items.isEmpty() -> -1
            else -> {
                val lastNonZero = items.indexOfLast { it.total > 0L }
                if (lastNonZero >= 0) lastNonZero else items.lastIndex
            }
        }
        invalidate()
        if (selectedIndex in items.indices) {
            onBarSelected?.invoke(items[selectedIndex])
        }
    }

    override fun onDraw(canvas: Canvas) {
        super.onDraw(canvas)
        val w = width.toFloat()
        val h = height.toFloat()
        if (w <= 0f || h <= 0f) return

        val maxTotal = items.maxOfOrNull { it.total } ?: 0L
        if (items.isEmpty() || maxTotal <= 0L) {
            val yCenter = h / 2f - (emptyPaint.descent() + emptyPaint.ascent()) / 2f
            canvas.drawText("Belum ada data penjualan pada periode ini", w / 2f, yCenter, emptyPaint)
            return
        }

        val niceMax = hitungNiceMax(maxTotal)
        val yLabelWidth = yLabelPaint.measureText(formatRingkas(niceMax)) + dp(8f)

        chartLeft = paddingLeft + yLabelWidth
        val chartRight = w - paddingRight - dp(8f)
        val chartTop = paddingTop + dp(18f)
        val chartBottom = h - paddingBottom - dp(24f)
        val chartWidth = max(1f, chartRight - chartLeft)
        val chartHeight = max(1f, chartBottom - chartTop)

        // 5 garis grid horizontal (0%, 25%, 50%, 75%, 100%)
        val steps = 4
        for (i in 0..steps) {
            val fraction = i.toFloat() / steps
            val y = chartBottom - fraction * chartHeight
            canvas.drawLine(chartLeft, y, chartRight, y, gridPaint)

            val labelVal = (niceMax * fraction).toLong()
            val textY = y - (yLabelPaint.descent() + yLabelPaint.ascent()) / 2f
            canvas.drawText(formatRingkas(labelVal), chartLeft - dp(6f), textY, yLabelPaint)
        }

        slotWidth = chartWidth / items.size
        val barWidth = min(slotWidth * 0.56f, dp(30f))
        val cornerRadius = dp(5f)
        val radii = floatArrayOf(
            cornerRadius, cornerRadius,
            cornerRadius, cornerRadius,
            0f, 0f,
            0f, 0f
        )

        items.forEachIndexed { index, item ->
            val centerX = chartLeft + slotWidth * (index + 0.5f)
            val ratio = (item.total.toFloat() / niceMax.toFloat()).coerceIn(0f, 1f)
            val barTop = if (item.total > 0L) {
                min(chartBottom - dp(3f), chartBottom - ratio * chartHeight)
            } else {
                chartBottom
            }

            if (item.total > 0L) {
                barPath.reset()
                barPath.addRoundRect(
                    centerX - barWidth / 2f,
                    barTop,
                    centerX + barWidth / 2f,
                    chartBottom,
                    radii,
                    Path.Direction.CW
                )
                val paint = if (index == selectedIndex) barSelectedPaint else barPaint
                canvas.drawPath(barPath, paint)
            }

            if (index == selectedIndex && item.total > 0L) {
                val valText = formatRingkas(item.total)
                val halfW = valuePaint.measureText(valText) / 2f
                val textX = centerX.coerceIn(chartLeft + halfW, max(chartLeft + halfW, chartRight - halfW))
                val textY = max(dp(12f), barTop - dp(5f))
                canvas.drawText(valText, textX, textY, valuePaint)
            }

            val labelPaint = if (index == selectedIndex) xLabelSelectedPaint else xLabelPaint
            val xLabelY = chartBottom + dp(16f)
            canvas.drawText(item.label, centerX, xLabelY, labelPaint)
        }
    }

    override fun onTouchEvent(event: MotionEvent): Boolean {
        if (items.isEmpty() || slotWidth <= 0f) return super.onTouchEvent(event)

        when (event.actionMasked) {
            MotionEvent.ACTION_DOWN -> {
                downX = event.x
                downY = event.y
                return true
            }
            MotionEvent.ACTION_UP -> {
                val dx = abs(event.x - downX)
                val dy = abs(event.y - downY)
                if (dx < touchSlop && dy < touchSlop) {
                    val idx = ((event.x - chartLeft) / slotWidth).toInt().coerceIn(0, items.lastIndex)
                    selectedIndex = idx
                    invalidate()
                    onBarSelected?.invoke(items[idx])
                    performClick()
                }
                return true
            }
        }
        return super.onTouchEvent(event)
    }

    override fun performClick(): Boolean {
        super.performClick()
        return true
    }

    private fun hitungNiceMax(rawMax: Long): Long {
        if (rawMax <= 1000L) return 1000L
        val exp = 10.0.pow(kotlin.math.floor(log10(rawMax.toDouble()))).toLong()
        val normalized = rawMax.toDouble() / exp
        val niceNormalized = when {
            normalized <= 1.0 -> 1.0
            normalized <= 2.0 -> 2.0
            normalized <= 5.0 -> 5.0
            else -> 10.0
        }
        val nice = (niceNormalized * exp).toLong()
        return max(4L, ceil(nice.toDouble() / 4.0).toLong() * 4L)
    }

    private fun formatRingkas(nilai: Long): String {
        val locale = Locale.forLanguageTag("id-ID")
        return when {
            nilai >= 1_000_000_000L -> {
                val m = nilai / 1_000_000_000.0
                if (m % 1.0 == 0.0) "${m.toLong()} M" else String.format(locale, "%.1f M", m)
            }
            nilai >= 1_000_000L -> {
                val jt = nilai / 1_000_000.0
                if (jt % 1.0 == 0.0) "${jt.toLong()} jt" else String.format(locale, "%.1f jt", jt)
            }
            nilai >= 1_000L -> "${nilai / 1_000L} rb"
            else -> nilai.toString()
        }
    }

    private fun dp(value: Float): Float =
        TypedValue.applyDimension(TypedValue.COMPLEX_UNIT_DIP, value, resources.displayMetrics)

    private fun sp(value: Float): Float =
        TypedValue.applyDimension(TypedValue.COMPLEX_UNIT_SP, value, resources.displayMetrics)
}
