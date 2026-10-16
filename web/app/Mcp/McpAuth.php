<?php
declare(strict_types=1);

namespace App\Mcp;

use App\Core\Auth;
use App\Core\DB;

/**
 * Autentikasi MCP: Bearer token ber-scope (utama) → sesi web (fallback) → api_tokens (kompat read-only).
 * Identitas SELALU dari server. Argumen user_id dari AI diabaikan.
 * @return array{user:array, source:string, scopes:array<int,string>}|null
 */
final class McpAuth {
    public const PREFIX = 'fp_mcp_';

    public static function fromRequest(): ?array {
        $token = self::bearer();
        if ($token !== '') {
            $found = self::fromMcpToken($token);
            if ($found) return $found;
            // Kompatibilitas: token Android lama hanya boleh read.
            $u = Auth::userFromBearer();
            if ($u && ($u['status'] ?? '') === 'active' && ($u['role'] ?? '') === 'user') {
                return ['user' => $u, 'source' => 'api_token', 'scopes' => ['read']];
            }
            return null;
        }
        // Fallback sesi web (dashboard / curl dengan cookie).
        try {
            $u = Auth::user();
        } catch (\Throwable $e) { $u = null; }
        if ($u && ($u['status'] ?? '') === 'active' && ($u['role'] ?? '') === 'user') {
            return ['user' => $u, 'source' => 'session', 'scopes' => ['read', 'purchase']];
        }
        return null;
    }

    public static function bearer(): string {
        $h = (string)($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($h === '' && function_exists('apache_request_headers')) {
            foreach ((apache_request_headers() ?: []) as $k => $v) {
                if (strtolower((string)$k) === 'authorization') { $h = (string)$v; break; }
            }
        }
        if (str_starts_with($h, 'Bearer ')) return trim(substr($h, 7));
        // Fallback untuk konektor yang hanya bisa query param (didokumentasikan; tetap via HTTPS).
        $q = (string)($_GET['access_token'] ?? $_GET['token'] ?? '');
        return trim($q);
    }

    /** @return array{user:array, source:string, scopes:array<int,string>}|null */
    public static function fromMcpToken(string $token): ?array {
        if (!str_starts_with($token, self::PREFIX) || strlen($token) < 24) return null;
        try {
            $row = DB::one(
                'SELECT t.*, u.* FROM mcp_tokens t JOIN users u ON u.id = t.user_id
                 WHERE t.token_hash = ? AND t.revoked_at IS NULL AND t.expires_at > NOW() AND u.status = ? LIMIT 1',
                [hash('sha256', $token), 'active']
            );
        } catch (\Throwable $e) { return null; }
        if (!$row || ($row['role'] ?? '') !== 'user') return null;
        try { DB::exec('UPDATE mcp_tokens SET last_used_at = NOW() WHERE token_hash = ?', [hash('sha256', $token)]); } catch (\Throwable $e) {}
        $scopes = array_values(array_intersect(
            array_map('trim', explode(',', strtolower((string)($row['scopes'] ?? 'read')))),
            ['read', 'purchase']
        ));
        if ($scopes === []) $scopes = ['read'];
        $user = $row;
        $user['id'] = (int)($row['user_id'] ?? $row['id']);
        return ['user' => $user, 'source' => 'mcp_token', 'scopes' => $scopes];
    }

    /** Terbitkan token baru. Token mentah hanya dikembalikan sekali. */
    public static function issue(int $userId, string $name, string $client, array $scopes): string {
        $scopes = array_values(array_intersect(array_map('strtolower', $scopes), ['read', 'purchase']));
        if ($scopes === []) $scopes = ['read'];
        $token = self::PREFIX . bin2hex(random_bytes(24));
        DB::exec(
            'INSERT INTO mcp_tokens (user_id, name, client, token_hash, scopes, expires_at) VALUES (?,?,?,?,?, DATE_ADD(NOW(), INTERVAL 90 DAY))',
            [$userId, mb_substr($name, 0, 80), mb_substr($client, 0, 80), hash('sha256', $token), implode(',', $scopes)]
        );
        return $token;
    }

    public static function revoke(int $userId, int $tokenId): void {
        DB::exec('UPDATE mcp_tokens SET revoked_at = NOW() WHERE id = ? AND user_id = ?', [$tokenId, $userId]);
    }

    public static function ensureTables(): void {
        $sql = (string)@file_get_contents(fp_base_path('database/migrate_mcp.sql'));
        if ($sql === '') return;
        // Eksekusi per-statement CREATE saja (aman di-rerun); INSERT settings via Settings agar idempoten.
        foreach (preg_split('/;\s*\n/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if (!str_starts_with(strtoupper(ltrim($stmt)), 'CREATE TABLE')) continue;
            try { DB::exec($stmt); } catch (\Throwable $e) {}
        }
        try {
            foreach (['mcp_enabled' => '1', 'mcp_max_per_tx' => '100000', 'mcp_max_daily' => '500000', 'mcp_allowed_categories' => 'pulsa,data,pln,emoney,game', 'mcp_rate_per_min' => '60', 'mcp_prepare_ttl_minutes' => '10'] as $k => $v) {
                DB::exec('INSERT IGNORE INTO system_settings (`key`,`value`) VALUES (?,?)', [$k, $v]);
            }
        } catch (\Throwable $e) {}
    }
}
