<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

/** Rekonsiliasi untuk Super Admin: pending lama, mismatch saldo, webhook hilang. */
final class ReconcileService {
    /** @return array<string,mixed> */
    public static function overview(): array {
        $pendingTx = DB::all("SELECT kind, COUNT(*) c FROM transactions WHERE status IN ('pending','processing') GROUP BY kind");
        $oldPending = DB::all("SELECT reference_id, kind, total, created_at FROM transactions WHERE status IN ('pending','processing') AND created_at < DATE_SUB(NOW(), INTERVAL 30 MINUTE) ORDER BY created_at LIMIT 50");
        $wh = DB::all("SELECT provider, event, COUNT(*) c FROM webhook_events WHERE status <> 'processed' GROUP BY provider, event");
        $mismatch = [];
        foreach (DB::all('SELECT user_id, balance FROM wallets LIMIT 500') as $w) {
            $rebuilt = WalletService::rebuild((int)$w['user_id']);
            if ($rebuilt !== (int)$w['balance']) {
                $mismatch[] = ['user_id' => (int)$w['user_id'], 'stored' => (int)$w['balance'], 'ledger' => $rebuilt];
                if (count($mismatch) >= 20) break;
            }
        }
        return ['pending_by_kind' => $pendingTx, 'old_pending' => $oldPending, 'webhooks_open' => $wh, 'balance_mismatch' => $mismatch];
    }
}
