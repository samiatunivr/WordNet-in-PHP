<?php use Asl\Totp; ?>
<h1><?= e(t('admin.account')) ?></h1>
<p class="muted" dir="ltr"><?= e($user['email']) ?></p>
<div class="two-col">
  <section class="admin-section">
    <h2><?= e(t('admin.change_password')) ?></h2>
    <form method="post" action="<?= e(admin_url('account/password')) ?>" class="admin-form">
      <?= csrf_field() ?>
      <label><?= e(t('admin.current_password')) ?><input type="password" name="current_password" required autocomplete="current-password" dir="ltr"></label>
      <label><?= e(t('admin.new_password')) ?><input type="password" name="new_password" required minlength="<?= Asl\Auth::MIN_PASSWORD ?>" maxlength="200" autocomplete="new-password" dir="ltr"></label>
      <label><?= e(t('admin.confirm_password')) ?><input type="password" name="confirm_password" required minlength="<?= Asl\Auth::MIN_PASSWORD ?>" maxlength="200" autocomplete="new-password" dir="ltr"></label>
      <button class="btn" type="submit"><?= e(t('admin.save')) ?></button>
    </form>
  </section>
  <section class="admin-section">
    <h2><?= e(t('admin.2fa')) ?></h2>
    <?php if (!empty($user['totp_secret'])): ?>
      <p class="ok"><?= e(t('admin.2fa_on')) ?></p>
      <form method="post" action="<?= e(admin_url('account/2fa/disable')) ?>" class="admin-form" data-confirm="<?= e(t('admin.confirm_2fa_disable')) ?>">
        <?= csrf_field() ?>
        <label><?= e(t('admin.current_password')) ?><input type="password" name="current_password" required autocomplete="current-password" dir="ltr"></label>
        <label><?= e(t('admin.2fa_code')) ?><input type="text" name="code" required inputmode="numeric" maxlength="7" autocomplete="one-time-code" dir="ltr"></label>
        <button class="btn btn-danger" type="submit"><?= e(t('admin.2fa_disable')) ?></button>
      </form>
    <?php elseif ($pendingSecret): ?>
      <p><?= e(t('admin.2fa_setup_text')) ?></p>
      <p class="secret" dir="ltr"><code><?= e(trim(chunk_split($pendingSecret, 4, ' '))) ?></code></p>
      <p class="hint break" dir="ltr"><?= e(Totp::uri($pendingSecret, $user['email'])) ?></p>
      <form method="post" action="<?= e(admin_url('account/2fa/enable')) ?>" class="admin-form">
        <?= csrf_field() ?>
        <label><?= e(t('admin.2fa_code')) ?><input type="text" name="code" required inputmode="numeric" maxlength="7" autocomplete="one-time-code" dir="ltr"></label>
        <button class="btn" type="submit"><?= e(t('admin.2fa_enable')) ?></button>
      </form>
    <?php else: ?>
      <p><?= e(t('admin.2fa_off')) ?></p>
      <form method="post" action="<?= e(admin_url('account/2fa/start')) ?>">
        <?= csrf_field() ?>
        <button class="btn" type="submit"><?= e(t('admin.2fa_start')) ?></button>
      </form>
    <?php endif; ?>
  </section>
</div>
