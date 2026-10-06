<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Validator;
use App\Core\View;
use App\Providers\DigiFlazzClient;
use App\Services\NotificationService;
use App\Services\PpobService;
use App\Services\ReconcileService;
use App\Services\Settings;
use App\Services\WalletService;
use App\Services\WithdrawService;

/** Panel admin/super-admin dengan permission granular. */
final class AdminController {
    private array $u;
    private bool $super;

    public function __construct() {
        $u = Auth::user();
        if (!$u || !Auth::isAdmin($u)) redirect('login');
        $this->u = $u;
        $this->super = Auth::isSuperAdmin($u);
    }

    private function need(string $perm): void {
        if (!Auth::can($perm, $this->u)) {
            http_response_code(403);
            View::render('admin/denied', ['title' => 'Akses ditolak', 'user' => $this->u, 'unread' => 0], 'admin');
            exit;
        }
    }

    private function base(string $view, array $data = []): void {
        $data += ['user' => $this->u, 'is_super' => $this->super, 'perms' => Auth::PERMISSIONS];
        View::render($view, $data, 'admin');
    }

    public function dashboard(): void {
        $this->need('dashboard.view');
        $s = [
            'users' => (int)(DB::one('SELECT COUNT(*) c FROM users WHERE role="user"')['c'] ?? 0),
            'tx_today' => (int)(DB::one("SELECT COUNT(*) c FROM transactions WHERE DATE(created_at)=CURDATE()")['c'] ?? 0),
            'vol_today' => (int)(DB::one("SELECT COALESCE(SUM(total),0) s FROM transactions WHERE DATE(created_at)=CURDATE() AND status='success'")['s'] ?? 0),
            'pending' => (int)(DB::one("SELECT COUNT(*) c FROM transactions WHERE status IN ('pending','processing')")['c'] ?? 0),
            'failed' => (int)(DB::one("SELECT COUNT(*) c FROM transactions WHERE status IN ('failed','expired') AND DATE(created_at)=CURDATE()")['c'] ?? 0),
            'liability' => (int)(DB::one('SELECT COALESCE(SUM(balance),0) s FROM wallets')['s'] ?? 0),
        ];
        $daily = DB::all("SELECT DATE(created_at) d, COUNT(*) c, COALESCE(SUM(CASE WHEN status='success' THEN total ELSE 0 END),0) v FROM transactions WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) GROUP BY DATE(created_at) ORDER BY d");
        // Estimasi profit PPOB hari ini (modal vs jual) — hanya super admin yg lihat angka ini
        $profit = $this->super ? (int)(DB::one("SELECT COALESCE(SUM(pt.sell_price - pt.base_price),0) s FROM provider_transactions pt JOIN transactions t ON t.id=pt.transaction_id WHERE t.status='success' AND DATE(t.created_at)=CURDATE()")['s'] ?? 0) : 0;
        $prov = (new DigiFlazzClient())->balance();
        $recent = DB::all('SELECT t.*, u.name FROM transactions t JOIN users u ON u.id=t.user_id ORDER BY t.id DESC LIMIT 10');
        $this->base('admin/dashboard', ['title' => 'Command Center', 'nav' => 'dashboard', 's' => $s, 'daily' => $daily, 'profit' => $profit, 'prov' => $prov, 'recent' => $recent]);
    }

    // ---- Users ----
    public function users(): void {
        $this->need('users.view');
        $q = trim($_GET['q'] ?? '');
        $rows = $q !== ''
            ? DB::all('SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id=u.id WHERE u.role="user" AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?) ORDER BY u.id DESC LIMIT 50', ["%{$q}%", "%{$q}%", "%{$q}%"])
            : DB::all('SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON w.user_id=u.id WHERE u.role="user" ORDER BY u.id DESC LIMIT 50');
        $this->base('admin/users', ['title' => 'Pengguna', 'nav' => 'users', 'rows' => $rows, 'q' => $q, 'can_manage' => Auth::can('users.manage', $this->u), 'ok' => flash('ok'), 'error' => flash('error')]);
    }

    public function userToggle(string $id): void {
        $this->need('users.manage');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/pengguna');
        $t = DB::one('SELECT * FROM users WHERE id=? AND role="user"', [(int)$id]);
        if ($t) {
            $ns = $t['status'] === 'active' ? 'suspended' : 'active';
            DB::exec('UPDATE users SET status=? WHERE id=?', [$ns, (int)$id]);
            Logger::audit((int)$this->u['id'], $this->u['role'], 'admin.user.toggle', 'users', (int)$id, 'ok', ['to' => $ns]);
            flash('ok', 'Status pengguna diperbarui.');
        }
        redirect('admin/pengguna');
    }

    // ---- Admin management (super only) ----
    public function admins(): void {
        if (!$this->super) { $this->need('admins.manage'); }
        $rows = DB::all('SELECT * FROM users WHERE role IN ("admin","super_admin") ORDER BY id');
        $permsOf = [];
        foreach ($rows as $r) $permsOf[(int)$r['id']] = array_column(DB::all('SELECT permission FROM admin_permissions WHERE admin_id=?', [(int)$r['id']]), 'permission');
        $this->base('admin/admins', ['title' => 'Kelola Admin', 'nav' => 'admins', 'rows' => $rows, 'permsOf' => $permsOf, 'ok' => flash('ok'), 'error' => flash('error')]);
    }

    public function adminStore(): void {
        if (!$this->super) redirect('admin');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/admin');
        [$clean, $errors] = Validator::validate($_POST, ['name' => 'required|max:100', 'email' => 'required|email|max:190', 'password' => 'required|min:8']);
        if ($errors || DB::one('SELECT id FROM users WHERE email=?', [$clean['email']])) { flash('error', 'Data admin tidak valid/sudah ada.'); redirect('admin/admin'); }
        $perms = array_intersect($_POST['perms'] ?? [], Auth::PERMISSIONS);
        DB::tx(function () use ($clean, $perms) {
            $pdo = DB::pdo();
            $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status,email_verified_at) VALUES ('admin',?,?,?,?,'', 'active',NOW())")->execute([$clean['name'], '080000000000', $clean['email'], password_hash($_POST['password'], PASSWORD_DEFAULT)]);
            $id = (int)$pdo->lastInsertId();
            $pdo->prepare('UPDATE users SET public_id=? WHERE id=?')->execute([new_public_id(), $id]);
            foreach ($perms as $p) $pdo->prepare('INSERT IGNORE INTO admin_permissions (admin_id,permission) VALUES (?,?)')->execute([$id, $p]);
        });
        Logger::audit((int)$this->u['id'], 'super_admin', 'admin.create', 'users', null);
        flash('ok', 'Admin dibuat.');
        redirect('admin/admin');
    }

    public function adminPerms(string $id): void {
        if (!$this->super) redirect('admin');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/admin');
        $perms = array_intersect($_POST['perms'] ?? [], Auth::PERMISSIONS);
        DB::tx(function () use ($id, $perms) {
            DB::exec('DELETE FROM admin_permissions WHERE admin_id=?', [(int)$id]);
            foreach ($perms as $p) DB::exec('INSERT INTO admin_permissions (admin_id,permission) VALUES (?,?)', [(int)$id, $p]);
        });
        Logger::audit((int)$this->u['id'], 'super_admin', 'admin.perms', 'users', (int)$id, 'ok', ['perms' => $perms]);
        flash('ok', 'Permission admin diperbarui.');
        redirect('admin/admin');
    }

    // ---- Transactions / deposits / withdrawals ----
    public function transactions(): void {
        $this->need('transactions.view');
        $st = $_GET['status'] ?? '';
        $sql = 'SELECT t.*, u.name FROM transactions t JOIN users u ON u.id=t.user_id';
        $p = [];
        if (in_array($st, ['pending', 'processing', 'success', 'failed', 'expired', 'refunded', 'reversed'], true)) { $sql .= ' WHERE t.status=?'; $p[] = $st; }
        $rows = DB::all($sql . ' ORDER BY t.id DESC LIMIT 100', $p);
        $this->base('admin/transactions', ['title' => 'Transaksi', 'nav' => 'tx', 'rows' => $rows, 'status' => $st]);
    }

    public function deposits(): void {
        $this->need('deposits.view');
        $rows = DB::all('SELECT d.*, u.name FROM deposits d JOIN users u ON u.id=d.user_id ORDER BY d.id DESC LIMIT 100');
        $this->base('admin/deposits', ['title' => 'Deposit', 'nav' => 'deposits', 'rows' => $rows]);
    }

    public function withdrawals(): void {
        $this->need('withdrawals.view');
        $rows = DB::all('SELECT w.*, u.name, CONCAT(wd.bank_name," • ",wd.bank_account) dest FROM withdrawals w JOIN users u ON u.id=w.user_id JOIN withdrawal_destinations wd ON wd.id=w.destination_id ORDER BY w.id DESC LIMIT 100');
        $this->base('admin/withdrawals', ['title' => 'Withdrawal', 'nav' => 'withdrawals', 'rows' => $rows, 'ok' => flash('ok'), 'can_manage' => Auth::can('withdrawals.manage', $this->u)]);
    }

    public function withdrawalReject(string $ref): void {
        $this->need('withdrawals.manage');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/withdraw');
        $w = DB::one('SELECT * FROM withdrawals WHERE reference_id=?', [$ref]);
        if ($w) { WithdrawService::reverse((int)$w['user_id'], $ref, 'Ditolak admin'); flash('ok', 'Withdrawal ditolak, saldo dikembalikan.'); }
        redirect('admin/withdraw');
    }

    // ---- Products & pricing ----
    public function products(): void {
        $this->need('products.view');
        $rows = DB::all('SELECT p.*, pp.sell_price, pp.margin, c.name cat_name, (SELECT base_price FROM provider_products WHERE product_id=p.id ORDER BY id DESC LIMIT 1) base_price FROM products p JOIN product_categories c ON c.id=p.category_id LEFT JOIN product_prices pp ON pp.product_id=p.id ORDER BY c.sort, p.name LIMIT 200');
        $cats = DB::all('SELECT * FROM product_categories ORDER BY sort');
        $this->base('admin/products', ['title' => 'Produk & Harga', 'nav' => 'products', 'rows' => $rows, 'cats' => $cats, 'ok' => flash('ok'), 'can_manage' => Auth::can('products.manage', $this->u), 'is_super' => $this->super]);
    }

    public function priceSave(): void {
        $this->need('products.manage');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/produk');
        $pid = (int)($_POST['product_id'] ?? 0);
        $sell = (int)($_POST['sell_price'] ?? 0);
        if ($pid > 0 && $sell > 0) {
            $base = (int)(DB::one('SELECT base_price FROM provider_products WHERE product_id=? ORDER BY id DESC LIMIT 1', [$pid])['base_price'] ?? 0);
            DB::exec('INSERT INTO product_prices (product_id,sell_price,margin,updated_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE sell_price=VALUES(sell_price), margin=VALUES(margin), updated_by=VALUES(updated_by)', [$pid, $sell, max(0, $sell - $base), (int)$this->u['id']]);
            Logger::audit((int)$this->u['id'], $this->u['role'], 'pricing.update', 'products', $pid, 'ok', ['sell' => $sell]);
            flash('ok', 'Harga jual diperbarui.');
        }
        redirect('admin/produk');
    }

    public function productToggle(string $id): void {
        $this->need('products.manage');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/produk');
        DB::exec('UPDATE products SET is_active = 1 - is_active WHERE id=?', [(int)$id]);
        Logger::audit((int)$this->u['id'], $this->u['role'], 'product.toggle', 'products', (int)$id);
        redirect('admin/produk');
    }

    public function syncProducts(): void {
        $this->need('products.manage');
        $r = \App\Services\CatalogSyncService::syncPrepaid();
        if (!$r['ok']) { flash('error', (string)$r['message']); redirect('admin/produk'); }
        Logger::audit((int)$this->u['id'], $this->u['role'], 'product.sync', 'providers', 'digiflazz', 'ok', ['skus' => $r['skus'], 'active' => $r['active']]);
        flash('ok', "Sinkronisasi DigiFlazz selesai: {$r['skus']} SKU, {$r['active']} aktif.");
        redirect('admin/produk');
    }

    // ---- Reports / audit / reconcile / notifications / settings ----
    public function reports(): void {
        $this->need('reports.view');
        $rows = DB::all("SELECT DATE(t.created_at) d, t.kind, COUNT(*) c, SUM(CASE WHEN t.status='success' THEN t.total ELSE 0 END) v FROM transactions t WHERE t.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(t.created_at), t.kind ORDER BY d DESC, t.kind LIMIT 200");
        $this->base('admin/reports', ['title' => 'Laporan', 'nav' => 'reports', 'rows' => $rows]);
    }

    public function audit(): void {
        $this->need('audit.view');
        $rows = DB::all('SELECT a.*, u.name actor_name FROM audit_logs a LEFT JOIN users u ON u.id=a.actor_id ORDER BY a.id DESC LIMIT 100');
        $this->base('admin/audit', ['title' => 'Audit Log', 'nav' => 'audit', 'rows' => $rows]);
    }

    public function reconcile(): void {
        $this->need('reconcile.manage');
        $data = ReconcileService::overview();
        // aksi: refund paksa transaksi PPOB gagal yang masih pending > 60 mnt
        $this->base('admin/reconcile', ['title' => 'Rekonsiliasi', 'nav' => 'reconcile', 'data' => $data, 'ok' => flash('ok')]);
    }

    public function reconcileRefund(string $ref): void {
        $this->need('reconcile.manage');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/rekonsiliasi');
        $t = DB::one('SELECT * FROM transactions WHERE reference_id=?', [$ref]);
        if ($t && $t['kind'] === 'ppob') { PpobService::refund((int)$t['user_id'], $ref, 'Refund manual via rekonsiliasi'); flash('ok', 'Refund diproses.'); }
        redirect('admin/rekonsiliasi');
    }

    public function notifications(): void {
        $this->need('notifications.manage');
        if (is_post()) {
            if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/notifikasi');
            [$clean, $errors] = Validator::validate($_POST, ['title' => 'required|max:150', 'body' => 'required|max:255']);
            if (!$errors) {
                $users = DB::all('SELECT id FROM users WHERE role="user" AND status="active"');
                foreach ($users as $x) NotificationService::send((int)$x['id'], 'announcement', $clean['title'], $clean['body']);
                Logger::audit((int)$this->u['id'], $this->u['role'], 'notify.broadcast', 'notifications', null);
                flash('ok', 'Pengumuman terkirim ke ' . count($users) . ' pengguna.');
            } else flash('error', reset($errors));
            redirect('admin/notifikasi');
        }
        $this->base('admin/notifications', ['title' => 'Notifikasi', 'nav' => 'notifications', 'ok' => flash('ok'), 'error' => flash('error')]);
    }

    public function settings(): void {
        if (!$this->super) $this->need('settings.manage');
        if (is_post()) {
            if (!$this->super) redirect('admin');
            if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/pengaturan');
            foreach (['site_name', 'min_deposit', 'max_deposit', 'min_withdraw', 'max_withdraw', 'min_transfer', 'max_transfer', 'provider_mode'] as $k) {
                if (isset($_POST[$k])) Settings::set($k, trim((string)$_POST[$k]), (int)$this->u['id']);
            }
            Settings::set('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0', (int)$this->u['id']);
            Logger::audit((int)$this->u['id'], 'super_admin', 'settings.update', 'system_settings', null);
            flash('ok', 'Pengaturan disimpan.');
            redirect('admin/pengaturan');
        }
        $this->base('admin/settings', ['title' => 'Pengaturan', 'nav' => 'settings', 's' => Settings::all(), 'ok' => flash('ok')]);
    }

    public function apiConfig(): void {
        if (!$this->super) { $this->need('apiconfig.manage'); if (!$this->super) redirect('admin'); }
        if (is_post()) {
            if (!CSRF::check($_POST['_csrf'] ?? null)) redirect('admin/api');
            // Simpan ke system_settings (terpisah dari .env agar bisa diatur via panel;
            // .env tetap prioritas bila terisi — lihat index.php wiring).
            foreach (['buatqris_account' => 'account', 'buatqris_wd_account' => 'wd', 'digiflazz_user' => 'user'] as $field => $x) {
                if (isset($_POST[$field]) && trim((string)$_POST[$field]) !== '') Settings::set($field, trim((string)$_POST[$field]), (int)$this->u['id']);
            }
            foreach (['buatqris_secret' => 'secret', 'buatqris_sign' => 'sign', 'digiflazz_key' => 'key'] as $field => $x) {
                $v = trim((string)($_POST[$field] ?? ''));
                if ($v !== '' && $v !== '••••••••') Settings::set($field, $v, (int)$this->u['id']);
            }
            Logger::audit((int)$this->u['id'], 'super_admin', 'apiconfig.update', 'system_settings', null);
            flash('ok', 'Konfigurasi API disimpan.');
            redirect('admin/api');
        }
        $masked = [];
        foreach (['buatqris_secret', 'buatqris_sign', 'digiflazz_key'] as $k) $masked[$k] = mask_secret(Settings::get($k, ''));
        $this->base('admin/apiconfig', ['title' => 'Konfigurasi API', 'nav' => 'apiconfig', 's' => Settings::all(), 'masked' => $masked, 'ok' => flash('ok')]);
    }
}
