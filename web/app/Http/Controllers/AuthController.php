<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\DB;
use App\Core\Logger;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Core\View;
use App\Services\OtpService;

/** Register/Login/OTP/Logout + Setup Super Admin pertama. */
final class AuthController {
    public function login(): void {
        if (Auth::check()) redirect('');
        // Satu pintu masuk untuk SEMUA peran: user, admin, super_admin.
        // Redirect tujuan diatur otomatis di doLogin berdasarkan role.
        View::render('auth/login', ['title' => 'Masuk — FanzPay', 'error' => flash('error'), 'ok' => flash('ok')], 'auth');
    }

    public function doLogin(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa. Coba lagi.'); redirect('login'); }
        $rl = RateLimiter::hit('login:' . client_ip(), 10, 300);
        if (!$rl['allowed']) { flash('error', 'Terlalu banyak percobaan. Coba lagi nanti.'); redirect('login'); }
        [$in] = [Validator::validate($_POST, ['email' => 'required|max:190', 'password' => 'required|max:255'])];
        $u = DB::one('SELECT * FROM users WHERE email = ?', [trim($_POST['email'] ?? '')]);
        if (!$u || !password_verify((string)($_POST['password'] ?? ''), (string)$u['password_hash'])) {
            Logger::audit(null, null, 'auth.login.failed', 'users', null, 'fail', ['email' => trim($_POST['email'] ?? '')]);
            flash('error', 'Email atau kata sandi salah.');
            redirect('login');
        }
        if ($u['status'] !== 'active') { flash('error', 'Akun belum aktif. Verifikasi email dulu.'); redirect('login'); }
        Auth::login($u);
        Logger::audit((int)$u['id'], $u['role'], 'auth.login', 'users', (int)$u['id']);
        redirect($u['role'] === 'user' ? '' : 'admin');
    }

    public function register(): void {
        if (Auth::check()) redirect('');
        View::render('auth/register', ['title' => 'Daftar — FanzPay', 'error' => flash('error')], 'auth');
    }

    public function doRegister(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('register'); }
        [$clean, $errors] = Validator::validate($_POST, [
            'name' => 'required|max:100', 'phone' => 'required|phone',
            'email' => 'required|email|max:190', 'password' => 'required|min:8|max:255',
        ]);
        $clean['phone'] = Validator::normalizePhone($clean['phone']);
        if ($errors) { flash('error', reset($errors)); foreach ($clean as $k => $v) if ($k !== 'password') flash('old_' . $k, $v); redirect('register'); }
        if (DB::one('SELECT id FROM users WHERE email = ? OR phone = ?', [$clean['email'], $clean['phone']])) {
            flash('error', 'Email/nomor HP sudah terdaftar.'); redirect('register');
        }
        $public = new_public_id();
        DB::tx(function () use ($clean, $public) {
            $pdo = DB::pdo();
            $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status) VALUES ('user',?,?,?,?,?,'pending')")->execute([
                $clean['name'], $clean['phone'], $clean['email'], password_hash($_POST['password'], PASSWORD_DEFAULT), $public,
            ]);
            $uid = (int)$pdo->lastInsertId();
            $pdo->prepare('INSERT INTO wallets (user_id,balance,locked) VALUES (?,?,?)')->execute([$uid, 0, 0]);
        });
        $devOtp = OtpService::issue($clean['email'], 'register');
        Session_store($clean['email']);
        View::render('auth/verify', ['title' => 'Verifikasi Email', 'email' => $clean['email'], 'dev_otp' => config('app.env') === 'production' ? null : $devOtp, 'error' => null], 'auth');
    }

    public function verify(): void {
        $email = $_SESSION['pending_email'] ?? '';
        if ($email === '') redirect('register');
        View::render('auth/verify', ['title' => 'Verifikasi Email', 'email' => $email, 'dev_otp' => null, 'error' => flash('error')], 'auth');
    }

    public function doVerify(): void {
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa.'); redirect('verify'); }
        $email = $_SESSION['pending_email'] ?? (string)($_POST['email'] ?? '');
        $r = OtpService::consume($email, (string)($_POST['otp'] ?? ''), 'register');
        if (!$r['ok']) { flash('error', $r['message']); redirect('verify'); }
        DB::exec("UPDATE users SET status='active', email_verified_at=NOW() WHERE email=?", [$email]);
        $u = DB::one('SELECT * FROM users WHERE email=?', [$email]);
        unset($_SESSION['pending_email']);
        if ($u) { Auth::login($u); Logger::audit((int)$u['id'], 'user', 'auth.verified', 'users', (int)$u['id']); }
        redirect('');
    }

    public function logout(): void {
        $u = Auth::user();
        if ($u) Logger::audit((int)$u['id'], $u['role'], 'auth.logout', 'users', (int)$u['id']);
        Auth::logout();
        redirect('login');
    }

    /**
     * Pendaftaran Super Admin pertama via web: GET /setup-super
     * Hanya aktif saat BELUM ada super_admin di database.
     * Setelah ada 1 saja, halaman terkunci permanen.
     */
    public function setupSuper(): void {
        if (Auth::check()) redirect('');
        if (self::superAdminExists()) {
            // Super admin hanya boleh SATU akun: URL ini mati total setelah ada.
            Logger::audit(null, null, 'auth.setup_super.blocked', 'users', null, 'fail', ['ip' => client_ip()]);
            http_response_code(404);
            View::render('errors/404', ['title' => 'Tidak ditemukan'], 'public');
            return;
        }
        View::render('auth/setup-super', ['title' => 'Setup Super Admin — FanzPay', 'error' => flash('error')], 'auth');
    }

    public function doSetupSuper(): void {
        // Kunci utama: hanya 1 super admin — cek sebelum validasi lain agar selalu 404 saat terkunci.
        if (self::superAdminExists()) {
            Logger::audit(null, null, 'auth.setup_super.blocked', 'users', null, 'fail', ['ip' => client_ip()]);
            http_response_code(404);
            View::render('errors/404', ['title' => 'Tidak ditemukan'], 'public');
            return;
        }
        if (!CSRF::check($_POST['_csrf'] ?? null)) { flash('error', 'Sesi kedaluwarsa. Coba lagi.'); redirect('setup-super'); }
        $rl = RateLimiter::hit('setup-super:' . client_ip(), 5, 600);
        if (!$rl['allowed']) { flash('error', 'Terlalu banyak percobaan. Coba lagi nanti.'); redirect('setup-super'); }
        [$clean, $errors] = Validator::validate($_POST, [
            'name' => 'required|max:100', 'phone' => 'required|phone',
            'email' => 'required|email|max:190', 'password' => 'required|min:8|max:255',
        ]);
        $clean['phone'] = Validator::normalizePhone($clean['phone'] ?? '');
        if ($errors) { flash('error', reset($errors)); foreach ($clean as $k => $v) if ($k !== 'password') flash('old_' . $k, $v); redirect('setup-super'); }
        if (DB::one('SELECT id FROM users WHERE email = ? OR phone = ?', [$clean['email'], $clean['phone']])) {
            flash('error', 'Email/nomor HP sudah terdaftar.'); redirect('setup-super');
        }
        // Double-check race: kunci via transaksi — jika sudah ada, batalkan.
        try {
            $uid = 0;
            DB::tx(function () use ($clean, &$uid) {
                $exists = DB::one('SELECT id FROM users WHERE role = ? LIMIT 1', ['super_admin']);
                if ($exists) throw new \RuntimeException('Setup sudah ditutup.');
                $pdo = DB::pdo();
                $pdo->prepare("INSERT INTO users (role,name,phone,email,password_hash,public_id,status,email_verified_at) VALUES ('super_admin',?,?,?,?,?,'active',NOW())")->execute([
                    $clean['name'], $clean['phone'], $clean['email'], password_hash($_POST['password'], PASSWORD_DEFAULT), new_public_id(),
                ]);
                $uid = (int)$pdo->lastInsertId();
                $pdo->prepare('INSERT INTO wallets (user_id,balance,locked) VALUES (?,?,?)')->execute([$uid, 0, 0]);
            });
            $u = DB::one('SELECT * FROM users WHERE id = ?', [$uid]);
            if ($u) {
                Auth::login($u);
                Logger::audit((int)$u['id'], 'super_admin', 'auth.setup_super.created', 'users', (int)$u['id']);
            }
            flash('ok', 'Super Admin berhasil dibuat. Selamat datang, Founding Circle.');
            redirect('admin');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage() === 'Setup sudah ditutup.' ? 'Setup Super Admin sudah ditutup.' : 'Gagal membuat Super Admin. Coba lagi.');
            redirect($e->getMessage() === 'Setup sudah ditutup.' ? 'login' : 'setup-super');
        }
    }

    private static function superAdminExists(): bool {
        try {
            return DB::one('SELECT id FROM users WHERE role = ? LIMIT 1', ['super_admin']) !== null;
        } catch (\Throwable $e) { return true; }
    }
}

function Session_store(string $email): void {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $_SESSION['pending_email'] = $email;
}
