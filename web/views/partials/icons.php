<?php
declare(strict_types=1);
/** Ikon SVG inline konsisten (stroke 1.8, 20px). Tanpa emoji sebagai ikon. */
function icon(string $name, int $size = 20): string {
    $p = [
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'swap' => '<path d="M7 4 3 8l4 4"/><path d="M3 8h14"/><path d="m17 12 4 4-4 4"/><path d="M21 16H7"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 5-5.5 8-5.5s6.5 1.5 8 5.5"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.8-5 6.5-5s5.5 1.5 6.5 5"/><circle cx="17" cy="9" r="2.5"/><path d="M16 15.2c2.3.3 4.3 1.7 5.5 4.3"/>',
        'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h13v3"/><rect x="3" y="7" width="18" height="13" rx="2"/><circle cx="17" cy="13.5" r="1.2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14h1M14 20h1M18 18h3v3h-3z"/>',
        'bell' => '<path d="M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'zap' => '<path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z"/>',
        'wifi' => '<path d="M2 9a15 15 0 0 1 20 0M5.5 12.5a10 10 0 0 1 13 0M9 16a5 5 0 0 1 6 0"/><circle cx="12" cy="19" r="1.3"/>',
        'phone' => '<rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18.5h2"/>',
        'smartphone' => '<rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18.5h2"/><path d="M10 5.5h4"/>',
        'drop' => '<path d="M12 3s6 6.2 6 11a6 6 0 0 1-12 0c0-4.8 6-11 6-11z"/>',
        'droplet' => '<path d="M12 3s6 6.2 6 11a6 6 0 0 1-12 0c0-4.8 6-11 6-11z"/><path d="M9.5 14a2.5 2.5 0 0 0 2.5 2.5"/>',
        'shield' => '<path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-4z"/>',
        'shieldCheck' => '<path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-4z"/><path d="m9 11.5 2 2 4-4.5"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2.5"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'tv' => '<rect x="2" y="5" width="20" height="13" rx="2"/><path d="M9 21h6"/>',
        'game' => '<rect x="2" y="7" width="20" height="11" rx="5.5"/><path d="M7 11v4M5 13h4"/><circle cx="16" cy="12" r="1.2"/><circle cx="18.5" cy="14.5" r="1.2"/>',
        'gamepad' => '<rect x="2" y="7" width="20" height="11" rx="5.5"/><path d="M7 11v4M5 13h4"/><circle cx="16" cy="12" r="1.2"/><circle cx="18.5" cy="14.5" r="1.2"/>',
        'out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'check' => '<path d="m4 12.5 5 5L20 6.5"/>',
        'checkCircle' => '<circle cx="12" cy="12" r="9"/><path d="m8.5 12.2 2.4 2.4 4.8-5.2"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'chev' => '<path d="m9 6 6 6-6 6"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'bank' => '<path d="M3 9.5 12 4l9 5.5"/><path d="M4 10v8M8.5 10v8M12 10v8M15.5 10v8M20 10v8"/><path d="M2.5 20.5h19"/>',
        'inbox' => '<path d="M3 13l3-8h12l3 8v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6z"/><path d="M3 13h6l1.5 2h3L15 13h6"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14 3h-4l-.5 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6A7 7 0 0 0 5 12c0 .4 0 .8.1 1.2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2L10 21h4l.5-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2z"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/>',
        'receipt' => '<path d="M5 3h14v18l-2.3-1.5L14.4 21l-2.4-1.5L9.6 21l-2.3-1.5L5 21V3z"/><path d="M9 8h6M9 12h6"/>',
        'tag' => '<path d="m3 3 8.5.5L21 13l-7.5 7.5L4 11 3 3z"/><circle cx="8.5" cy="8.5" r="1.4"/>',
        'refresh' => '<path d="M20 12a8 8 0 1 1-2.3-5.6"/><path d="M20 3v4h-4"/>',
        'headset' => '<path d="M4 13a8 8 0 0 1 16 0"/><rect x="3" y="13" width="4" height="7" rx="1.5"/><rect x="17" y="13" width="4" height="7" rx="1.5"/><path d="M19 20a4 4 0 0 1-4 2h-2"/>',
        'file' => '<path d="M6 2h8l4 4v16H6V2z"/><path d="M14 2v4h4"/><path d="M9 12h6M9 16h6"/>',
        'moon' => '<path d="M20 14.5A8.5 8.5 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5z"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'download' => '<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M4 19h16"/>',
        'arrowR' => '<path d="M4 12h15"/><path d="m13 6 6 6-6 6"/>',
        'arrowL' => '<path d="M20 12H5"/><path d="m11 6-6 6 6 6"/>',
        'spark' => '<path d="M12 2v5M12 17v5M2 12h5M17 12h5"/><circle cx="12" cy="12" r="3.5"/>',
        'eye' => '<path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.8"/>',
        'eyeOff' => '<path d="M10.6 5.2A16.4 16.4 0 0 1 12 5.5c6.5 0 10 6.5 10 6.5a17.6 17.6 0 0 1-3.1 3.8M6.6 6.6A16.8 16.8 0 0 0 2 12s3.5 6.5 10 6.5c1.4 0 2.7-.3 3.9-.8"/><path d="m3 3 18 18"/>',
        'send' => '<path d="m3 11 18-7-7 18-2.5-7.5L3 11z"/>',
        'star' => '<path d="m12 2.5 2.9 6 6.6.9-4.8 4.6 1.2 6.5L12 17.4 6.1 20.5l1.2-6.5L2.5 9.4l6.6-.9 2.9-6z"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2.5"/><path d="m2.5 6.5 9.5 7 9.5-7"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13.5 13.5 0 0 1 0 18M12 3a13.5 13.5 0 0 0 0 18"/>',
        'arrowUp' => '<path d="M12 20V5"/><path d="m5 12 7-7 7 7"/>',
        'play' => '<rect x="2" y="4" width="20" height="16" rx="4"/><path d="m10 9 5 3-5 3V9z"/>',
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.1"/>',
        'youtube' => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9.5 5 2.5-5 2.5v-5z"/>',
        'atSign' => '<circle cx="12" cy="12" r="4"/><path d="M16 12v1.5a2.5 2.5 0 0 0 5 0V12a9 9 0 1 0-3.5 7.1"/>',
        'shieldLock' => '<path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-4z"/><rect x="9" y="11" width="6" height="5" rx="1.2"/><path d="M10.2 11V9.8a1.8 1.8 0 0 1 3.6 0V11"/>',
    ];
    $inner = $p[$name] ?? $p['grid'];
    return '<svg class="ic" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

function status_badge(string $s): string {
    $map = ['success' => 'ok', 'approved' => 'ok', 'active' => 'ok', 'pending' => 'warn', 'processing' => 'warn', 'failed' => 'bad', 'expired' => 'mut', 'refunded' => 'info', 'reversed' => 'info', 'rejected' => 'bad', 'suspended' => 'bad'];
    $label = ['success' => 'Berhasil', 'approved' => 'Disetujui', 'active' => 'Aktif', 'pending' => 'Menunggu', 'processing' => 'Diproses', 'failed' => 'Gagal', 'expired' => 'Kedaluwarsa', 'refunded' => 'Refund', 'reversed' => 'Dibalik', 'rejected' => 'Ditolak', 'suspended' => 'Ditangguhkan'];
    $c = $map[$s] ?? 'mut';
    return '<span class="badge b-' . $c . '">' . e($label[$s] ?? ucfirst($s)) . '</span>';
}

function kind_label(string $k): string {
    return ['deposit' => 'Deposit', 'withdraw' => 'Tarik Dana', 'transfer' => 'Transfer', 'ppob' => 'PPOB', 'refund' => 'Refund'][$k] ?? $k;
}
