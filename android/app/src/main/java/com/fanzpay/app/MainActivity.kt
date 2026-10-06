package com.fanzpay.app

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.AccountCircle
import androidx.compose.material.icons.filled.DateRange
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material.icons.filled.SwapHoriz
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.navigation.NavType
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import androidx.navigation.navArgument
import androidx.navigation.navDeepLink
import com.fanzpay.app.data.Product
import com.fanzpay.app.data.SessionStore
import com.fanzpay.app.ui.screens.DepositScreen
import com.fanzpay.app.ui.screens.DepositStatusScreen
import com.fanzpay.app.ui.screens.HistoryScreen
import com.fanzpay.app.ui.screens.HomeScreen
import com.fanzpay.app.ui.screens.LoginScreen
import com.fanzpay.app.ui.screens.MyQrScreen
import com.fanzpay.app.ui.screens.NotificationsScreen
import com.fanzpay.app.ui.screens.ProductBuyScreen
import com.fanzpay.app.ui.screens.ProductsScreen
import com.fanzpay.app.ui.screens.ProfileScreen
import com.fanzpay.app.ui.screens.ScanScreen
import com.fanzpay.app.ui.screens.TransferScreen
import com.fanzpay.app.ui.screens.WithdrawScreen
import com.fanzpay.app.ui.theme.FanzPayTheme
import kotlinx.coroutines.launch

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { Root() }
    }
}

private data class Tab(val route: String, val label: String, val icon: ImageVector)

private val TABS = listOf(
    Tab("home", "Beranda", Icons.Filled.Home),
    Tab("products", "Produk", Icons.Filled.ShoppingCart),
    Tab("transfer", "Transfer", Icons.Filled.SwapHoriz),
    Tab("history", "Riwayat", Icons.Filled.DateRange),
    Tab("profile", "Profil", Icons.Filled.AccountCircle)
)

@Composable
private fun Root() {
    val nav = rememberNavController()
    val ctx = androidx.compose.ui.platform.LocalContext.current
    val scope = rememberCoroutineScope()
    var authed by remember { mutableStateOf<Boolean?>(null) }
    var picked by remember { mutableStateOf<Product?>(null) }
    var dark by remember { mutableStateOf(false) } // default Light
    val back by nav.currentBackStackEntryAsState()
    val route = back?.destination?.route ?: "home"

    LaunchedEffect(Unit) {
        dark = try { SessionStore(ctx).loadDark() } catch (e: Exception) { false }
        authed = try {
            SessionStore(ctx).loadToken() != null
        } catch (e: Exception) { false }
    }

    FanzPayTheme(darkTheme = dark) {
        if (authed == null) return@FanzPayTheme
        if (authed == false) {
            LoginScreen(onLoggedIn = { authed = true }, onRegisterHint = {})
            return@FanzPayTheme
        }

        Scaffold(
            bottomBar = {
                NavigationBar {
                    TABS.forEach { t ->
                        NavigationBarItem(
                            selected = route.startsWith(t.route),
                            onClick = { nav.navigate(t.route) { launchSingleTop = true } },
                            icon = { Icon(t.icon, contentDescription = t.label) },
                            label = { Text(t.label) }
                        )
                    }
                }
            }
        ) { pad ->
            NavHost(nav, startDestination = "home", modifier = Modifier.padding(pad)) {
                composable("home") {
                    HomeScreen(
                        onDeposit = { nav.navigate("deposit") },
                        onTransfer = { nav.navigate("transfer") },
                        onWithdraw = { nav.navigate("withdraw") },
                        onHistory = { nav.navigate("history") },
                        onProducts = { nav.navigate("products") },
                        onQr = { nav.navigate("qr") }
                    )
                }
                composable("products") { ProductsScreen(onBuy = { picked = it; nav.navigate("buy") }) }
                composable("buy") {
                    val p = picked
                    if (p == null) nav.popBackStack()
                    else ProductBuyScreen(p) { nav.navigate("history") { popUpTo("home") } }
                }
                composable(
                    "transfer?to={to}",
                    arguments = listOf(navArgument("to") { type = NavType.StringType; defaultValue = "" }),
                    deepLinks = listOf(navDeepLink { uriPattern = "fanzpay://pay/u/{to}" })
                ) { e ->
                    TransferScreen(e.arguments?.getString("to") ?: "") { nav.navigate("history") { popUpTo("home") } }
                }
                composable("deposit") { DepositScreen(onCreated = { nav.navigate("deposit_status/$it") }) }
                composable("deposit_status/{ref}") { e ->
                    DepositStatusScreen(e.arguments?.getString("ref") ?: "")
                }
                composable("withdraw") { WithdrawScreen() }
                composable("history") { HistoryScreen() }
                composable("qr") { MyQrScreen() }
                composable("scan") { ScanScreen(onFound = { nav.navigate("transfer?to=$it") }) }
                composable("notif") { NotificationsScreen() }
                composable("profile") {
                    ProfileScreen(
                        onLogout = { authed = false },
                        darkMode = dark,
                        onToggleTheme = { v ->
                            dark = v
                            scope.launch { try { SessionStore(ctx).setDark(v) } catch (_: Exception) {} }
                        }
                    )
                }
            }
        }
    }
}
