<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\DB;
use App\Core\Logger;
use App\Core\Validator;
use App\Core\View;
use App\Services\DepositService;
use App\Services\NotificationService;
use App\Services\PinService;
use App\Services\PpobService;
use App\Services\PricingService;
use App\Services\Settings;
use App\Services\TransferService;
use App\Services\WalletService;
use App\Services\WithdrawService;

/** Halaman user: guard login + role user. */
final class UserController {
    private array $u;

    public function __construct() {
        $u = Auth::user();
        if (!$u) { redirect('login'); }
        if ($u['role'] !== 'user') { redirect('admin'); }
        $this->u = $u;
    }

    private function base(string $view, array $data = []): void {
        $uid = (int)$this->u['id'];
        $data += [
            'user' => $this->u,
            'balance' => WalletService::balance($uid),
            'unread' => NotificationService::unreadCount($uid),
            'has_pin' => PinService::has($uid),
        ];
        View::render($view, $data, 'app');
    }

    public function home(): void {
        $uid = (int)$this->u['id'];
        $recent = DB::all('SELECT * FROM transactions WHERE user_id = ? ORDER BY id DESC LIMIT 5', [$uid]);
        $this->base('user/home', [
            'title' => 'Beranda', 'nav' => 'home', 'recent' => $recent,
            'featured' => array_slice(PricingService::catalog(), 0, 8),
            'sandbox' => Settings::get('provider_mode', 'sandbox') !== 'production',
        ]);
    }

    public function products(): void {
        $cat = $_GET['cat'] ?? null;
        $this->base('user/products', [
            'title' => 'Produk', 'nav' => 'products',
            'cats' => DB::all('SELECT * FROM product_categories WHERE is_active=1 ORDER BY sort'),
            'items' => PricingService::catalog(is_string($cat) && $cat !== '' ? $cat : null),
            'active_cat' => $cat,
        ]);
    }

    public function productDetail(string $code): void {
        $p = DB::one('SELECT p.*, pp.sell_price, c.name cat_name, c.code cat_code FROM products p JOIN product_categories c ON c.id=p.category_id LEFT JOIN product_prices pp ON pp.product_id=p.id WHERE p.code=? AND p.is_active=1', [$code]);
        if (!$p) { http_response_code(404); View::render('user/products', ['title' => 'Produk', 'nav' => 'products', 'cats' => [], 'items' => [], 'active_cat' => null, 'user' => $this->u, 'balance' => 0, 'unread' => 0, 'has_pin' => true], 'app'); return; }
        $this->base('user/product-detail', ['title' => $p['name'], 'nav' => 'products', 'p' => $p, 'error' => flash('error'), 'ok' => flash('ok')]);
    }

    public function doPpob(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('produk'); }
        $r = PpobService::purchase((int)$this->u['id'], (string)($_POST['code'] ?? ''), (string)($_POST['customer_no'] ?? ''), (string)($_POST['pin'] ?? ''), (string)($_POST['_idem'] ?? ''));
        flash($r['ok'] ? 'ok' : 'error', $r['ok'] ? 'Pembelian diproses. Lihat riwayat.' : $r['message']);
        redirect('riwayat');
    }

    public function transfer(): void {
        $this->base('user/transfer', ['title' => 'Transfer Gratis', 'nav' => 'transfer', 'error' => flash('error'), 'ok' => flash('ok'), 'lookup' => $_GET['to'] ?? '']);
    }

    public function doTransfer(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('transfer'); }
        $r = TransferService::transfer((int)$this->u['id'], (string)($_POST['lookup'] ?? ''), (int)($_POST['amount'] ?? 0), (string)($_POST['pin'] ?? ''), (string)($_POST['_idem'] ?? ''));
        flash($r['ok'] ? 'ok' : 'error', $r['ok'] ? 'Transfer berhasil. Gratis biaya.' : $r['message']);
        redirect($r['ok'] ? 'riwayat' : 'transfer');
    }

    public function deposit(): void {
        $this->base('user/deposit', ['title' => 'Deposit', 'nav' => 'deposit', 'error' => flash('error'), 'min' => Settings::int('min_deposit', 10000), 'max' => Settings::int('max_deposit', 5000000)]);
    }

    public function doDeposit(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('deposit'); }
        $r = DepositService::create((int)$this->u['id'], (int)($_POST['amount'] ?? 0), (string)($_POST['_idem'] ?? ''));
        if (!$r['ok']) { flash('error', $r['message']); redirect('deposit'); }
        redirect('deposit/' . $r['deposit']['reference_id']);
    }

    public function depositShow(string $ref): void {
        $d = DB::one('SELECT * FROM deposits WHERE reference_id=? AND user_id=?', [$ref, (int)$this->u['id']]);
        if (!$d) { http_response_code(404); redirect('deposit'); }
        $this->base('user/deposit-show', ['title' => 'Bayar Deposit', 'nav' => 'deposit', 'd' => $d]);
    }

    public function withdraw(): void {
        $uid = (int)$this->u['id'];
        $this->base('user/withdraw', [
            'title' => 'Tarik Dana', 'nav' => 'withdraw', 'error' => flash('error'), 'ok' => flash('ok'),
            'dests' => DB::all('SELECT * FROM withdrawal_destinations WHERE user_id=? ORDER BY id DESC', [$uid]),
            'min' => Settings::int('min_withdraw', 20000),
        ]);
    }

    public function doWithdrawDest(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('withdraw'); }
        [$clean, $errors] = Validator::validate($_POST, ['label' => 'required|max:80', 'bank_name' => 'required|max:80', 'bank_account' => 'required|max:40', 'bank_holder' => 'required|max:100']);
        if ($errors) { flash('error', reset($errors)); redirect('withdraw'); }
        DB::exec('INSERT INTO withdrawal_destinations (user_id,label,bank_name,bank_account,bank_holder) VALUES (?,?,?,?,?)', [(int)$this->u['id'], $clean['label'], $clean['bank_name'], $clean['bank_account'], $clean['bank_holder']]);
        flash('ok', 'Tujuan penarikan disimpan.');
        redirect('withdraw');
    }

    public function doWithdraw(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('withdraw'); }
        $r = WithdrawService::request((int)$this->u['id'], (int)($_POST['destination_id'] ?? 0), (int)($_POST['amount'] ?? 0), (string)($_POST['pin'] ?? ''), (string)($_POST['_idem'] ?? ''));
        flash($r['ok'] ? 'ok' : 'error', $r['ok'] ? 'Penarikan diajukan, menunggu proses.' : $r['message']);
        redirect($r['ok'] ? 'riwayat' : 'withdraw');
    }

    public function history(): void {
        $uid = (int)$this->u['id'];
        $kind = $_GET['kind'] ?? '';
        $allowed = ['deposit' => 'deposit', 'transfer' => 'transfer', 'ppob' => 'ppob', 'withdraw' => 'withdraw'];
        $sql = 'SELECT * FROM transactions WHERE user_id = ?';
        $p = [$uid];
        if (isset($allowed[$kind])) { $sql .= ' AND kind = ?'; $p[] = $kind; }
        $sql .= ' ORDER BY id DESC LIMIT 50';
        $this->base('user/history', ['title' => 'Riwayat', 'nav' => 'history', 'rows' => DB::all($sql, $p), 'kind' => $kind]);
    }

    public function txDetail(string $ref): void {
        $t = DB::one('SELECT * FROM transactions WHERE reference_id=? AND user_id=?', [$ref, (int)$this->u['id']]);
        if (!$t) { http_response_code(404); redirect('riwayat'); }
        $items = DB::all('SELECT * FROM transaction_items WHERE transaction_id=?', [(int)$t['id']]);
        $ledger = DB::all('SELECT * FROM wallet_ledger WHERE reference_id=? AND user_id=? ORDER BY id', [$ref, (int)$this->u['id']]);
        $this->base('user/tx-detail', ['title' => 'Detail Transaksi', 'nav' => 'history', 't' => $t, 'items' => $items, 'ledger' => $ledger]);
    }

    public function notifications(): void {
        $uid = (int)$this->u['id'];
        DB::exec('UPDATE notifications SET is_read=1 WHERE user_id=?', [$uid]);
        $this->base('user/notifications', ['title' => 'Notifikasi', 'nav' => 'notif', 'rows' => NotificationService::list($uid, 30)]);
    }

    public function profile(): void {
        $this->base('user/profile', ['title' => 'Profil', 'nav' => 'profile', 'error' => flash('error'), 'ok' => flash('ok')]);
    }

    public function doProfile(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('profil'); }
        [$clean, $errors] = Validator::validate($_POST, ['name' => 'required|max:100']);
        if ($errors) { flash('error', reset($errors)); redirect('profil'); }
        DB::exec('UPDATE users SET name=? WHERE id=?', [$clean['name'], (int)$this->u['id']]);
        Logger::audit((int)$this->u['id'], 'user', 'profile.update', 'users', (int)$this->u['id']);
        flash('ok', 'Profil diperbarui.');
        redirect('profil');
    }

    public function pin(): void {
        $this->base('user/pin', ['title' => 'PIN Transaksi', 'nav' => 'profile', 'error' => flash('error'), 'ok' => flash('ok')]);
    }

    public function doPin(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('pin'); }
        [$clean, $errors] = Validator::validate($_POST, ['pin' => 'required|pin']);
        if ($errors) { flash('error', reset($errors)); redirect('pin'); }
        PinService::set((int)$this->u['id'], $clean['pin']);
        flash('ok', 'PIN transaksi disimpan.');
        redirect('profil');
    }

    public function qr(): void {
        $this->base('user/qr', ['title' => 'QR Saya', 'nav' => 'profile']);
    }
}
