<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;
use App\Core\Logger;
use App\Core\RateLimiter;

final class PinService {
    public static function has(int $userId): bool {
        return DB::one('SELECT user_id FROM transaction_pins WHERE user_id = ?', [$userId]) !== null;
    }

    public static function set(int $userId, string $pin): void {
        $hash = password_hash($pin, PASSWORD_DEFAULT);
        DB::exec('INSERT INTO transaction_pins (user_id, pin_hash, failed_attempts, locked_until) VALUES (?,?,0,NULL) ON DUPLICATE KEY UPDATE pin_hash=VALUES(pin_hash), failed_attempts=0, locked_until=NULL', [$userId, $hash]);
        Logger::audit($userId, null, 'pin.set', 'transaction_pins', $userId);
    }

    /** @return array{ok:bool, message?:string, retry_after?:int} */
    public static function verify(int $userId, string $pin): array {
        $row = DB::one('SELECT * FROM transaction_pins WHERE user_id = ?', [$userId]);
        if (!$row) return ['ok' => false, 'message' => 'PIN belum diatur. Buat PIN 6 digit dulu.'];
        if (!empty($row['locked_until']) && strtotime((string)$row['locked_until']) > time()) {
            return ['ok' => false, 'message' => 'PIN terkunci sementara. Coba lagi nanti.'];
        }
        $rl = RateLimiter::hit('pin:' . $userId, 5, 300);
        if (!$rl['allowed']) {
            DB::exec('UPDATE transaction_pins SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?', [$userId]);
            Logger::log('security.pin-lock', ['user_id' => $userId]);
            return ['ok' => false, 'message' => 'Terlalu banyak percobaan. PIN dikunci 15 menit.'];
        }
        if (!password_verify($pin, (string)$row['pin_hash'])) {
            $fails = (int)$row['failed_attempts'] + 1;
            $lock = $fails >= 5 ? ', locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)' : '';
            DB::exec("UPDATE transaction_pins SET failed_attempts = ? {$lock} WHERE user_id = ?", [$fails, $userId]);
            Logger::audit($userId, null, 'pin.failed', 'transaction_pins', $userId, 'fail');
            return ['ok' => false, 'message' => 'PIN salah. Sisa percobaan terbatas.'];
        }
        DB::exec('UPDATE transaction_pins SET failed_attempts = 0, locked_until = NULL WHERE user_id = ?', [$userId]);
        RateLimiter::clear('pin:' . $userId);
        return ['ok' => true];
    }
}
