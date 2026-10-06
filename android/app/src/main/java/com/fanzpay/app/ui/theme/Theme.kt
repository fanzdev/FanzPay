package com.fanzpay.app.ui.theme

import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.darkColorScheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.sp

// Brand — sama dengan web: biru → violet → sky
val Brand = Color(0xFF1B4DFF)
val BrandDark = Color(0xFF1238C7)
val Violet = Color(0xFF7C3AED)
val Sky = Color(0xFF2EA6E5)
val Ink = Color(0xFF0B1526)
val Muted = Color(0xFF64748B)
val Bg = Color(0xFFF4F6FB)
val Line = Color(0xFFE4E9F1)
val Ok = Color(0xFF12805C)
val Warn = Color(0xFFB45309)
val Bad = Color(0xFFC2410C)

// Dark tokens
val DarkBg = Color(0xFF070C1A)
val DarkSurface = Color(0xFF0F1830)
val DarkLine = Color(0x22FFFFFF)

val BrandGradient: Brush
    get() = Brush.linearGradient(listOf(Brand, Violet, Sky))

private val LightScheme = lightColorScheme(
    primary = Brand,
    onPrimary = Color.White,
    secondary = Sky,
    tertiary = Violet,
    background = Bg,
    surface = Color.White,
    surfaceVariant = Color(0xFFEEF1F6),
    onSurface = Ink,
    onSurfaceVariant = Muted,
    outline = Line,
    error = Bad
)

private val DarkScheme = darkColorScheme(
    primary = Color(0xFF7EA2FF),
    onPrimary = Color(0xFF0B1526),
    secondary = Sky,
    tertiary = Color(0xFFB79CFF),
    background = DarkBg,
    surface = DarkSurface,
    surfaceVariant = Color(0xFF16233B),
    onSurface = Color(0xFFEAF0FA),
    onSurfaceVariant = Color(0xFF8A99B5),
    outline = DarkLine,
    error = Color(0xFFFF9D7A)
)

@Composable
fun FanzPayTheme(
    darkTheme: Boolean = false, // default Light — sesuai permintaan
    content: @Composable () -> Unit
) {
    MaterialTheme(
        colorScheme = if (darkTheme) DarkScheme else LightScheme,
        typography = androidx.compose.material3.Typography(
            displaySmall = TextStyle(fontSize = 30.sp, fontWeight = FontWeight.ExtraBold, letterSpacing = (-0.5).sp),
            titleLarge = TextStyle(fontSize = 23.sp, fontWeight = FontWeight.ExtraBold),
            titleMedium = TextStyle(fontSize = 17.sp, fontWeight = FontWeight.Bold),
            bodyMedium = TextStyle(fontSize = 14.5.sp, fontWeight = FontWeight.Normal),
            labelSmall = TextStyle(fontSize = 12.sp, fontWeight = FontWeight.SemiBold)
        ),
        content = content
    )
}

/** Format rupiah Indonesia: 125000 -> "Rp 125.000" */
fun rupiah(n: Int): String {
    val neg = n < 0
    val s = kotlin.math.abs(n).toString().reversed().chunked(3).joinToString(".").reversed()
    return (if (neg) "-Rp " else "Rp ") + s
}

fun statusLabel(s: String): String = when (s) {
    "success", "approved" -> "Berhasil"
    "pending" -> "Menunggu"
    "processing" -> "Diproses"
    "failed" -> "Gagal"
    "expired" -> "Kedaluwarsa"
    "refunded" -> "Refund"
    "rejected" -> "Ditolak"
    else -> s
}

fun statusColor(s: String): Color = when (s) {
    "success", "approved" -> Ok
    "pending", "processing" -> Warn
    "failed", "expired", "rejected" -> Bad
    else -> Muted
}

fun kindLabel(k: String): String = when (k) {
    "deposit" -> "Deposit"
    "withdraw" -> "Tarik Dana"
    "transfer" -> "Transfer"
    "ppob" -> "PPOB"
    "refund" -> "Refund"
    else -> k.replaceFirstChar { it.uppercase() }
}
