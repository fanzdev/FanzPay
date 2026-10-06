<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/**
 * Wallet + ledger append-only. Semua perubahan saldo lewat sini,
 * selalu dalam DB transaction + SELECT ... FOR UPDATE.
 */
final class WalletService {
    public static function getOrCreate(int $userId): array {
        $w = DB::one('SELECT * FROM wallets WHERE user_id = ?', [$userId]);
        if ($w) return $w;
        DB::exec('INSERT IGNORE INTO wallets (user_id, balance, locked) VALUES (?,?,?)', [$userId, 0, 0]);
        return DB::one('SELECT * FROM wallets WHERE user_id = ?', [$userId]);
    }

    public static function balance(int $userId): int {
        return (int)(self::getOrCreate($userId)['balance'] ?? 0);
    }

    /**
     * Tulis satu baris ledger. Harus dipanggil di dalam transaksi aktif
     * setelah wallet di-lock. Mengembalikan balance_after.
     */
    public static function append(int $userId, string $txnId, string $ref, string $type, string $direction, int $amount, array $opt = []): int {
        $pdo = DB::pdo();
        $st = $pdo->prepare('SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE');
        $st->execute([$userId]);
        $row = $st->fetch();
        if (!$row) {
            $pdo->prepare('INSERT INTO wallets (user_id,balance,locked) VALUES (?,?,?)')->execute([$userId, 0, 0]);
            $before = 0;
        } else {
            $before = (int)$row['balance'];
        }
        $after = $direction === 'credit' ? $before + $amount : $before - $amount;
        if ($after < 0) throw new \RuntimeException('Saldo tidak mencukupi.');
        $pdo->prepare('UPDATE wallets SET balance = ? WHERE user_id = ?')->execute([$after, $userId]);
        $pdo->prepare('INSERT INTO wallet_ledger (transaction_id, reference_id, user_id, type, direction, amount, balance_before, balance_after, source, destination, status, provider_txn_id, metadata) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
            $txnId, $ref, $userId, $type, $direction, $amount, $before, $after,
            $opt['source'] ?? 'wallet', $opt['destination'] ?? 'wallet',
            $opt['status'] ?? 'success', $opt['provider_txn_id'] ?? null,
            isset($opt['metadata']) ? json_encode($opt['metadata'], JSON_UNESCAPED_UNICODE) : null,
        ]);
        return $after;
    }

    /** Rekonstruksi saldo dari ledger (untuk rekonsiliasi). */
    public static function rebuild(int $userId): int {
        $rows = DB::all('SELECT direction, amount FROM wallet_ledger WHERE user_id = ? AND status = ? ORDER BY id', [$userId, 'success']);
        $bal = 0;
        foreach ($rows as $r) $bal += $r['direction'] === 'credit' ? (int)$r['amount'] : -(int)$r['amount'];
        return $bal;
    }

    /** @return array<int,array> */
    public static function history(int $userId, string $type = '', int $limit = 20, int $offset = 0): array {
        if ($type !== '') {
            return DB::all('SELECT * FROM wallet_ledger WHERE user_id = ? AND type = ? ORDER BY id DESC LIMIT ? OFFSET ?', [$userId, $type, $limit, $offset]);
        }
        return DB::all('SELECT * FROM wallet_ledger WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?', [$userId, $limit, $offset]);
    }
}
