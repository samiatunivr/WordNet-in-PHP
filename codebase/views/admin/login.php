<div class="login-box">
  <h1><?= e(t('admin.login')) ?></h1>
  <form method="post" action="<?= e(admin_url('login')) ?>" autocomplete="on">
    <?= csrf_field() ?>
    <label><?= e(t('admin.email')) ?>
      <input type="email" name="email" required maxlength="190" autocomplete="username" dir="ltr">
    </label>
    <label><?= e(t('admin.password')) ?>
      <input type="password" name="password" required maxlength="200" autocomplete="current-password" dir="ltr">
    </label>
    <label><?= e(t('admin.2fa_code')) ?> <small class="muted">(<?= e(t('admin.if_enabled')) ?>)</small>
      <input type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" maxlength="7" autocomplete="one-time-code" dir="ltr">
    </label>
    <button class="btn btn-block" type="submit"><?= e(t('admin.sign_in')) ?></button>
  </form>
</div>
