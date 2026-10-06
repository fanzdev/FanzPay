package com.fanzpay.app.data

import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Query

interface ApiService {
    @POST("api/auth/login")
    suspend fun login(@Body body: LoginRequest): ApiWrapper<LoginData>

    @GET("api/me")
    suspend fun me(): ApiWrapper<MeData>

    @GET("api/wallet")
    suspend fun wallet(@Query("type") type: String? = null): ApiWrapper<WalletData>

    @GET("api/products")
    suspend fun products(@Query("cat") cat: String? = null): ApiWrapper<List<Product>>

    @GET("api/resolve")
    suspend fun resolve(@Query("q") q: String): ApiWrapper<ResolveData>

    @POST("api/transfer")
    suspend fun transfer(@Body body: TransferRequest): ApiWrapper<TransferData>

    @POST("api/deposit")
    suspend fun deposit(@Body body: DepositRequest): ApiWrapper<DepositData>

    @GET("api/deposit/{ref}")
    suspend fun depositStatus(@retrofit2.http.Path("ref") ref: String): ApiWrapper<DepositData>

    @POST("api/withdraw")
    suspend fun withdraw(@Body body: WithdrawRequest): ApiWrapper<WithdrawalData>

    @GET("api/destinations")
    suspend fun destinations(): ApiWrapper<List<Destination>>

    @POST("api/ppob")
    suspend fun ppob(@Body body: PpobRequest): ApiWrapper<TxRow>

    @GET("api/transactions")
    suspend fun transactions(): ApiWrapper<List<TxRow>>

    @GET("api/notifications")
    suspend fun notifications(): ApiWrapper<List<Notif>>

    @POST("api/pin")
    suspend fun setPin(@Body body: PinRequest): ApiWrapper<Any?>
}
