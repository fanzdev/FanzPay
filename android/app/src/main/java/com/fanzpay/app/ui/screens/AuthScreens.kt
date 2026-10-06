package com.fanzpay.app.ui.screens

import androidx.compose.animation.AnimatedVisibility
import androidx.compose.animation.fadeIn
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import com.fanzpay.app.data.ApiClient
import com.fanzpay.app.data.LoginRequest
import com.fanzpay.app.data.SessionStore
import com.fanzpay.app.ui.components.FpButton
import com.fanzpay.app.ui.components.FpTextField
import com.fanzpay.app.ui.components.GlassCard
import com.fanzpay.app.ui.theme.BrandGradient
import kotlinx.coroutines.launch

@Composable
fun LoginScreen(onLoggedIn: () -> Unit, onRegisterHint: () -> Unit) {
    val ctx = LocalContext.current
    val scope = rememberCoroutineScope()
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var baseUrl by remember { mutableStateOf("") }
    var loading by remember { mutableStateOf(false) }
    var error by remember { mutableStateOf<String?>(null) }

    Column(
        Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(22.dp),
        horizontalAlignment = Alignment.CenterHorizontally
    ) {
        Spacer(Modifier.height(18.dp))
        Box(
            Modifier
                .fillMaxWidth()
                .clip(RoundedCornerShape(24.dp))
                .background(BrandGradient)
                .padding(22.dp)
        ) {
            Column {
                Text("💜", style = MaterialTheme.typography.displaySmall)
                Text(
                    "Uangmu, ceritamu.",
                    style = MaterialTheme.typography.titleLarge,
                    color = Color.White,
                    fontWeight = FontWeight.ExtraBold
                )
                Text(
                    "Masuk dan lanjutkan hustle-mu. Pelan tapi pasti.",
                    color = Color.White.copy(alpha = .85)
                )
            }
        }
        Spacer(Modifier.height(16.dp))
        GlassCard(Modifier.fillMaxWidth()) {
            Text("Masuk ke FanzPay", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold)
            Text(
                "Khusus pelanggan. Admin & super admin masuk via website ya — biar aman.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant
            )
            Spacer(Modifier.height(12.dp))
            AnimatedVisibility(visible = error != null, enter = fadeIn()) {
                if (error != null) {
                    Text(error!!, color = Color(0xFFC2410C), fontWeight = FontWeight.Bold)
                    Spacer(Modifier.height(6.dp))
                }
            }
            FpTextField(email, { email = it }, "Email")
            FpTextField(password, { password = it }, "Kata sandi", secret = true)
            FpTextField(baseUrl, { baseUrl = it }, "Server (opsional, cth. https://domainmu/)")
            FpButton("Masuk →", loading = loading) {
                scope.launch {
                    loading = true; error = null
                    try {
                        if (baseUrl.isNotBlank()) SessionStore(ctx).setBaseUrl(baseUrl.trim())
                        val res = ApiClient.service(ctx).login(LoginRequest(email.trim(), password))
                        if (res.ok && res.data != null) {
                            SessionStore(ctx).saveToken(res.data.token)
                            onLoggedIn()
                        } else error = res.message ?: "Gagal masuk. Cek email & kata sandi."
                    } catch (e: Exception) {
                        error = "Tidak dapat menghubungi server. Cek koneksi & alamat server."
                    } finally { loading = false }
                }
            }
            androidx.compose.material3.TextButton(onClick = onRegisterHint) {
                Text("Belum punya akun? Daftar gratis via website")
            }
        }
        Spacer(Modifier.height(10.dp))
        Text(
            "🛡️ PIN 6 digit · Ledger auditable · Transfer Rp0",
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant
        )
    }
}
