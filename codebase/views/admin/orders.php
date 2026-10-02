<?php use Asl\Orders; ?>
<div class="page-head">
  <h1><?= e(t('admin.orders')) ?></h1>
  <form method="get" action="<?= e(admin_url('orders')) ?>" class="inline-form">
    <select name="status" aria-label="<?= e(t('admin.status')) ?>">
      <option value=""><?= e(t('admin.all_but_pending')) ?></option>
      <?php foreach (Orders::STATUSES as $s): ?>
        <option value="<?= e($s) ?>"<?= $s === $status ? ' selected' : '' ?>><?= e(t('admin.status.' . $s)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-small" type="submit"><?= e(t('admin.filter')) ?></button>
  </form>
</div>
<?= Asl\View::capture('admin/_orders_table', ['orders' => $orders]) ?>
<?php if ($pages > 1): ?>
  <nav class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a href="<?= e(admin_url('orders') . '?' . http_build_query(['status' => $status, 'page' => $i])) ?>"<?= $i === $page ? ' class="active"' : '' ?>><?= $i ?></a>
    <?php endfor; ?>
  </nav>
<?php endif; ?>
