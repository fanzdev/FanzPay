package com.fanzpay.app.ui.screens

import android.graphics.Bitmap
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Switch
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.asImageBitmap
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.fanzpay.app.data.ApiClient
import com.fanzpay.app.data.Notif
import com.fanzpay.app.data.PinRequest
import com.fanzpay.app.data.WithdrawRequest
import com.fanzpay.app.ui.components.FpButton
import com.fanzpay.app.ui.components.FpTextField
import com.fanzpay.app.ui.components.GlassCard
import com.fanzpay.app.ui.components.PinPad
import com.fanzpay.app.ui.components.SectionTitle
import com.google.zxing.BarcodeFormat
import com.google.zxing.qrcode.QRCodeWriter
import kotlinx.coroutines.launch

fun qrBitmap(text: String, size: Int = 512): Bitmap {
    val bits = QRCodeWriter().encode(text, BarcodeFormat.QR_CODE, size, size)
    val bmp = Bitmap.createBitmap(size, size, Bitmap.Config.RGB_565)
    for (x in 0 until size) for (y in 0 until size) {
        bmp.setPixel(x, y, if (bits.get(x, y)) android.graphics.Color.BLACK else android.graphics.Color.WHITE)
    }
    return bmp
}

@Composable
fun MyQrScreen() {
    val ctx = LocalContext.current
    var publicId by remember { mutableStateOf("") }
    LaunchedEffect(Unit) {
        publicId = try { ApiClient.service(ctx).me().data?.user?.public_id ?: "" } catch (e: Exception) { "" }
    }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp), horizontalAlignment = Alignment.CenterHorizontally) {
        SectionTitle("QR saya 📷", "Teman scan → saldo masuk. Gratis, tanpa drama.")
        GlassCard(Modifier.fillMaxWidth()) {
            Column(horizontalAlignment = Alignment.CenterHorizontally, modifier = Modifier.fillMaxWidth()) {
                if (publicId.isNotEmpty()) {
                    val bmp = remember(publicId) { qrBitmap("fanzpay://pay/u/$publicId") }
                    Image(bmp.asImageBitmap(), contentDescription = "QR FanzPay", modifier = Modifier.size(240.dp))
                    Spacer(Modifier.height(8.dp))
                    Text("fanzpay://pay/u/$publicId", style = MaterialTheme.typography.bodyMedium)
                } else {
                    Text("Memuat QR…")
                }
            }
        }
    }
}

@Composable
fun WithdrawScreen() {
    val ctx = LocalContext.current
    val scope = rememberCoroutineScope()
    var destId by remember { mutableStateOf("") }
    var dests by remember { mutableStateOf("") }
    var amount by remember { mutableStateOf("") }
    var pin by remember { mutableStateOf("") }
    var msg by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    LaunchedEffect(Unit) {
        dests = try {
            (ApiClient.service(ctx).destinations().data ?: emptyList())
                .joinToString("\n") { "${it.id}. ${it.label} • ${it.bank_name} ${it.bank_account}" }
        } catch (e: Exception) { "Gagal memuat tujuan. Tambah via website ya." }
    }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)) {
        SectionTitle("Tarik dana 🏧", "Ke bank & e-wallet. Jujur soal fee & estimasi.")
        GlassCard(Modifier.fillMaxWidth()) {
            Text(dests.ifBlank { "Belum ada tujuan tersimpan. Tambah dulu via website." })
            Spacer(Modifier.height(8.dp))
            FpTextField(destId, { destId = it.filter(Char::isDigit) }, "ID tujuan", numeric = true)
            FpTextField(amount, { amount = it.filter(Char::isDigit) }, "Nominal (Rp)", numeric = true)
            Text("PIN transaksi", fontWeight = FontWeight.Bold); PinPad(pin) { pin = it }
            if (msg != null) Text(msg!!)
            FpButton("Ajukan penarikan →", loading = loading, enabled = pin.length == 6) {
                scope.launch {
                    loading = true
                    try {
                        val r = ApiClient.service(ctx).withdraw(
                            WithdrawRequest(destId.toIntOrNull() ?: 0, amount.toIntOrNull() ?: 0, pin)
                        )
                        msg = if (r.ok) "Diajukan: ${r.data?.reference_id} ✅" else r.message
                    } catch (e: Exception) { msg = "Gagal mengajukan." }
                    finally { loading = false }
                }
            }
        }
    }
}

@Composable
fun NotificationsScreen() {
    val ctx = LocalContext.current
    var rows by remember { mutableStateOf<List<Notif>>(emptyList()) }
    LaunchedEffect(Unit) {
        rows = try { ApiClient.service(ctx).notifications().data ?: emptyList() } catch (e: Exception) { emptyList() }
    }
    LazyColumn(Modifier.fillMaxSize().padding(16.dp)) {
        item { SectionTitle("Notifikasi 🔔", "Kabar real dari akunmu.") }
        if (rows.isEmpty()) {
            item { GlassCard(Modifier.fillMaxWidth()) { Text("Hening itu tenang. Belum ada notifikasi.") } }
        }
        items(rows) { n ->
            GlassCard(Modifier.padding(vertical = 4.dp).fillMaxWidth()) {
                Text(n.title, fontWeight = FontWeight.Bold); Text(n.body)
            }
        }
    }
}

@Composable
fun ProfileScreen(onLogout: () -> Unit, darkMode: Boolean, onToggleTheme: (Boolean) -> Unit) {
    val ctx = LocalContext.current
    val scope = rememberCoroutineScope()
    var name by remember { mutableStateOf("") }
    var pin by remember { mutableStateOf("") }
    var msg by remember { mutableStateOf<String?>(null) }
    LaunchedEffect(Unit) {
        name = try { ApiClient.service(ctx).me().data?.user?.name ?: "" } catch (e: Exception) { "" }
    }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)) {
        SectionTitle("Profil 💜", name.ifBlank { "Pelanggan FanzPay" })
        GlassCard(Modifier.fillMaxWidth()) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text(if (darkMode) "Mode gelap 🌙" else "Mode terang ☀️", fontWeight = FontWeight.Bold)
                    Text("Default terang — mata aman, gaya tetap.", style = MaterialTheme.typography.bodyMedium)
                }
                Switch(checked = darkMode, onCheckedChange = onToggleTheme)
            }
        }
        GlassCard(Modifier.fillMaxWidth()) {
            Text("Atur PIN transaksi (6 digit)", fontWeight = FontWeight.Bold)
            Text("PIN beda dari kata sandi. Jangan pakai tanggal lahir ya, bestie.", style = MaterialTheme.typography.bodyMedium)
            PinPad(pin) { pin = it }
            if (msg != null) Text(msg!!)
            FpButton("Simpan PIN", enabled = pin.length == 6) {
                scope.launch {
                    try {
                        val r = ApiClient.service(ctx).setPin(PinRequest(pin))
                        msg = if (r.ok) "PIN tersimpan ✅" else r.message
                    } catch (e: Exception) { msg = "Gagal menyimpan PIN." }
                }
            }
        }
        GlassCard(Modifier.fillMaxWidth()) {
            Text("Keluar", fontWeight = FontWeight.Bold)
            Text("Sampai jumpa. Ceritamu aman tersimpan.", style = MaterialTheme.typography.bodyMedium)
            Spacer(Modifier.height(8.dp))
            FpButton("Keluar →") {
                scope.launch { com.fanzpay.app.data.SessionStore(ctx).clear(); onLogout() }
            }
        }
    }
}
