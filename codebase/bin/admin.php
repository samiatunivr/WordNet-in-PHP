<?php
declare(strict_types=1);

/*
 * Admin accounts are managed from the command line only (never via the web).
 *
 *   php codebase/bin/admin.php create  you@example.com
 *   php codebase/bin/admin.php password you@example.com
 *   php codebase/bin/admin.php reset-2fa you@example.com
 *   php codebase/bin/admin.php delete  you@example.com
 */
if (PHP_SAPI !== 'cli') {
    exit(1);
}
require dirname(__DIR__) . '/bootstrap.php';

use Asl\Auth;
use Asl\Db;

[$cmd, $email] = [$argv[1] ?? '', mb_strtolower(trim($argv[2] ?? ''))];
if (!in_array($cmd, ['create', 'password', 'reset-2fa', 'delete'], true) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php admin.php create|password|reset-2fa|delete <email>\n");
    exit(1);
}

function ask_password(): string
{
    $tty = stream_isatty(STDIN);
    for ($i = 0; $i < 3; $i++) {
        echo 'Password (min ' . Auth::MIN_PASSWORD . ' chars): ';
        if ($tty) { shell_exec('stty -echo'); }
        $p1 = rtrim((string) fgets(STDIN), "\r\n");
        if ($tty) { shell_exec('stty echo'); echo "\nRepeat: "; shell_exec('stty -echo'); } else { echo "\n"; }
        $p2 = rtrim((string) fgets(STDIN), "\r\n");
        if ($tty) { shell_exec('stty echo'); }
        echo "\n";
        if (!Auth::validatePassword($p1)) {
            echo "Password too short.\n";
        } elseif (!hash_equals($p1, $p2)) {
            echo "Passwords do not match.\n";
        } else {
            return $p1;
        }
    }
    exit(1);
}

$user = Db::one('SELECT * FROM admin_users WHERE email = ?', [$email]);
switch ($cmd) {
    case 'create':
        if ($user) {
            fwrite(STDERR, "User already exists.\n");
            exit(1);
        }
        Db::insert('admin_users', ['email' => $email, 'password_hash' => Auth::hash(ask_password()), 'created_at' => Db::now()]);
        echo "Admin $email created.\n";
        break;
    case 'password':
        if (!$user) { fwrite(STDERR, "No such user.\n"); exit(1); }
        Db::update('admin_users', ['password_hash' => Auth::hash(ask_password())], 'id = :id', ['id' => $user['id']]);
        echo "Password updated.\n";
        break;
    case 'reset-2fa':
        if (!$user) { fwrite(STDERR, "No such user.\n"); exit(1); }
        Db::update('admin_users', ['totp_secret' => null, 'totp_last_step' => null], 'id = :id', ['id' => $user['id']]);
        echo "Two-factor authentication reset.\n";
        break;
    case 'delete':
        Db::run('DELETE FROM admin_users WHERE email = ?', [$email]);
        echo "Deleted.\n";
        break;
}
