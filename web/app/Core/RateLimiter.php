<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Rate limiting berbasis database (cPanel-friendly, tanpa Redis).
 * Key: misalnya "login:127.0.0.1", "pin:12", "api:7".
 */
final class RateLimiter {
    /** @return array{allowed: bool, remaining: int, retry_after: int} */
    public static function hit(string $key, int $max, int $windowSeconds): array {
        $now = time();
        $reset = date('Y-m-d H:i:s', $now + $windowSeconds);
        try {
            // Tanpa transaksi eksplisit: REPLACE/UPDATE tunggal sudah atomic,
            // dan fungsi ini sering dipanggil di dalam transaksi bisnis lain.
            $row = DB::one('SELECT hits, reset_at FROM rate_limits WHERE `key` = ?', [$key]);
            if (!$row || strtotime((string)$row['reset_at']) <= $now) {
                DB::exec('REPLACE INTO rate_limits (`key`, hits, reset_at) VALUES (?,?,?)', [$key, 1, $reset]);
                return ['allowed' => true, 'remaining' => $max - 1, 'retry_after' => 0];
            }
            if ((int)$row['hits'] >= $max) {
                return ['allowed' => false, 'remaining' => 0, 'retry_after' => max(1, strtotime((string)$row['reset_at']) - $now)];
            }
            DB::exec('UPDATE rate_limits SET hits = hits + 1 WHERE `key` = ?', [$key]);
            return ['allowed' => true, 'remaining' => $max - (int)$row['hits'] - 1, 'retry_after' => 0];
        } catch (\Throwable $e) {
            return ['allowed' => true, 'remaining' => $max, 'retry_after' => 0];
        }
    }

    public static function clear(string $key): void {
        try { DB::exec('DELETE FROM rate_limits WHERE `key` = ?', [$key]); } catch (\Throwable $e) {}
    }
}
