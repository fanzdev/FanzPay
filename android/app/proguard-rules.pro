# FanzPay ProGuard — jangan obfuscate model API (Gson) dan Retrofit
-keep class com.fanzpay.app.data.** { *; }
-keepattributes Signature, InnerClasses, EnclosingMethod
-keepclasseswithmembers class * { @retrofit2.http.* <methods>; }
