package com.fanzpay.app.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.fanzpay.app.data.ApiClient
import com.fanzpay.app.data.MeData
import com.fanzpay.app.data.TxRow
import com.fanzpay.app.ui.components.GlassCard
import com.fanzpay.app.ui.components.SectionTitle
import com.fanzpay.app.ui.components.StatusChip
import com.fanzpay.app.ui.theme.BrandGradient
import com.fanzpay.app.ui.theme.kindLabel
import com.fanzpay.app.ui.theme.rupiah

@Composable
fun HomeScreen(
    onDeposit: () -> Unit,
    onTransfer: () -> Unit,
    onWithdraw: () -> Unit,
    onHistory: () -> Unit,
    onProducts: () -> Unit,
    onQr: () -> Unit
) {
    val ctx = LocalContext.current
    var me by remember { mutableStateOf<MeData?>(null) }
    var recent by remember { mutableStateOf<List<TxRow>>(emptyList()) }
    var err by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(Unit) {
        try {
            me = ApiClient.service(ctx).me().data
            recent = ApiClient.service(ctx).transactions().data ?: emptyList()
        } catch (e: Exception) { err = "Gagal memuat data. Tarik untuk coba lagi." }
    }

    LazyColumn(Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(14.dp)) {
        item {
            Box(
                Modifier
                    .fillMaxWidth()
                    .clip(RoundedCornerShape(24.dp))
                    .background(BrandGradient)
                    .padding(22.dp)
            ) {
                Column {
                    Text(
                        "Halo, ${me?.user?.name ?: "pejuang"} 💜",
                        color = Color.White.copy(alpha = .85)
                    )
                    Text(
                        rupiah(me?.balance ?: 0),
                        style = MaterialTheme.typography.displaySmall,
                        color = Color.White,
                        fontWeight = FontWeight.ExtraBold
                    )
                    Text("Pelan tapi pasti — setiap rupiah tercatat.", color = Color.White.copy(alpha = .8))
                    if (me?.has_pin == false) {
                        Spacer(Modifier.height(8.dp))
                        Text(
                            "🔐 Buat PIN di menu Profil biar bisa gas transaksi.",
                            color = Color(0xFFF0DDA3),
                            fontWeight = FontWeight.Bold
                        )
                    }
                    if (err != null) Text(err!!, color = Color(0xFFF0DDA3))
                }
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                listOf(
                    "⬇\nDeposit" to onDeposit,
                    "🔄\nTransfer" to onTransfer,
                    "🏧\nWithdraw" to onWithdraw,
                    "🧾\nRiwayat" to onHistory
                ).forEach { (label, fn) ->
                    Card(
                        onClick = fn,
                        modifier = Modifier.weight(1f),
                        shape = RoundedCornerShape(18.dp),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface.copy(alpha = .9)),
                        elevation = CardDefaults.cardElevation(6.dp)
                    ) {
                        Text(label, modifier = Modifier.padding(14.dp), fontWeight = FontWeight.Bold)
                    }
                }
            }
        }
        item {
            Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                Card(onClick = onProducts, modifier = Modifier.weight(1f), shape = RoundedCornerShape(18.dp)) {
                    Column(Modifier.padding(16.dp)) {
                        Text("🛍️ Beli Produk", fontWeight = FontWeight.ExtraBold)
                        Text("Pulsa, data, PLN, game", style = MaterialTheme.typography.bodyMedium, color = Color.Gray)
                    }
                }
                Card(onClick = onQr, modifier = Modifier.weight(1f), shape = RoundedCornerShape(18.dp)) {
                    Column(Modifier.padding(16.dp)) {
                        Text("📷 QR Saya", fontWeight = FontWeight.ExtraBold)
                        Text("Terima saldo Rp0", style = MaterialTheme.typography.bodyMedium, color = Color.Gray)
                    }
                }
            }
        }
        item { SectionTitle("Terakhir", "Aktivitas real dari akunmu — tanpa data palsu.") }
        if (recent.isEmpty()) {
            item {
                GlassCard(Modifier.fillMaxWidth()) {
                    Text("Belum ada transaksi 🌱", fontWeight = FontWeight.ExtraBold)
                    Text("Deposit pertamamu adalah awal cerita. Gas dari nominal kecil dulu.")
                }
            }
        }
        items(recent.take(6)) { t ->
            GlassCard(Modifier.fillMaxWidth()) {
                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                    Column(Modifier.weight(1f)) {
                        Text("${kindLabel(t.kind)} • ${t.reference_id}", fontWeight = FontWeight.Bold)
                        Text(t.created_at, color = Color.Gray)
                    }
                    Column { Text(rupiah(t.total), fontWeight = FontWeight.ExtraBold); StatusChip(t.status) }
                }
            }
        }
    }
}

@Composable
fun HistoryScreen() {
    val ctx = LocalContext.current
    var rows by remember { mutableStateOf<List<TxRow>>(emptyList()) }
    LaunchedEffect(Unit) {
        rows = try { ApiClient.service(ctx).transactions().data ?: emptyList() } catch (e: Exception) { emptyList() }
    }
    LazyColumn(Modifier.fillMaxSize().padding(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
        item { Text("Riwayat transaksi 🧾", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold); Spacer(Modifier.height(2.dp)); Text("Semua real, semua tercatat.") }
        if (rows.isEmpty()) {
            item { GlassCard(Modifier.fillMaxWidth()) { Text("Kosong itu jujur. Belum ada transaksi di akun ini.") } }
        }
        items(rows) { t ->
            GlassCard(Modifier.fillMaxWidth()) {
                Text(t.reference_id, color = Color.Gray)
                Text("${kindLabel(t.kind)} — ${rupiah(t.total)}", fontWeight = FontWeight.Bold)
                StatusChip(t.status)
            }
        }
    }
}
