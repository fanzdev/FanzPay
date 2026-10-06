package com.fanzpay.app.data

// Model 1:1 dengan JSON backend web/api/*. Nominal = Int rupiah.
data class ApiWrapper<T>(val ok: Boolean, val data: T? = null, val message: String? = null)

data class LoginData(
    val token: String,
    val user: UserBrief
)

data class UserBrief(
    val id: Int,
    val name: String,
    val email: String,
    val phone: String,
    val role: String,
    val public_id: String
)

data class MeData(
    val user: MeUser,
    val balance: Int,
    val has_pin: Boolean,
    val unread: Int
)

data class MeUser(
    val name: String,
    val email: String,
    val phone: String,
    val role: String,
    val public_id: String
)

data class Product(
    val id: Int,
    val code: String,
    val name: String,
    val cat_code: String,
    val cat_name: String,
    val price: Int
)

data class WalletData(
    val balance: Int,
    val ledger: List<LedgerRow>,
    val page: Int
)

data class LedgerRow(
    val type: String,
    val direction: String,
    val amount: Int,
    val reference_id: String,
    val created_at: String
)

data class TxRow(
    val reference_id: String,
    val kind: String,
    val amount: Int,
    val total: Int,
    val status: String,
    val note: String,
    val created_at: String
)

data class ResolveData(val name: String, val phone: String, val public_id: String)

data class DepositData(
    val reference_id: String,
    val status: String,
    val total_amount: Int,
    val qr_url: String?,
    val payment_url: String?,
    val expired_at: String?
)

data class TransferData(
    val reference_id: String,
    val amount: Int,
    val status: String
)

data class WithdrawalData(
    val reference_id: String,
    val amount: Int,
    val status: String
)

data class Destination(
    val id: Int,
    val label: String,
    val bank_name: String,
    val bank_account: String,
    val bank_holder: String
)

data class Notif(val title: String, val body: String, val created_at: String)

data class LoginRequest(val email: String, val password: String, val device: String = "android")
data class TransferRequest(val lookup: String, val amount: Int, val pin: String)
data class DepositRequest(val amount: Int)
data class WithdrawRequest(val destination_id: Int, val amount: Int, val pin: String)
data class PpobRequest(val code: String, val customer_no: String, val pin: String)
data class PinRequest(val pin: String)
