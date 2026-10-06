package com.fanzpay.app.data

import android.content.Context
import com.fanzpay.app.BuildConfig
import com.google.gson.GsonBuilder
import java.util.UUID
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory

/** Retrofit + Bearer token + idempotency key per request finansial. */
object ApiClient {
    @Volatile var token: String? = null

    private fun client(): OkHttpClient {
        val log = HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BASIC }
        return OkHttpClient.Builder()
            .addInterceptor(Interceptor { chain ->
                val b = chain.request().newBuilder()
                token?.let { b.addHeader("Authorization", "Bearer $it") }
                val path = chain.request().url.encodedPath
                if (path.contains("/api/transfer") || path.contains("/api/deposit") ||
                    path.contains("/api/withdraw") || path.contains("/api/ppob")
                ) {
                    b.addHeader("X-Idempotency-Key", UUID.randomUUID().toString())
                }
                chain.proceed(b.build())
            })
            .addInterceptor(log)
            .build()
    }

    fun service(context: Context): ApiService {
        var base = context.getSharedPreferences("fp_cfg", Context.MODE_PRIVATE)
            .getString("base_url", null) ?: BuildConfig.API_BASE_URL
        if (!base.endsWith("/")) base += "/"
        val gson = GsonBuilder().create()
        return Retrofit.Builder()
            .baseUrl(base)
            .client(client())
            .addConverterFactory(GsonConverterFactory.create(gson))
            .build()
            .create(ApiService::class.java)
    }
}
