<?php
use Asl\I18n;
use Asl\Products;
?>
<div class="page-head">
  <h1><?= e(t('admin.products')) ?></h1>
  <a class="btn" href="<?= e(admin_url('products/new')) ?>">+ <?= e(t('admin.new_product')) ?></a>
</div>
<?php if (!$products): ?>
  <p class="muted"><?= e(t('admin.none')) ?></p>
<?php else: ?>
<div class="table-scroll">
<table class="table">
  <thead><tr><th></th><th><?= e(t('admin.name')) ?></th><th><?= e(t('admin.price_kg')) ?></th><th><?= e(t('admin.price_l')) ?></th><th><?= e(t('admin.units')) ?></th><th><?= e(t('admin.status')) ?></th></tr></thead>
  <tbody>
  <?php foreach ($products as $p): ?>
    <tr>
      <td><img class="thumb-sm" src="<?= e(Products::imageUrl($p['cover'])) ?>" alt="" width="48" height="48"></td>
      <td>
        <a href="<?= e(admin_url('products/' . $p['id'])) ?>"><strong><?= e(I18n::field($p, 'name')) ?></strong></a><br>
        <small class="muted"><?php foreach (I18n::LOCALES as $l): if ($l !== I18n::locale()): ?><span lang="<?= e($l) ?>"><?= e($p['name_' . $l]) ?></span> · <?php endif; endforeach; ?></small>
      </td>
      <td class="num"><?= $p['price_per_kg_cents'] !== null ? e(money((int) $p['price_per_kg_cents'])) : '—' ?></td>
      <td class="num"><?= $p['price_per_l_cents'] !== null ? e(money((int) $p['price_per_l_cents'])) : '—' ?></td>
      <td dir="ltr"><?= e(str_replace(',', ', ', $p['units'])) ?></td>
      <td><?= (int) $p['is_active'] ? e(t('admin.active')) : '<span class="muted">' . e(t('admin.hidden')) . '</span>' ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
