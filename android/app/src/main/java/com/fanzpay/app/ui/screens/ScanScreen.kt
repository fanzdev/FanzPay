package com.fanzpay.app.ui.screens

import android.Manifest
import android.content.pm.PackageManager
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.camera.core.CameraSelector
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.Preview
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.platform.LocalLifecycleOwner
import androidx.compose.ui.unit.dp
import androidx.compose.ui.viewinterop.AndroidView
import androidx.core.content.ContextCompat
import com.google.mlkit.vision.barcode.BarcodeScanning
import com.google.mlkit.vision.common.InputImage
import java.util.concurrent.Executors

/**
 * Scan QR FanzPay (fanzpay://pay/u/{id}) → lanjut ke Transfer terisi otomatis.
 * Catatan: tidak ada secret di aplikasi; token hanya Bearer sesi user.
 */
@Composable
fun ScanScreen(onFound: (String) -> Unit) {
    val ctx = LocalContext.current
    var granted by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(ctx, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED
        )
    }
    val launcher = rememberLauncherForActivityResult(ActivityResultContracts.RequestPermission()) {
        granted = it
    }
    var locked by remember { mutableStateOf(false) }
    Column(Modifier.fillMaxSize().padding(20.dp)) {
        Text("Scan QR FanzPay", style = MaterialTheme.typography.titleLarge)
        if (!granted) {
            Spacer(Modifier.height(8.dp))
            Text("Izin kamera diperlukan untuk scan QR.")
            Button(onClick = { launcher.launch(Manifest.permission.CAMERA) }) { Text("Izinkan kamera") }
            return@Column
        }
        CameraPreview(onCode = { code ->
            if (!locked && code.startsWith("fanzpay://pay/u/")) {
                locked = true
                onFound(code.removePrefix("fanzpay://pay/u/"))
            }
        })
    }
}

@Composable
private fun CameraPreview(onCode: (String) -> Unit) {
    val owner = LocalLifecycleOwner.current
    AndroidView(
        factory = { c ->
            val view = PreviewView(c)
            val future = ProcessCameraProvider.getInstance(c)
            future.addListener({
                val provider = future.get()
                val preview = Preview.Builder().build().also { it.setSurfaceProvider(view.surfaceProvider) }
                val analysis = ImageAnalysis.Builder()
                    .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST).build()
                val scanner = BarcodeScanning.getClient()
                analysis.setAnalyzer(Executors.newSingleThreadExecutor()) { proxy ->
                    val media = proxy.image
                    if (media != null) {
                        val input = InputImage.fromMediaImage(media, proxy.imageInfo.rotationDegrees)
                        scanner.process(input)
                            .addOnSuccessListener { codes ->
                                codes.firstOrNull()?.rawValue?.let(onCode)
                            }
                            .addOnCompleteListener { proxy.close() }
                    } else proxy.close()
                }
                provider.unbindAll()
                provider.bindToLifecycle(owner, CameraSelector.DEFAULT_BACK_CAMERA, preview, analysis)
            }, ContextCompat.getMainExecutor(c))
            view
        },
        modifier = Modifier.fillMaxSize()
    )
}
