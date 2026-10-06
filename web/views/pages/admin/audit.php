<?php /** @var array $rows */ ?>
<div class="panel"><div class="table-wrap"><table class="tbl">
  <tr><th>Waktu</th><th>Aktor</th><th>Aksi</th><th>Target</th><th>Hasil</th><th>IP</th></tr>
  <?php foreach ($rows as $a): ?>
    <tr><td><small><?= e($a['created_at']) ?></small></td><td><?= e($a['actor_name'] ?? ('#' . ($a['actor_id'] ?? '–'))) ?><br><small><?= e((string)($a['actor_role'] ?? '')) ?></small></td><td class="mono"><?= e($a['action']) ?></td><td><?= e((string)($a['target'] ?? '') . ' ' . (string)($a['target_id'] ?? '')) ?></td><td><?= e($a['result']) ?></td><td class="mono"><?= e($a['ip']) ?></td></tr>
  <?php endforeach; ?>
</table></div></div>
