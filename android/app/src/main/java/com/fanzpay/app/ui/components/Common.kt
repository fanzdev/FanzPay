package com.fanzpay.app.ui.components

import androidx.compose.animation.animateContentSize
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.AssistChip
import androidx.compose.material3.AssistChipDefaults
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardOptions
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.text.input.VisualTransformation
import androidx.compose.ui.unit.dp
import com.fanzpay.app.ui.theme.Brand
import com.fanzpay.app.ui.theme.BrandGradient
import com.fanzpay.app.ui.theme.Muted
import com.fanzpay.app.ui.theme.statusColor
import com.fanzpay.app.ui.theme.statusLabel

@Composable
fun GlassCard(modifier: Modifier = Modifier, content: @Composable () -> Unit) {
    Card(
        modifier = modifier,
        shape = RoundedCornerShape(20.dp),
        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surface.copy(alpha = 0.82)),
        elevation = CardDefaults.cardElevation(defaultElevation = 8.dp)
    ) {
        Column(Modifier.padding(18.dp).animateContentSize()) { content() }
    }
}

@Composable
fun SectionTitle(title: String, sub: String = "") {
    Text(title, style = MaterialTheme.typography.titleMedium, fontWeight = FontWeight.ExtraBold)
    if (sub.isNotBlank()) {
        Spacer(Modifier.height(2.dp))
        Text(sub, style = MaterialTheme.typography.bodyMedium, color = MaterialTheme.colorScheme.onSurfaceVariant)
    }
    Spacer(Modifier.height(10.dp))
}

@Composable
fun FpTextField(
    value: String,
    onValue: (String) -> Unit,
    label: String,
    secret: Boolean = false,
    numeric: Boolean = false
) {
    OutlinedTextField(
        value = value,
        onValueChange = onValue,
        label = { Text(label) },
        singleLine = true,
        visualTransformation = if (secret) PasswordVisualTransformation() else VisualTransformation.None,
        keyboardOptions = KeyboardOptions(keyboardType = if (numeric) KeyboardType.Number else KeyboardType.Text),
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp),
        shape = RoundedCornerShape(14.dp)
    )
}

@Composable
fun FpButton(text: String, onClick: () -> Unit, loading: Boolean = false, enabled: Boolean = true) {
    Button(
        onClick = onClick,
        enabled = enabled && !loading,
        colors = ButtonDefaults.buttonColors(containerColor = Color.Transparent),
        shape = RoundedCornerShape(14.dp),
        contentPadding = androidx.compose.foundation.layout.PaddingValues(0.dp),
        modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp).height(52.dp)
    ) {
        Box(
            Modifier
                .fillMaxWidth()
                .height(52.dp)
                .clip(RoundedCornerShape(14.dp))
                .background(if (enabled && !loading) BrandGradient else Brush.linearGradient(listOf(Color.Gray.copy(alpha = .4f), Color.Gray.copy(alpha = .4f)))),
            contentAlignment = Alignment.Center
        ) {
            if (loading) CircularProgressIndicator(strokeWidth = 2.dp, color = Color.White, modifier = Modifier.padding(2.dp))
            else Text(text, color = Color.White, fontWeight = FontWeight.Bold)
        }
    }
}

@Composable
fun FpGhostButton(text: String, onClick: () -> Unit) {
    androidx.compose.material3.OutlinedButton(
        onClick = onClick,
        shape = RoundedCornerShape(14.dp),
        modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp).height(50.dp)
    ) { Text(text, color = Brand) }
}

@Composable
fun StatusChip(status: String) {
    val c = statusColor(status)
    AssistChip(
        onClick = {},
        label = { Text(statusLabel(status), fontWeight = FontWeight.Bold) },
        colors = AssistChipDefaults.assistChipColors(labelColor = c)
    )
}

@Composable
fun PinPad(value: String, onValue: (String) -> Unit) {
    Column(Modifier.fillMaxWidth()) {
        Text(
            text = "● ".repeat(value.length) + "○ ".repeat(6 - value.length),
            color = Muted,
            modifier = Modifier.fillMaxWidth().padding(8.dp),
            style = MaterialTheme.typography.titleMedium
        )
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceEvenly) {
            (1..9).map { it.toString() }.forEach { d ->
                androidx.compose.material3.TextButton(onClick = { if (value.length < 6) onValue(value + d) }) {
                    Text(d, style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold)
                }
            }
        }
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceEvenly) {
            androidx.compose.material3.TextButton(onClick = { onValue(value.dropLast(1)) }) { Text("⌫") }
            androidx.compose.material3.TextButton(onClick = { if (value.length < 6) onValue(value + "0") }) {
                Text("0", style = MaterialTheme.typography.titleLarge, fontWeight = FontWeight.ExtraBold)
            }
            androidx.compose.material3.TextButton(onClick = { onValue("") }) { Text("C") }
        }
    }
}
