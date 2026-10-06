<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;

/**
 * OTP email 6 digit. Di local/XAMPP tanpa SMTP, OTP ditulis ke log +
 * ditampilkan di layar (mode dev) agar alur tetap bisa diuji.
 */
final class OtpService {
    public static function issue(string $email, string $purpose = 'register'): string {
        $otp = (string)random_int(100000, 999999);
        DB::exec(
            "INSERT INTO email_otps (email, otp_hash, purpose, expires_at) VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))",
            [$email, password_hash($otp, PASSWORD_DEFAULT), $purpose]
        );
        Logger::log('otp.issued', ['email' => $email, 'purpose' => $purpose]);
        if (config('app.env') !== 'production') {
            Logger::log('otp.dev', ['email' => $email, 'purpose' => $purpose]);
        }
        // Kirim via mail() bila tersedia; kegagalan tidak menggagalkan alur dev.
        try {
            @mail($email, '[FanzPay] Kode verifikasi', "Kode verifikasi FanzPay: {$otp}\nBerlaku 10 menit.", 'From: ' . config('mail.from'));
        } catch (\Throwable $e) {}
        return $otp;
    }

    /** @return array{ok:bool, message?:string} */
    public static function consume(string $email, string $otp, string $purpose = 'register'): array {
        $row = DB::one('SELECT * FROM email_otps WHERE email = ? AND purpose = ? AND consumed_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1', [$email, $purpose]);
        if (!$row) return ['ok' => false, 'message' => 'Kode tidak valid atau kedaluwarsa.'];
        if ((int)$row['attempts'] >= 5) return ['ok' => false, 'message' => 'Terlalu banyak percobaan. Minta kode baru.'];
        if (!password_verify($otp, (string)$row['otp_hash'])) {
            DB::exec('UPDATE email_otps SET attempts = attempts + 1 WHERE id = ?', [(int)$row['id']]);
            return ['ok' => false, 'message' => 'Kode salah.'];
        }
        DB::exec('UPDATE email_otps SET consumed_at = NOW() WHERE id = ?', [(int)$row['id']]);
        return ['ok' => true];
    }
}
