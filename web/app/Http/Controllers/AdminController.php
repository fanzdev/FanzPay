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

/** Panel admin/super-admin dengan permission granular.
 * Dipakai lewat dua prefix: /admin (role admin) dan /superadmin (role super_admin)
 * via subclass SuperAdminController. Semua URL/redirect memakai $this->panel. */
class AdminController {
    protected string $panel = 'admin';
    protected array $u;
    protected bool $super;

    public function __construct() {
        $u = Auth::user();
        if (!$u) redirect('login');
        $role = (string)($u['role'] ?? '');
        if ($this->panel === 'superadmin') {
            // Prefix /superadmin khusus super_admin. Admin biasa digiring ke panelnya.
            if ($role === 'admin') redirect($this->swapPanel('admin'));
            if ($role !== 'super_admin') redirect('home');
        } else {
            // Prefix /admin khusus admin. Super admin digiring ke panelnya.
            if ($role === 'super_admin') redirect($this->swapPanel('superadmin'));
            if ($role !== 'admin') redirect('home');
        }
        $this->u = $u;
        $this->super = Auth::isSuperAdmin($u);
    }

    /** Alihkan ke prefix panel lain dengan path yang sama (mis. /admin/deposit → /superadmin/deposit). */
    protected function swapPanel(string $to): string {
        $req = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $basePath = app_base()['path'];
        if ($basePath !== '' && str_starts_with($req, $basePath)) $req = substr($req, strlen($basePath));
        $req = '/' . trim($req, '/');
        $from = $this->panel === 'superadmin' ? '/superadmin' : '/admin';
        $rest = str_starts_with($req, $from) ? substr($req, strlen($from)) : '';
        return trim($to . $rest, '/') === $to && $rest === '' ? $to : $to . $rest;
    }

    /** Daftar permission milik user ini (super admin = semua). */
    protected function myPerms(): array {
        if ($this->super) return Auth::PERMISSIONS;
        try {
            return array_column(DB::all('SELECT permission FROM admin_permissions WHERE admin_id = ?', [(int)$this->u['id']]), 'permission');
        } catch (\Throwable $e) { return []; }
    }

    private function need(string $perm): void {
        if (!Auth::can($perm, $this->u)) {
            http_response_code(403);
            View::render('admin/denied', ['title' => 'Akses ditolak', 'user' => $this->u, 'unread' => 0, 'panel' => $this->panel, 'my_perms' => $this->myPerms(), 'is_super' => $this->super], 'admin');
            exit;
        }
    }

    private function base(string $view, array $data = []): void {
        $data += ['user' => $this->u, 'is_super' => $this->super, 'perms' => Auth::PERMISSIONS, 'panel' => $this->panel, 'my_perms' => $this->myPerms()];
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
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/pengguna');
        $t = DB::one('SELECT * FROM users WHERE id=? AND role="user"', [(int)$id]);
        if ($t) {
            $ns = $t['status'] === 'active' ? 'suspended' : 'active';
            DB::exec('UPDATE users SET status=? WHERE id=?', [$ns, (int)$id]);
            Logger::audit((int)$this->u['id'], $this->u['role'], 'admin.user.toggle', 'users', (int)$id, 'ok', ['to' => $ns]);
            flash('ok', 'Status pengguna diperbarui.');
        }
        redirect($this->panel . '/pengguna');
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
        if (!$this->super) redirect($this->panel);
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/admin');
        [$clean, $errors] = Validator::validate($_POST, ['name' => 'required|max:100', 'email' => 'required|email|max:190', 'password' => 'required|min:8']);
        if ($errors || DB::one('SELECT id FROM users WHERE email=?', [$clean['email']])) { flash('error', 'Data admin tidak valid/sudah ada.'); redirect($this->panel . '/admin'); }
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
        redirect($this->panel . '/admin');
    }

    public function adminPerms(string $id): void {
        if (!$this->super) redirect($this->panel);
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/admin');
        $perms = array_intersect($_POST['perms'] ?? [], Auth::PERMISSIONS);
        DB::tx(function () use ($id, $perms) {
            DB::exec('DELETE FROM admin_permissions WHERE admin_id=?', [(int)$id]);
            foreach ($perms as $p) DB::exec('INSERT INTO admin_permissions (admin_id,permission) VALUES (?,?)', [(int)$id, $p]);
        });
        Logger::audit((int)$this->u['id'], 'super_admin', 'admin.perms', 'users', (int)$id, 'ok', ['perms' => $perms]);
        flash('ok', 'Permission admin diperbarui.');
        redirect($this->panel . '/admin');
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
        $q = trim((string)($_GET['q'] ?? ''));
        $st = trim((string)($_GET['status'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 20;
        $off = ($page - 1) * $per;
        $hasGw = self::hasColumn('deposits', 'gateway');
        $sel = $hasGw ? 'd.*, u.name' : 'd.*, u.name';
        $sql = "SELECT {$sel} FROM deposits d JOIN users u ON u.id=d.user_id";
        $cond = [];
        $p = [];
        if ($q !== '') { $cond[] = '(d.reference_id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)'; $p[] = "%{$q}%"; $p[] = "%{$q}%"; $p[] = "%{$q}%"; }
        if (in_array($st, ['pending', 'success', 'expired', 'failed'], true)) { $cond[] = 'd.status = ?'; $p[] = $st; }
        if ($cond) $sql .= ' WHERE ' . implode(' AND ', $cond);
        $total = (int)(DB::one('SELECT COUNT(*) c FROM deposits d JOIN users u ON u.id=d.user_id' . ($cond ? ' WHERE ' . implode(' AND ', $cond) : ''), $p)['c'] ?? 0);
        $rows = DB::all($sql . ' ORDER BY d.id DESC LIMIT ' . $per . ' OFFSET ' . $off, $p);
        $this->base('admin/deposits', ['title' => 'Deposit', 'nav' => 'deposits', 'rows' => $rows, 'q' => $q, 'status' => $st, 'page' => $page, 'pages' => max(1, (int)ceil($total / $per)), 'has_gateway' => $hasGw, 'ok' => flash('ok'), 'error' => flash('error')]);
    }

    private static function hasColumn(string $table, string $col): bool {
        static $cache = [];
        $k = $table . '.' . $col;
        if (isset($cache[$k])) return $cache[$k];
        try {
            foreach (DB::all("SHOW COLUMNS FROM `{$table}` LIKE ?", [$col]) as $r) { $cache[$k] = true; return true; }
        } catch (\Throwable $e) {}
        return $cache[$k] = false;
    }

    public function withdrawals(): void {
        $this->need('withdrawals.view');
        $rows = DB::all('SELECT w.*, u.name, CONCAT(wd.bank_name," • ",wd.bank_account) dest FROM withdrawals w JOIN users u ON u.id=w.user_id JOIN withdrawal_destinations wd ON wd.id=w.destination_id ORDER BY w.id DESC LIMIT 100');
        $this->base('admin/withdrawals', ['title' => 'Withdrawal', 'nav' => 'withdrawals', 'rows' => $rows, 'ok' => flash('ok'), 'can_manage' => Auth::can('withdrawals.manage', $this->u)]);
    }

    public function withdrawalReject(string $ref): void {
        $this->need('withdrawals.manage');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/withdraw');
        $w = DB::one('SELECT * FROM withdrawals WHERE reference_id=?', [$ref]);
        if ($w) { WithdrawService::reverse((int)$w['user_id'], $ref, 'Ditolak admin'); flash('ok', 'Withdrawal ditolak, saldo dikembalikan.'); }
        redirect($this->panel . '/withdraw');
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
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/produk');
        $pid = (int)($_POST['product_id'] ?? 0);
        $sell = (int)($_POST['sell_price'] ?? 0);
        if ($pid > 0 && $sell > 0) {
            $base = (int)(DB::one('SELECT base_price FROM provider_products WHERE product_id=? ORDER BY id DESC LIMIT 1', [$pid])['base_price'] ?? 0);
            DB::exec('INSERT INTO product_prices (product_id,sell_price,margin,updated_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE sell_price=VALUES(sell_price), margin=VALUES(margin), updated_by=VALUES(updated_by)', [$pid, $sell, max(0, $sell - $base), (int)$this->u['id']]);
            Logger::audit((int)$this->u['id'], $this->u['role'], 'pricing.update', 'products', $pid, 'ok', ['sell' => $sell]);
            flash('ok', 'Harga jual diperbarui.');
        }
        redirect($this->panel . '/produk');
    }

    public function productToggle(string $id): void {
        $this->need('products.manage');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/produk');
        DB::exec('UPDATE products SET is_active = 1 - is_active WHERE id=?', [(int)$id]);
        Logger::audit((int)$this->u['id'], $this->u['role'], 'product.toggle', 'products', (int)$id);
        redirect($this->panel . '/produk');
    }

    public function syncProducts(): void {
        $this->need('products.manage');
        $r = \App\Services\CatalogSyncService::syncPrepaid();
        if (!$r['ok']) { flash('error', (string)$r['message']); redirect($this->panel . '/produk'); }
        Logger::audit((int)$this->u['id'], $this->u['role'], 'product.sync', 'providers', 'digiflazz', 'ok', ['skus' => $r['skus'], 'active' => $r['active']]);
        flash('ok', "Sinkronisasi DigiFlazz selesai: {$r['skus']} SKU, {$r['active']} aktif.");
        redirect($this->panel . '/produk');
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
        $data['midtrans_pending'] = ReconcileService::midtransPending();
        $data['midtrans_mode'] = \App\Providers\MidtransClient::resolveConfig()['mode'];
        $data['midtrans_ready'] = strlen(\App\Providers\MidtransClient::resolveConfig()['server_key']) >= 20;
        // aksi: refund paksa transaksi PPOB gagal yang masih pending > 60 mnt
        $this->base('admin/reconcile', ['title' => 'Rekonsiliasi', 'nav' => 'reconcile', 'data' => $data, 'ok' => flash('ok')]);
    }

    public function reconcileRefund(string $ref): void {
        $this->need('reconcile.manage');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/rekonsiliasi');
        $t = DB::one('SELECT * FROM transactions WHERE reference_id=?', [$ref]);
        if ($t && $t['kind'] === 'ppob') { PpobService::refund((int)$t['user_id'], $ref, 'Refund manual via rekonsiliasi'); flash('ok', 'Refund diproses.'); }
        redirect($this->panel . '/rekonsiliasi');
    }

    public function notifications(): void {
        $this->need('notifications.manage');
        if (is_post()) {
            if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/notifikasi');
            [$clean, $errors] = Validator::validate($_POST, ['title' => 'required|max:150', 'body' => 'required|max:255']);
            if (!$errors) {
                $users = DB::all('SELECT id FROM users WHERE role="user" AND status="active"');
                foreach ($users as $x) NotificationService::send((int)$x['id'], 'announcement', $clean['title'], $clean['body']);
                Logger::audit((int)$this->u['id'], $this->u['role'], 'notify.broadcast', 'notifications', null);
                flash('ok', 'Pengumuman terkirim ke ' . count($users) . ' pengguna.');
            } else flash('error', reset($errors));
            redirect($this->panel . '/notifikasi');
        }
        $this->base('admin/notifications', ['title' => 'Notifikasi', 'nav' => 'notifications', 'ok' => flash('ok'), 'error' => flash('error')]);
    }

    public function settings(): void {
        if (!$this->super) $this->need('settings.manage');
        if (is_post()) {
            if (!$this->super) redirect($this->panel);
            if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/pengaturan');
            foreach (['site_name', 'min_deposit', 'max_deposit', 'min_withdraw', 'max_withdraw', 'min_transfer', 'max_transfer', 'provider_mode', 'deposit_gateway', 'deposit_methods', 'deposit_expiry_minutes', 'midtrans_mode', 'midtrans_fee_qris_pct', 'midtrans_fee_bca', 'midtrans_fee_bri', 'midtrans_fee_bni', 'midtrans_fee_mandiri', 'midtrans_fee_permata', 'midtrans_fee_gopay_pct', 'midtrans_fee_shopeepay_pct'] as $k) {
                if (isset($_POST[$k])) {
                    $v = trim((string)$_POST[$k]);
                    // Validasi keras kebijakan deposit di backend.
                    if (in_array($k, ['min_deposit', 'max_deposit'], true)) {
                        $iv = (int)$v;
                        if ($k === 'min_deposit' && $iv < 10000) { flash('error', 'Minimal deposit tidak boleh di bawah Rp 10.000.'); redirect($this->panel . '/pengaturan'); }
                        if ($iv < 0) { flash('error', 'Nominal tidak valid.'); redirect($this->panel . '/pengaturan'); }
                    }
                    if ($k === 'deposit_methods') {
                        $allow = array_keys(\App\Services\MidtransService::METHODS);
                        $picked = array_values(array_intersect(array_map('trim', explode(',', strtolower($v))), $allow));
                        if ($picked === []) { flash('error', 'Pilih minimal satu metode pembayaran.'); redirect($this->panel . '/pengaturan'); }
                        $v = implode(',', $picked);
                    }
                    if ($k === 'deposit_expiry_minutes') {
                        $iv = (int)$v;
                        if ($iv < 5 || $iv > 60) { flash('error', 'Masa berlaku 5–60 menit.'); redirect($this->panel . '/pengaturan'); }
                    }
                    if ($k === 'deposit_gateway' && !in_array($v, ['midtrans', 'buatqris'], true)) { flash('error', 'Gateway tidak valid.'); redirect($this->panel . '/pengaturan'); }
                    if ($k === 'midtrans_mode' && !in_array($v, ['sandbox', 'production'], true)) { flash('error', 'Mode Midtrans tidak valid.'); redirect($this->panel . '/pengaturan'); }
                    Settings::set($k, $v, (int)$this->u['id']);
                }
            }
            Settings::set('maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0', (int)$this->u['id']);
            Settings::set('deposit_enabled', isset($_POST['deposit_enabled']) ? '1' : '0', (int)$this->u['id']);
            Logger::audit((int)$this->u['id'], 'super_admin', 'settings.update', 'system_settings', null);
            flash('ok', 'Pengaturan disimpan.');
            redirect($this->panel . '/pengaturan');
        }
        $this->base('admin/settings', ['title' => 'Pengaturan', 'nav' => 'settings', 's' => Settings::all(), 'ok' => flash('ok'), 'error' => flash('error')]);
    }

    // ---- MCP Server (super admin only) ----
    public function mcp(): void {
        if (!$this->super) redirect($this->panel);
        \App\Mcp\McpAuth::ensureTables();
        if (is_post()) {
            if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/mcp');
            Settings::set('mcp_enabled', isset($_POST['mcp_enabled']) ? '1' : '0', (int)$this->u['id']);
            Settings::set('mcp_max_per_tx', (string)max(0, (int)($_POST['mcp_max_per_tx'] ?? 100000)), (int)$this->u['id']);
            Settings::set('mcp_max_daily', (string)max(0, (int)($_POST['mcp_max_daily'] ?? 500000)), (int)$this->u['id']);
            $cats = strtolower(trim((string)($_POST['mcp_allowed_categories'] ?? '')));
            Settings::set('mcp_allowed_categories', $cats !== '' ? $cats : 'pulsa,data,pln,emoney,game', (int)$this->u['id']);
            $rl = (int)($_POST['mcp_rate_per_min'] ?? 60);
            Settings::set('mcp_rate_per_min', (string)min(600, max(10, $rl)), (int)$this->u['id']);
            $want = is_array($_POST['tools'] ?? null) ? $_POST['tools'] : [];
            foreach (array_keys(\App\Mcp\McpTools::definitions()) as $t) {
                Settings::set('mcp_tool_' . $t, in_array($t, $want, true) ? '1' : '0', (int)$this->u['id']);
            }
            Logger::audit((int)$this->u['id'], 'super_admin', 'mcp.settings.update', 'system_settings', null);
            flash('ok', 'Pengaturan MCP disimpan & ditegakkan backend.');
            redirect($this->panel . '/mcp');
        }
        $tokens = [];
        try {
            $tokens = DB::all('SELECT t.*, u.email FROM mcp_tokens t JOIN users u ON u.id=t.user_id WHERE t.revoked_at IS NULL AND t.expires_at > NOW() ORDER BY t.id DESC LIMIT 20');
        } catch (\Throwable $e) {}
        try {
            $usage = DB::all("SELECT DATE(created_at) d, COUNT(*) c FROM audit_logs WHERE action LIKE 'mcp.tool.%' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(created_at) ORDER BY d");
        } catch (\Throwable $e) { $usage = []; }
        try {
            $pending = DB::all("SELECT t.reference_id, t.total, t.created_at, u.email FROM transactions t JOIN users u ON u.id=t.user_id WHERE t.kind='ppob' AND t.status IN ('pending','processing') AND t.note LIKE '%[mcp]%' ORDER BY t.id DESC LIMIT 20");
        } catch (\Throwable $e) { $pending = []; }
        $this->base('admin/mcp', ['title' => 'MCP Server', 'nav' => 'mcp', 's' => Settings::all(), 'tools' => array_keys(\App\Mcp\McpTools::definitions()), 'tokens' => $tokens, 'usage' => $usage, 'pending' => $pending, 'ok' => flash('ok'), 'error' => flash('error')]);
    }

    public function mcpRevoke(string $id): void {
        if (!$this->super) redirect($this->panel);
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/mcp');
        try {
            $t = DB::one('SELECT user_id FROM mcp_tokens WHERE id = ?', [(int)$id]);
            DB::exec('UPDATE mcp_tokens SET revoked_at = NOW() WHERE id = ?', [(int)$id]);
            if ($t) \App\Services\NotificationService::send((int)$t['user_id'], 'mcp', 'Akses AI dicabut admin', 'Koneksi AI Anda dicabut administrator.');
            Logger::audit((int)$this->u['id'], 'super_admin', 'mcp.token.revoke', 'mcp_tokens', (int)$id);
            flash('ok', 'Koneksi dicabut.');
        } catch (\Throwable $e) { flash('error', 'Gagal mencabut.'); }
        redirect($this->panel . '/mcp');
    }

    public function apiConfig(): void {
        if (!$this->super) { $this->need('apiconfig.manage'); if (!$this->super) redirect($this->panel); }
        if (is_post()) {
            if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/api');
            // Simpan ke system_settings (terpisah dari .env agar bisa diatur via panel;
            // .env tetap prioritas bila terisi — lihat index.php wiring).
            foreach (['buatqris_account' => 'account', 'buatqris_wd_account' => 'wd', 'digiflazz_user' => 'user'] as $field => $x) {
                if (isset($_POST[$field]) && trim((string)$_POST[$field]) !== '') Settings::set($field, trim((string)$_POST[$field]), (int)$this->u['id']);
            }
            foreach (['buatqris_secret' => 'secret', 'buatqris_sign' => 'sign', 'digiflazz_key' => 'key'] as $field => $x) {
                $v = trim((string)($_POST[$field] ?? ''));
                if ($v !== '' && $v !== '••••••••') Settings::set($field, $v, (int)$this->u['id']);
            }
            // Midtrans: non-rahasia langsung simpan; secret hanya bila diisi (masking di view).
            if (isset($_POST['midtrans_mode']) && in_array($_POST['midtrans_mode'], ['sandbox', 'production'], true)) {
                Settings::set('midtrans_mode', (string)$_POST['midtrans_mode'], (int)$this->u['id']);
            }
            foreach (['midtrans_server_key' => 1, 'midtrans_client_key' => 1] as $field => $x) {
                $v = trim((string)($_POST[$field] ?? ''));
                if ($v !== '' && $v !== '••••••••') {
                    if (strlen($v) < 20) { flash('error', 'Kunci Midtrans tampak tidak valid (terlalu pendek).'); redirect($this->panel . '/api'); }
                    Settings::set($field, $v, (int)$this->u['id']);
                }
            }
            Logger::audit((int)$this->u['id'], 'super_admin', 'apiconfig.update', 'system_settings', null);
            flash('ok', 'Konfigurasi API disimpan.');
            redirect($this->panel . '/api');
        }
        $masked = [];
        foreach (['buatqris_secret', 'buatqris_sign', 'digiflazz_key', 'midtrans_server_key', 'midtrans_client_key'] as $k) $masked[$k] = mask_secret(Settings::get($k, ''));
        $cfg = \App\Providers\MidtransClient::resolveConfig();
        $this->base('admin/apiconfig', [
            'title' => 'Konfigurasi API', 'nav' => 'apiconfig', 's' => Settings::all(), 'masked' => $masked,
            'ok' => flash('ok'), 'error' => flash('error'),
            'midtrans_mode' => $cfg['mode'], 'midtrans_ready' => strlen($cfg['server_key']) >= 20,
            'webhook_url' => url('webhooks/midtrans'),
        ]);
    }

    /** Uji konektivitas Midtrans (super admin). Tidak membuat transaksi / dana. */
    public function midtransTest(): void {
        if (!$this->super) redirect($this->panel);
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/api');
        $r = (new \App\Providers\MidtransClient())->testConnection();
        Logger::audit((int)$this->u['id'], 'super_admin', 'midtrans.test', 'system_settings', null, $r['ok'] ? 'ok' : 'fail');
        flash($r['ok'] ? 'ok' : 'error', $r['message']);
        redirect($this->panel . '/api');
    }

    /** Sinkronkan satu deposit pending via Status API resmi (super admin / deposits.manage). */
    public function depositSync(string $ref): void {
        $this->need('deposits.view');
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/deposit');
        $dep = DB::one('SELECT * FROM deposits WHERE reference_id=?', [$ref]);
        if (!$dep) { flash('error', 'Deposit tidak ditemukan.'); redirect($this->panel . '/deposit'); }
        if (($dep['gateway'] ?? 'midtrans') !== 'midtrans' || !empty($dep['is_mock'])) {
            flash('error', 'Hanya deposit Midtrans non-mock yang bisa disinkronkan.');
            redirect($this->panel . '/deposit');
        }
        $st = (new \App\Providers\MidtransClient())->status($ref);
        if (!$st['ok']) {
            Logger::audit((int)$this->u['id'], $this->u['role'], 'deposit.sync', 'deposits', $ref, 'fail', ['http' => $st['http'] ?? 0]);
            flash('error', (string)($st['message'] ?? 'Gagal cek status.'));
            redirect($this->panel . '/deposit');
        }
        $d = $st['data'];
        $ts = (string)($d['transaction_status'] ?? 'unknown');
        if (in_array($ts, ['settlement', 'capture'], true) && ($ts !== 'capture' || ($d['fraud_status'] ?? 'accept') === 'accept')) {
            if ((int)($d['gross_amount'] ?? -1) !== (int)$dep['total_amount']) {
                Logger::audit((int)$this->u['id'], $this->u['role'], 'deposit.sync', 'deposits', $ref, 'fail', ['reason' => 'amount-mismatch']);
                flash('error', 'Nominal Midtrans tidak cocok — butuh tinjauan manual.');
                redirect($this->panel . '/deposit');
            }
            \App\Services\MidtransService::applyNotification(array_merge($d, ['order_id' => $ref]));
            flash('ok', 'Status Midtrans: ' . $ts . ' — saldo dikredit bila sah.');
        } elseif ($ts === 'pending') {
            flash('ok', 'Status Midtrans masih pending. Menunggu pembayaran.');
        } else {
            \App\Services\MidtransService::applyNotification(array_merge($d, ['order_id' => $ref]));
            flash('ok', 'Status Midtrans: ' . $ts . ' — deposit diperbarui.');
        }
        Logger::audit((int)$this->u['id'], $this->u['role'], 'deposit.sync', 'deposits', $ref, 'ok', ['midtrans_status' => $ts]);
        redirect($this->panel . '/deposit');
    }

    /** Simulasi sukses deposit MOCK sandbox (super admin). Transaksi nyata ditolak. */
    public function depositSimulate(string $ref): void {
        if (!$this->super) redirect($this->panel);
        if (!CSRF::check($_POST['_csrf'] ?? null)) redirect($this->panel . '/deposit');
        $r = \App\Services\MidtransService::simulateSuccess($ref);
        Logger::audit((int)$this->u['id'], 'super_admin', 'deposit.simulate', 'deposits', $ref, $r['ok'] ? 'ok' : 'fail');
        flash($r['ok'] ? 'ok' : 'error', $r['ok'] ? 'Simulasi sukses: saldo dikredit + ledger tercatat.' : (string)($r['message'] ?? 'Gagal.'));
        redirect($this->panel . '/deposit');
    }
}
