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
        'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h13v3"/><rect x="3" y="7" width="18" height="13" rx="2"/><circle cx="17" cy="13.5" r="1.2"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'qr' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3h-3zM20 14h1M14 20h1M18 18h3v3h-3z"/>',
        'bell' => '<path d="M6 9a6 6 0 1 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'zap' => '<path d="M13 2 4 14h6l-1 8 9-12h-6l1-8z"/>',
        'wifi' => '<path d="M2 9a15 15 0 0 1 20 0M5.5 12.5a10 10 0 0 1 13 0M9 16a5 5 0 0 1 6 0"/><circle cx="12" cy="19" r="1.3"/>',
        'phone' => '<rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M11 18.5h2"/>',
        'drop' => '<path d="M12 3s6 6.2 6 11a6 6 0 0 1-12 0c0-4.8 6-11 6-11z"/>',
        'shield' => '<path d="M12 2 4 6v6c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V6l-8-4z"/>',
        'tv' => '<rect x="2" y="5" width="20" height="13" rx="2"/><path d="M9 21h6"/>',
        'game' => '<rect x="2" y="7" width="20" height="11" rx="5.5"/><path d="M7 11v4M5 13h4"/><circle cx="16" cy="12" r="1.2"/><circle cx="18.5" cy="14.5" r="1.2"/>',
        'out' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5M21 12H9"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'check' => '<path d="m4 12.5 5 5L20 6.5"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'chev' => '<path d="m9 6 6 6-6 6"/>',
        'card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>',
        'inbox' => '<path d="M3 13l3-8h12l3 8v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6z"/><path d="M3 13h6l1.5 2h3L15 13h6"/>',
        'gear' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2-1.2L14 3h-4l-.5 2.6a7 7 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6A7 7 0 0 0 5 12c0 .4 0 .8.1 1.2l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2 1.2L10 21h4l.5-2.6a7 7 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2z"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/>',
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
