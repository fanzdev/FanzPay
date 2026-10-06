<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/** Koneksi PDO singleton + helper transaksi. */
final class DB {
    private static ?PDO $pdo = null;

    public static function pdo(): PDO {
        if (self::$pdo instanceof PDO) return self::$pdo;
        $host = config('db.host');
        $port = (int)config('db.port');
        $name = config('db.name');
        $user = config('db.user');
        $pass = (string)config('db.pass');
        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (config('app.debug')) {
                http_response_code(500);
                echo 'Koneksi database gagal. Periksa web/.env lalu import web/database/schema.sql';
                exit;
            }
            error_log('[fanzpay] db connect failed');
            http_response_code(500);
            echo 'Layanan sedang diproses. Silakan coba lagi.';
            exit;
        }
        return self::$pdo;
    }

    public static function one(string $sql, array $p = []): ?array {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        $r = $st->fetch();
        return $r === false ? null : $r;
    }

    /** @return array<int,array> */
    public static function all(string $sql, array $p = []): array {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function exec(string $sql, array $p = []): int {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        return $st->rowCount();
    }

    public static function lastId(): string {
        return self::pdo()->lastInsertId();
    }

    /** Jalankan closure di dalam DB transaction. Rollback otomatis saat exception. */
    public static function tx(callable $fn) {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $out = $fn($pdo);
            $pdo->commit();
            return $out;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }
}
