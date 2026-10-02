<h1><?= e(t('admin.dashboard')) ?></h1>
<div class="stats">
  <div class="stat"><span class="stat-value"><?= (int) $stats['products'] ?></span><span><?= e(t('admin.products')) ?></span></div>
  <div class="stat"><span class="stat-value"><?= (int) $stats['paid'] ?></span><span><?= e(t('admin.to_fulfil')) ?></span></div>
  <div class="stat"><span class="stat-value"><?= e(money($stats['revenue'])) ?></span><span><?= e(t('admin.revenue')) ?></span></div>
  <div class="stat<?= $stats['review'] ? ' stat-warn' : '' ?>"><span class="stat-value"><?= (int) $stats['review'] ?></span><span><?= e(t('admin.status.review')) ?></span></div>
</div>
<h2><?= e(t('admin.recent_orders')) ?></h2>
<?= Asl\View::capture('admin/_orders_table', ['orders' => $recent]) ?>
