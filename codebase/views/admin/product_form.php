<?php
use Asl\I18n;
use Asl\Money;
use Asl\Products;
use Asl\Units;

$src = $old ?? $product ?? [];
$v = static fn (string $k, string $d = '') => (string) ($src[$k] ?? $d);
if ($old) {
    $kg = $v('price_per_kg');
    $lt = $v('price_per_l');
    $units = (array) ($old['units'] ?? []);
    $active = ($old['is_active'] ?? '') === '1';
    $featured = ($old['is_featured'] ?? '') === '1';
} else {
    $kg = Money::toInput(isset($product['price_per_kg_cents']) ? (int) $product['price_per_kg_cents'] : null);
    $lt = Money::toInput(isset($product['price_per_l_cents']) ? (int) $product['price_per_l_cents'] : null);
    $units = $product ? explode(',', $product['units']) : ['g', 'kg'];
    $active = $product ? (int) $product['is_active'] === 1 : true;
    $featured = $product ? (int) $product['is_featured'] === 1 : false;
}
$action = $product ? admin_url('products/' . $product['id']) : admin_url('products/new');
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <?php if ($product): ?>
    <a class="btn btn-outline" href="<?= e(url('product/' . rawurlencode($product['slug']), 'ar')) ?>" target="_blank" rel="noopener"><?= e(t('admin.preview')) ?></a>
  <?php endif; ?>
</div>

<form method="post" action="<?= e($action) ?>" class="admin-form">
  <?= csrf_field() ?>
  <fieldset>
    <legend><?= e(t('admin.content')) ?></legend>
    <p class="hint"><?= e(t('admin.content_hint')) ?></p>
    <div class="lang-columns">
      <?php foreach (I18n::LOCALES as $l): ?>
        <div class="lang-col" lang="<?= e($l) ?>" dir="<?= e(I18n::dir($l)) ?>">
          <h3><?= e(I18n::NAMES[$l]) ?></h3>
          <label><?= e(t('admin.name')) ?> *
            <input type="text" name="name_<?= e($l) ?>" value="<?= e($v('name_' . $l)) ?>" required maxlength="200">
          </label>
          <label><?= e(t('admin.summary')) ?>
            <textarea name="summary_<?= e($l) ?>" rows="2" maxlength="500"><?= e($v('summary_' . $l)) ?></textarea>
          </label>
          <label><?= e(t('admin.description')) ?>
            <textarea name="description_<?= e($l) ?>" rows="8" maxlength="20000"><?= e($v('description_' . $l)) ?></textarea>
          </label>
        </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <fieldset>
    <legend><?= e(t('admin.pricing')) ?></legend>
    <div class="form-row">
      <label><?= e(t('admin.slug')) ?> *
        <input type="text" name="slug" value="<?= e($v('slug')) ?>" required maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" dir="ltr" placeholder="sidr-hadramout">
      </label>
      <label><?= e(t('admin.price_kg')) ?> (<?= e(strtoupper(Asl\Config::currency())) ?>)
        <input type="text" name="price_per_kg" value="<?= e($kg) ?>" inputmode="decimal" maxlength="12" dir="ltr" placeholder="89.00">
      </label>
      <label><?= e(t('admin.price_l')) ?> (<?= e(strtoupper(Asl\Config::currency())) ?>)
        <input type="text" name="price_per_l" value="<?= e($lt) ?>" inputmode="decimal" maxlength="12" dir="ltr" placeholder="120.00">
      </label>
      <label><?= e(t('admin.sort_order')) ?>
        <input type="number" name="sort_order" value="<?= e($v('sort_order', '0')) ?>" min="-9999" max="9999">
      </label>
    </div>
    <div class="checks">
      <span class="label"><?= e(t('admin.units')) ?>:</span>
      <?php foreach (array_keys(Units::UNITS) as $u): ?>
        <label class="check"><input type="checkbox" name="units[]" value="<?= e($u) ?>"<?= in_array($u, $units, true) ? ' checked' : '' ?>> <?= e(Units::label($u)) ?></label>
      <?php endforeach; ?>
    </div>
    <p class="hint"><?= e(t('admin.units_hint')) ?></p>
    <div class="checks">
      <label class="check"><input type="checkbox" name="is_active" value="1"<?= $active ? ' checked' : '' ?>> <?= e(t('admin.active')) ?></label>
      <label class="check"><input type="checkbox" name="is_featured" value="1"<?= $featured ? ' checked' : '' ?>> <?= e(t('admin.featured')) ?></label>
    </div>
  </fieldset>
  <button class="btn btn-large" type="submit"><?= e(t('admin.save')) ?></button>
</form>

<?php if ($product): ?>
  <section id="images" class="admin-section">
    <h2><?= e(t('admin.images')) ?></h2>
    <?php if ($images): ?>
      <ul class="image-list">
        <?php foreach ($images as $i => $img): ?>
          <li>
            <img src="<?= e(Products::imageUrl($img['filename'])) ?>" alt="" width="160" height="160">
            <div class="image-actions">
              <?php if ($i > 0): ?>
                <form method="post" action="<?= e(admin_url('images/' . $img['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="direction" value="up"><button class="btn btn-small btn-outline" type="submit" aria-label="<?= e(t('admin.move_up')) ?>">↑</button></form>
              <?php endif; ?>
              <?php if ($i < count($images) - 1): ?>
                <form method="post" action="<?= e(admin_url('images/' . $img['id'] . '/move')) ?>"><?= csrf_field() ?><input type="hidden" name="direction" value="down"><button class="btn btn-small btn-outline" type="submit" aria-label="<?= e(t('admin.move_down')) ?>">↓</button></form>
              <?php endif; ?>
              <form method="post" action="<?= e(admin_url('images/' . $img['id'] . '/delete')) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>"><?= csrf_field() ?><button class="btn btn-small btn-danger" type="submit"><?= e(t('admin.delete')) ?></button></form>
            </div>
            <?php if ($i === 0): ?><span class="tag"><?= e(t('admin.cover')) ?></span><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <form method="post" action="<?= e(admin_url('products/' . $product['id'] . '/images')) ?>" enctype="multipart/form-data" class="upload-form">
      <?= csrf_field() ?>
      <label><?= e(t('admin.upload_images')) ?>
        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
      </label>
      <p class="hint"><?= e(t('admin.upload_hint', ['mb' => (int) round(((int) Asl\Config::get('max_upload_bytes', 5242880)) / 1048576)])) ?></p>
      <button class="btn" type="submit"><?= e(t('admin.upload')) ?></button>
    </form>
  </section>

  <section class="admin-section danger-zone">
    <h2><?= e(t('admin.danger_zone')) ?></h2>
    <form method="post" action="<?= e(admin_url('products/' . $product['id'] . '/delete')) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-danger" type="submit"><?= e(t('admin.delete_product')) ?></button>
    </form>
  </section>
<?php endif; ?>
