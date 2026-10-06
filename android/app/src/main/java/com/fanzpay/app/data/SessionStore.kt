package com.fanzpay.app.data

import android.content.Context
import androidx.datastore.preferences.core.edit
import androidx.datastore.preferences.core.stringPreferencesKey
import androidx.datastore.preferences.preferencesDataStore
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.flow.map

private val Context.store by preferencesDataStore("fanzpay")

/** Token sesi disimpan di DataStore (bukan SharedPreferences biasa). */
class SessionStore(private val context: Context) {
    private val tokenKey = stringPreferencesKey("api_token")
    private val baseKey = stringPreferencesKey("base_url")
    private val themeKey = stringPreferencesKey("theme_dark")

    suspend fun saveToken(token: String) {
        context.store.edit { it[tokenKey] = token }
        ApiClient.token = token
    }

    suspend fun loadToken(): String? {
        val t = context.store.data.map { it[tokenKey] }.first()
        ApiClient.token = t
        return t
    }

    suspend fun clear() {
        context.store.edit { it.remove(tokenKey) }
        ApiClient.token = null
    }

    suspend fun setBaseUrl(url: String) {
        context.store.edit { it[baseKey] = url }
    }

    suspend fun setDark(dark: Boolean) {
        context.store.edit { it[themeKey] = if (dark) "1" else "0" }
    }

    suspend fun loadDark(): Boolean {
        return try { context.store.data.map { it[themeKey] == "1" }.first() } catch (e: Exception) { false }
    }
}
