<?php
/** @var array $user */ /** @var int $balance */ /** @var array $tokens */ /** @var array $policy */
/** @var string|null $new_token */ /** @var string $endpoint */ /** @var string|null $ok */ /** @var string|null $error */
?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<?php if (!empty($new_token)): ?>
<div class="panel" style="border:2px solid var(--ok,#157F53)">
  <h3>Token baru — salin sekarang (hanya tampil sekali)</h3>
  <code id="mcp-token" style="display:block;word-break:break-all;padding:10px;background:var(--bg,#F6F8FC);border-radius:8px"><?= e($new_token) ?></code>
  <p class="muted">Tempel token ini sebagai <b>Bearer token</b> di konektor MCP AI Anda. Jangan simpan di tempat yang bisa dibaca orang lain.</p>
  <button class="btn btn-ghost" type="button" onclick="navigator.clipboard&&navigator.clipboard.writeText(document.getElementById('mcp-token').innerText)">Salin token</button>
</div>
<?php endif; ?>

<div class="panel">
  <h3>Koneksi AI (MCP)</h3>
  <p class="muted">Hubungkan akun FanzPay ke ChatGPT, Claude, atau AI lain yang mendukung MCP. Endpoint: <code><?= e($endpoint) ?></code></p>
  <p><a class="btn btn-ghost" href="<?= url('panduan-mcp') ?>">Buka tutorial setup (nol → berhasil)</a>
  <a class="btn btn-ghost" href="<?= url('mcp-kebijakan') ?>">Atur batas pembelian otomatis</a></p>
  <p class="muted">Status pembelian otomatis: <b><?= !empty($policy['enabled']) ? 'AKTIF' : 'NONAKTIF (default)' ?></b>
  <?php if (!empty($policy['enabled'])): ?> · maks <?= rupiah((int)($policy['max_per_tx'] ?? 0)) ?>/trx · <?= rupiah((int)($policy['max_daily'] ?? 0)) ?>/hari<?php endif; ?></p>
</div>

<div class="panel">
  <h3>Buat token baru</h3>
  <form method="post" action="<?= url('mcp-koneksi/buat') ?>">
    <?= \App\Core\CSRF::field() ?>
    <div class="grid2">
      <div class="field"><label>Nama koneksi (mis. ChatGPT saya)</label><input name="name" maxlength="80" placeholder="ChatGPT saya" required></div>
      <div class="field"><label>AI provider</label>
        <select name="client">
          <option value="chatgpt">ChatGPT</option>
          <option value="claude">Claude</option>
          <option value="generic" selected>Lainnya / generik</option>
        </select>
      </div>
    </div>
    <label class="perm"><input type="checkbox" name="allow_purchase" value="1"> Izinkan pembelian PPOB otomatis (tetap dibatasi kebijakan — default hanya baca)</label>
    <div style="height:10px"></div>
    <button class="btn btn-primary" type="submit">Buat token</button>
  </form>
</div>

<div class="panel">
  <h3>Token aktif (maks 20 ditampilkan)</h3>
  <?php if (empty($tokens)): ?><p class="muted">Belum ada token. Buat satu di atas, lalu ikuti tutorial.</p><?php else: ?>
  <div class="table-wrap"><table>
    <thead><tr><th>Nama</th><th>Client</th><th>Scope</th><th>Kedaluwarsa</th><th>Terakhir dipakai</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($tokens as $t): ?>
      <tr>
        <td><?= e($t['name']) ?><?= !empty($t['revoked_at']) ? ' <span class="badge">dicabut</span>' : '' ?></td>
        <td><?= e($t['client']) ?></td>
        <td><code><?= e($t['scopes']) ?></code></td>
        <td><?= e($t['expires_at']) ?></td>
        <td><?= e($t['last_used_at'] ?? '—') ?></td>
        <td><?php if (empty($t['revoked_at'])): ?>
          <form method="post" action="<?= url('mcp-koneksi/' . (int)$t['id'] . '/cabut') ?>" onsubmit="return confirm('Cabut token ini? AI yang memakainya langsung terputus.')">
            <?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Cabut</button>
          </form><?php endif; ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
