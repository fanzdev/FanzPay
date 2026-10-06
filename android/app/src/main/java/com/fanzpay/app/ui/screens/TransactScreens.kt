package com.fanzpay.app.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.platform.LocalContext
import com.fanzpay.app.data.ApiClient
import com.fanzpay.app.data.DepositRequest
import com.fanzpay.app.data.PpobRequest
import com.fanzpay.app.data.Product
import com.fanzpay.app.data.TransferRequest
import com.fanzpay.app.ui.components.FpButton
import com.fanzpay.app.ui.components.FpTextField
import com.fanzpay.app.ui.components.GlassCard
import com.fanzpay.app.ui.components.PinPad
import com.fanzpay.app.ui.components.SectionTitle
import com.fanzpay.app.ui.components.StatusChip
import com.fanzpay.app.ui.theme.rupiah
import kotlinx.coroutines.launch

@Composable
fun ProductsScreen(onBuy: (Product) -> Unit) {
    val ctx = LocalContext.current
    var items by remember { mutableStateOf<List<Product>>(emptyList()) }
    var loading by remember { mutableStateOf(true) }
    LaunchedEffect(Unit) {
        items = try { ApiClient.service(ctx).products().data ?: emptyList() } catch (e: Exception) { emptyList() }
        loading = false
    }
    LazyColumn(Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
        item { SectionTitle("Produk digital 🛍️", "Katalog live dari provider resmi — tanpa harga palsu.") }
        if (!loading && items.isEmpty()) {
            item { GlassCard(Modifier.fillMaxWidth()) { Text("Katalog lagi disiapkan 🌱", fontWeight = FontWeight.ExtraBold); Text("Admin belum sinkron. Balik lagi bentar — kami anti pajang harga palsu.") } }
        }
        items(items) { p ->
            GlassCard(Modifier.fillMaxWidth()) {
                Text(p.name, fontWeight = FontWeight.ExtraBold)
                Text("${p.cat_name} • ${rupiah(p.price)}", color = androidx.compose.ui.graphics.Color.Gray)
                Spacer(Modifier.height(8.dp))
                FpButton("Beli →") { onBuy(p) }
            }
        }
    }
}

@Composable
fun ProductBuyScreen(product: Product, onDone: () -> Unit) {
    val ctx = LocalContext.current
    val scope = rememberCoroutineScope()
    var target by remember { mutableStateOf("") }
    var pin by remember { mutableStateOf("") }
    var msg by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)) {
        SectionTitle(product.name, rupiah(product.price) + " • harga final, no hidden fee.")
        GlassCard(Modifier.fillMaxWidth()) {
            FpTextField(target, { target = it }, "Nomor tujuan", numeric = true)
            Text("PIN transaksi (6 digit)", fontWeight = FontWeight.Bold); PinPad(pin) { pin = it }
            if (msg != null) Text(msg!!)
            FpButton("Bayar ${rupiah(product.price)}", loading = loading, enabled = target.isNotBlank() && pin.length == 6) {
                scope.launch {
                    loading = true
                    try {
                        val r = ApiClient.service(ctx).ppob(PpobRequest(product.code, target.trim(), pin))
                        msg = if (r.ok) "Diproses: ${r.data?.reference_id} 🎉" else r.message
                        if (r.ok) onDone()
                    } catch (e: Exception) { msg = "Gagal menghubungi server." }
                    finally { loading = false }
                }
            }
        }
    }
}

@Composable
fun TransferScreen(prefill: String, onDone: () -> Unit) {
    val ctx = LocalContext.current
    val scope = rememberCoroutineScope()
    var lookup by remember { mutableStateOf(prefill) }
    var amount by remember { mutableStateOf("") }
    var pin by remember { mutableStateOf("") }
    var peer by remember { mutableStateOf<String?>(null) }
    var msg by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)) {
        SectionTitle("Transfer gratis 🔄", "Rp0 ke sesama. Gagal di satu sisi, batal semua — fair.")
        GlassCard(Modifier.fillMaxWidth()) {
            FpTextField(lookup, { lookup = it; peer = null }, "Nomor HP / ID penerima")
            FpButton("Cek penerima") {
                scope.launch {
                    try {
                        val r = ApiClient.service(ctx).resolve(lookup.trim())
                        peer = if (r.ok) r.data?.name else null
                        if (!r.ok) msg = r.message
                    } catch (e: Exception) { msg = "Gagal cek penerima." }
                }
            }
            if (peer != null) Text("Penerima: $peer 💜", fontWeight = FontWeight.Bold)
            FpTextField(amount, { amount = it.filter(Char::isDigit) }, "Nominal (Rp)", numeric = true)
            Text("PIN transaksi", fontWeight = FontWeight.Bold); PinPad(pin) { pin = it }
            if (msg != null) Text(msg!!)
            FpButton("Kirim →", loading = loading, enabled = pin.length == 6 && (amount.toIntOrNull() ?: 0) > 0) {
                scope.launch {
                    loading = true
                    try {
                        val r = ApiClient.service(ctx).transfer(TransferRequest(lookup.trim(), amount.toInt(), pin))
                        msg = if (r.ok) "Terkirim: ${r.data?.reference_id} 🚀" else r.message
                        if (r.ok) onDone()
                    } catch (e: Exception) { msg = "Gagal mengirim." }
                    finally { loading = false }
                }
            }
        }
    }
}

@Composable
fun DepositScreen(onCreated: (String) -> Unit) {
    val ctx = LocalContext.current
    val scope = rememberCoroutineScope()
    var amount by remember { mutableStateOf("") }
    var msg by remember { mutableStateOf<String?>(null) }
    var loading by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxSize().padding(20.dp)) {
        SectionTitle("Deposit via QRIS ⚡", "Real-time, terverifikasi webhook. Mulai dari Rp10.000.")
        GlassCard(Modifier.fillMaxWidth()) {
            FpTextField(amount, { amount = it.filter(Char::isDigit) }, "Nominal (min 10000)", numeric = true)
            if (msg != null) Text(msg!!)
            FpButton("Buat QRIS →", loading = loading) {
                scope.launch {
                    loading = true
                    try {
                        val r = ApiClient.service(ctx).deposit(DepositRequest(amount.toIntOrNull() ?: 0))
                        if (r.ok && r.data != null) onCreated(r.data.reference_id) else msg = r.message
                    } catch (e: Exception) { msg = "Gagal membuat deposit." }
                    finally { loading = false }
                }
            }
        }
    }
}

@Composable
fun DepositStatusScreen(ref: String) {
    val ctx = LocalContext.current
    var status by remember { mutableStateOf("pending") }
    var total by remember { mutableStateOf(0) }
    LaunchedEffect(ref) {
        while (true) {
            try {
                val d = ApiClient.service(ctx).depositStatus(ref).data
                if (d != null) { status = d.status; total = d.total_amount }
                if (status != "pending") break
            } catch (e: Exception) { }
            kotlinx.coroutines.delay(25_000)
        }
    }
    Column(Modifier.fillMaxSize().padding(20.dp)) {
        SectionTitle("Deposit $ref", "Total bayar: ${rupiah(total)}")
        GlassCard(Modifier.fillMaxWidth()) {
            StatusChip(status)
            Spacer(Modifier.height(8.dp))
            Text("Status diperbarui otomatis tiap 25 detik. Sumber kebenaran tetap webhook — santai, sistem yang nungguin.")
        }
    }
}
