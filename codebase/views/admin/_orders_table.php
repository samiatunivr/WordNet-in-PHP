<?php if (!$orders): ?>
  <p class="muted"><?= e(t('admin.none')) ?></p>
<?php else: ?>
<div class="table-scroll">
<table class="table">
  <thead><tr><th>#</th><th><?= e(t('admin.date')) ?></th><th><?= e(t('admin.customer')) ?></th><th><?= e(t('admin.total')) ?></th><th><?= e(t('admin.status')) ?></th></tr></thead>
  <tbody>
  <?php foreach ($orders as $o): ?>
    <tr>
      <td><a href="<?= e(admin_url('orders/' . $o['id'])) ?>"><?= (int) $o['id'] ?></a></td>
      <td dir="ltr"><?= e($o['created_at']) ?> UTC</td>
      <td><?= e($o['customer_name'] ?? '—') ?><br><small class="muted" dir="ltr"><?= e($o['customer_email'] ?? '') ?></small></td>
      <td class="num"><?= e(money((int) $o['total_cents'])) ?></td>
      <td><span class="status status-<?= e($o['status']) ?>"><?= e(t('admin.status.' . $o['status'])) ?></span></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
