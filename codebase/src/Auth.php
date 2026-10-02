<?php
declare(strict_types=1);

namespace Asl;

/**
 * Admin authentication: Argon2id/bcrypt password hashes, optional TOTP
 * second factor, brute-force throttling per e-mail and per IP, session
 * fixation protection and idle timeout.
 */
final class Auth
{
    private const WINDOW = 900;          // 15 minutes
    private const MAX_PER_EMAIL = 5;
    private const MAX_PER_IP = 20;
    public const MIN_PASSWORD = 12;

    public static function hash(string $password): string
    {
        return password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT);
    }

    public static function isLockedOut(string $email): bool
    {
        $since = time() - self::WINDOW;
        $byEmail = (int) Db::value('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > ?', [$email, $since]);
        $byIp = (int) Db::value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [Http::ip(), $since]);
        return $byEmail >= self::MAX_PER_EMAIL || $byIp >= self::MAX_PER_IP;
    }

    private static function recordFailure(string $email): void
    {
        Db::insert('login_attempts', ['ip' => Http::ip(), 'email' => $email, 'attempted_at' => time()]);
        Db::run('DELETE FROM login_attempts WHERE attempted_at < ?', [time() - 86400]);
    }

    /** @return 'ok'|'invalid'|'locked' */
    public static function attempt(string $email, string $password, string $code): string
    {
        $email = mb_strtolower(trim($email));
        if (self::isLockedOut($email)) {
            return 'locked';
        }
        $user = Db::one('SELECT * FROM admin_users WHERE email = ?', [$email]);
        // Always run a hash verification so response time doesn't reveal valid e-mails.
        $hash = $user['password_hash'] ?? '$2y$12$F5fwW.DN3GwlOvCpt5sy8eJrKfGiMWtLAWdVI8.MlLnWvSP3n/oHO';
        $passwordOk = password_verify($password, $hash) && $user !== null;

        $step = null;
        if ($passwordOk && !empty($user['totp_secret'])) {
            $step = Totp::verify($user['totp_secret'], $code, $user['totp_last_step'] !== null ? (int) $user['totp_last_step'] : null);
            $passwordOk = $step !== null;
        }
        if (!$passwordOk) {
            self::recordFailure($email);
            return 'invalid';
        }

        Db::run('DELETE FROM login_attempts WHERE email = ?', [$email]);
        $update = ['last_login_at' => Db::now()];
        if ($step !== null) {
            $update['totp_last_step'] = $step;
        }
        if (password_needs_rehash($user['password_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
            $update['password_hash'] = self::hash($password);
        }
        Db::update('admin_users', $update, 'id = :id', ['id' => $user['id']]);

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_last'] = time();
        $_SESSION['admin_ua'] = hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        return 'ok';
    }

    public static function user(): ?array
    {
        $id = $_SESSION['admin_id'] ?? null;
        if (!is_int($id)) {
            return null;
        }
        $idle = (int) Config::get('admin_idle_timeout', 1800);
        $uaOk = hash_equals((string) ($_SESSION['admin_ua'] ?? ''), hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')));
        if (!$uaOk || time() - (int) ($_SESSION['admin_last'] ?? 0) > $idle) {
            self::logout();
            return null;
        }
        $_SESSION['admin_last'] = time();
        return Db::one('SELECT id, email, totp_secret, totp_last_step, last_login_at FROM admin_users WHERE id = ?', [$id]);
    }

    public static function require(): array
    {
        $user = self::user();
        if (!$user) {
            Http::redirect(admin_url('login'));
        }
        return $user;
    }

    public static function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_last'], $_SESSION['admin_ua'], $_SESSION['totp_pending']);
        session_regenerate_id(true);
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    public static function validatePassword(string $password): bool
    {
        return mb_strlen($password) >= self::MIN_PASSWORD && mb_strlen($password) <= 200;
    }
}
