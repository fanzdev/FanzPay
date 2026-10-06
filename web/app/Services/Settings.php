<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

final class Settings {
    /** @var array<string,string>|null */
    private static ?array $cache = null;

    public static function get(string $key, string $default = ''): string {
        if (self::$cache === null) self::all();
        return self::$cache[$key] ?? $default;
    }

    /** @return array<string,string> */
    public static function all(): array {
        if (self::$cache !== null) return self::$cache;
        try {
            $rows = DB::all('SELECT `key`,`value` FROM system_settings');
        } catch (\Throwable $e) { return self::$cache = []; }
        $out = [];
        foreach ($rows as $r) $out[$r['key']] = (string)$r['value'];
        return self::$cache = $out;
    }

    public static function set(string $key, string $value, ?int $by = null): void {
        DB::exec('INSERT INTO system_settings (`key`,`value`,updated_by) VALUES (?,?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`), updated_by=VALUES(updated_by)', [$key, $value, $by]);
        self::$cache = null;
    }

    public static function int(string $key, int $default): int {
        $v = self::get($key, '');
        return $v === '' ? $default : (int)$v;
    }

    public static function maintenance(): bool {
        return self::get('maintenance_mode', '0') === '1';
    }
}
