# Folder APK Android — FanzPay

Upload file build resmi ke sini dengan nama persis:

  web/public/assets/apk/fanzpay.apk

Cara build (Android Studio):
  1. Buka folder `android/`
  2. Build → Build App Bundle(s)/APK(s) → Build APK(s)
  3. Ambil `android/app/build/outputs/apk/debug/app-debug.apk`
     (atau versi release dari Generate Signed APK)
  4. Rename → `fanzpay.apk`, upload ke folder ini.

Setelah file ada:
  - Landing otomatis menampilkan ukuran asli + tombol unduh 1-klik.
  - Route `GET /download-apk` langsung mengirim file
    (header application/vnd.android.package-archive).
  - Route `GET /download-apk?info=1` menampilkan halaman info.

Selama file belum ada, halaman `/download-apk` menampilkan panduan
build secara jujur — tanpa link palsu.
