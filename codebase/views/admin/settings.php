<?php
use Asl\Money;
use Asl\Settings;
$cur = strtoupper(Asl\Config::currency());
?>
<h1><?= e(t('admin.settings')) ?></h1>
<form method="post" action="<?= e(admin_url('settings')) ?>" class="admin-form narrow-form">
  <?= csrf_field() ?>
  <fieldset>
    <legend><?= e(t('admin.shipping')) ?></legend>
    <label><?= e(t('admin.shipping_flat')) ?> (<?= e($cur) ?>)
      <input type="text" name="shipping_flat" value="<?= e(Money::toInput((int) Settings::get('shipping_flat_cents'))) ?>" inputmode="decimal" dir="ltr">
    </label>
    <label><?= e(t('admin.free_shipping_from')) ?> (<?= e($cur) ?>)
      <input type="text" name="free_shipping_from" value="<?= e(Money::toInput((int) Settings::get('free_shipping_from_cents'))) ?>" inputmode="decimal" dir="ltr">
    </label>
    <p class="hint"><?= e(t('admin.free_shipping_hint')) ?></p>
    <label><?= e(t('admin.shipping_countries')) ?>
      <input type="text" name="shipping_countries" value="<?= e(Settings::get('shipping_countries')) ?>" dir="ltr" maxlength="500">
    </label>
    <p class="hint"><?= e(t('admin.shipping_countries_hint')) ?></p>
  </fieldset>
  <fieldset>
    <legend><?= e(t('footer.contact')) ?></legend>
    <label><?= e(t('admin.email')) ?>
      <input type="email" name="contact_email" value="<?= e(Settings::get('contact_email')) ?>" dir="ltr" maxlength="190">
    </label>
    <label><?= e(t('admin.phone')) ?>
      <input type="text" name="contact_phone" value="<?= e(Settings::get('contact_phone')) ?>" dir="ltr" maxlength="40">
    </label>
  </fieldset>
  <fieldset>
    <legend><?= e(t('admin.invoice_details')) ?></legend>
    <p class="hint"><?= e(t('admin.invoice_details_hint')) ?></p>
    <label><?= e(t('admin.company_name')) ?>
      <input type="text" name="company_name" value="<?= e(Settings::get('company_name')) ?>" maxlength="190">
    </label>
    <label><?= e(t('admin.company_address')) ?>
      <textarea name="company_address" rows="3" maxlength="1000"><?= e(Settings::get('company_address')) ?></textarea>
    </label>
    <label><?= e(t('admin.vat_number')) ?>
      <input type="text" name="vat_number" value="<?= e(Settings::get('vat_number')) ?>" dir="ltr" maxlength="40" placeholder="NL000099998B57">
    </label>
    <label><?= e(t('admin.coc_number')) ?>
      <input type="text" name="coc_number" value="<?= e(Settings::get('coc_number')) ?>" dir="ltr" maxlength="40">
    </label>
    <label><?= e(t('admin.vat_rate')) ?> (%)
      <input type="text" name="vat_rate" value="<?= e(rtrim(rtrim(number_format((int) Settings::get('vat_rate_bp') / 100, 2, '.', ''), '0'), '.')) ?>" inputmode="decimal" dir="ltr" maxlength="5">
    </label>
    <p class="hint"><?= e(t('admin.vat_rate_hint')) ?></p>
    <label class="check"><input type="checkbox" name="invoice_bcc" value="1"<?= Settings::get('invoice_bcc') === '1' ? ' checked' : '' ?>> <?= e(t('admin.invoice_bcc')) ?></label>
  </fieldset>
  <button class="btn btn-large" type="submit"><?= e(t('admin.save')) ?></button>
</form>
