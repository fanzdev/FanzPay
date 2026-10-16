<?php
/** @var array $s */ /** @var array $tools */ /** @var array $tokens */ /** @var array $usage */ /** @var array $pending */
/** @var string|null $ok */ /** @var string|null $error */
?>
<?php if ($ok): ?><div class="alert ok"><?= e($ok) ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="alert err"><?= e($error) ?></div><?php endif; ?>
<div class="panel">
  <h3>MCP Server (Super Admin)</h3>
  <p class="muted">Endpoint: <code><?= e(url('mcp')) ?></code> · Tutorial pelanggan: <a href="<?= url('panduan-mcp') ?>">/panduan-mcp</a></p>
  <form method="post" action="<?= url($panel . '/mcp') ?>">
    <?= \App\Core\CSRF::field() ?>
    <label class="perm"><input type="checkbox" name="mcp_enabled" value="1" <?= ($s['mcp_enabled'] ?? '1') === '1' ? 'checked' : '' ?>> MCP Server aktif</label>
    <div class="grid2">
      <div class="field"><label>Batas sistem per transaksi (Rp)</label><input name="mcp_max_per_tx" type="number" min="0" value="<?= e($s['mcp_max_per_tx'] ?? '100000') ?>"></div>
      <div class="field"><label>Batas sistem harian (Rp)</label><input name="mcp_max_daily" type="number" min="0" value="<?= e($s['mcp_max_daily'] ?? '500000') ?>"></div>
      <div class="field"><label>Kategori sistem (koma)</label><input name="mcp_allowed_categories" value="<?= e($s['mcp_allowed_categories'] ?? 'pulsa,data,pln,emoney,game') ?>"></div>
      <div class="field"><label>Rate limit tools/call per menit</label><input name="mcp_rate_per_min" type="number" min="10" max="600" value="<?= e($s['mcp_rate_per_min'] ?? '60') ?>"></div>
    </div>
    <h3 style="margin-top:14px">Tools (matikan bila berisiko)</h3>
    <?php foreach ($tools as $t): ?>
      <label class="perm"><input type="checkbox" name="tools[]" value="<?= e($t) ?>" <?= ($s['mcp_tool_' . $t] ?? '1') === '1' ? 'checked' : '' ?>> <code><?= e($t) ?></code></label>
    <?php endforeach; ?>
    <div style="height:10px"></div>
    <button class="btn btn-primary" type="submit">Simpan</button>
  </form>
</div>

<div class="panel">
  <h3>Koneksi aktif (20 terbaru)</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>User</th><th>Nama</th><th>Client</th><th>Scope</th><th>Terakhir dipakai</th><th></th></tr></thead>
    <tbody>
    <?php if (empty($tokens)): ?><tr><td colspan="6" style="color:var(--mut)">Tidak ada data.</td></tr><?php endif; ?>
    <?php foreach ($tokens as $t): ?>
      <tr><td><?= e($t['email'] ?? ('#' . (int)$t['user_id'])) ?></td><td><?= e($t['name']) ?></td><td><?= e($t['client']) ?></td>
      <td><code><?= e($t['scopes']) ?></code></td><td><?= e($t['last_used_at'] ?? '—') ?></td>
      <td><form method="post" action="<?= url($panel . '/mcp/cabut/' . (int)$t['id']) ?>"><?= \App\Core\CSRF::field() ?><button class="btn btn-ghost btn-sm" type="submit">Cabut</button></form></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>

<div class="grid2">
  <div class="panel"><h3>Pemakaian 7 hari (tools/call sukses)</h3>
    <div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Call</th></tr></thead><tbody>
    <?php if (empty($usage)): ?><tr><td colspan="2" style="color:var(--mut)">Tidak ada data.</td></tr><?php endif; ?>
    <?php foreach ($usage as $r): ?><tr><td><?= e($r['d']) ?></td><td><?= (int)$r['c'] ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="panel"><h3>Transaksi MCP pending/processing</h3>
    <div class="table-wrap"><table><thead><tr><th>Ref</th><th>User</th><th>Total</th><th>Dibuat</th></tr></thead><tbody>
    <?php if (empty($pending)): ?><tr><td colspan="4" style="color:var(--mut)">Tidak ada data.</td></tr><?php endif; ?>
    <?php foreach ($pending as $r): ?><tr><td><?= e($r['reference_id']) ?></td><td><?= e($r['email'] ?? '') ?></td><td><?= rupiah((int)$r['total']) ?></td><td><?= e($r['created_at']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
</div>
