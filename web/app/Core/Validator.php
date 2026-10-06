<?php
declare(strict_types=1);

namespace App\Core;

/** Validasi input sederhana + normalisasi nomor HP Indonesia. */
final class Validator {
    /** @return array{0: array, 1: array<string,string>} [clean, errors] */
    public static function validate(array $input, array $rules): array {
        $clean = [];
        $errors = [];
        foreach ($rules as $field => $rule) {
            $v = trim((string)($input[$field] ?? ''));
            $parts = explode('|', $rule);
            foreach ($parts as $r) {
                if ($r === 'required' && $v === '') $errors[$field] = 'Wajib diisi.';
                if (str_starts_with($r, 'min:') && strlen($v) < (int)substr($r, 4)) $errors[$field] = 'Terlalu pendek.';
                if (str_starts_with($r, 'max:') && strlen($v) > (int)substr($r, 4)) $errors[$field] = 'Terlalu panjang.';
                if ($r === 'email' && $v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) $errors[$field] = 'Format email tidak valid.';
                if ($r === 'phone' && $v !== '' && !preg_match('/^(\+?62|0)8\d{7,11}$/', preg_replace('/[\s\-]/', '', $v))) $errors[$field] = 'Nomor HP tidak valid.';
                if ($r === 'pin' && !preg_match('/^\d{6}$/', $v)) $errors[$field] = 'PIN harus 6 digit angka.';
                if ($r === 'int' && $v !== '' && !preg_match('/^\d+$/', $v)) $errors[$field] = 'Harus berupa angka.';
            }
            $clean[$field] = $v;
        }
        return [$clean, $errors];
    }

    public static function normalizePhone(string $raw): string {
        $p = preg_replace('/[\s\-\.]/', '', trim($raw));
        if (str_starts_with($p, '+62')) $p = '0' . substr($p, 3);
        elseif (str_starts_with($p, '62')) $p = '0' . substr($p, 2);
        return $p;
    }
}
