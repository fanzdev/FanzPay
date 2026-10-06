<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\DB;

final class NotificationService {
    public static function send(int $userId, string $type, string $title, string $body): void {
        DB::exec('INSERT INTO notifications (user_id, type, title, body) VALUES (?,?,?,?)', [$userId, $type, $title, $body]);
    }

    public static function unreadCount(int $userId): int {
        $r = DB::one('SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
        return (int)($r['c'] ?? 0);
    }

    /** @return array<int,array> */
    public static function list(int $userId, int $limit = 20): array {
        return DB::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT ?', [$userId, $limit]);
    }
}
