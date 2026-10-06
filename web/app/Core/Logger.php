<?php
declare(strict_types=1);

namespace App\Core;

/** File logger terstruktur. TIDAK PERNAH log password/PIN/secret/OTP. */
final class Logger {
    public static function log(string $channel, array $ctx = []): void {
        $dir = fp_base_path('storage/logs');
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $ctx = self::scrub($ctx);
        $line = date('c') . ' [' . $channel . '] ' . json_encode($ctx, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        @file_put_contents($dir . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    /** @param array<string,mixed> $ctx */
    public static function scrub(array $ctx): array {
        $deny = ['password', 'pin', 'secret_token', 'secret', 'signing_secret', 'api_key', 'otp', 'wd_pin', 'authorization', 'token'];
        foreach ($ctx as $k => $v) {
            $lk = strtolower((string)$k);
            foreach ($deny as $d) {
                if (str_contains($lk, $d)) { $ctx[$k] = '[redacted]'; break; }
            }
            if (is_array($v)) $ctx[$k] = self::scrub($v);
        }
        return $ctx;
    }

    public static function audit(?int $actorId, ?string $actorRole, string $action, ?string $target = null, $targetId = null, string $result = 'ok', array $meta = []): void {
        try {
            DB::exec(
                'INSERT INTO audit_logs (actor_id, actor_role, action, target, target_id, ip, user_agent, result, metadata, created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())',
                [$actorId, $actorRole, $action, $target, $targetId !== null ? (string)$targetId : null, client_ip(), substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255), $result, json_encode(self::scrub($meta), JSON_UNESCAPED_UNICODE)]
            );
        } catch (\Throwable $e) {
            self::log('audit-failed', ['action' => $action]);
        }
    }
}
