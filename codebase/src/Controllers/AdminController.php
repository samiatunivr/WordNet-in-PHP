<?php
declare(strict_types=1);

namespace Asl\Controllers;

use Asl\Auth;
use Asl\Db;
use Asl\Http;
use Asl\I18n;
use Asl\ImageUpload;
use Asl\Money;
use Asl\Orders;
use Asl\Products;
use Asl\Security;
use Asl\Settings;
use Asl\Totp;
use Asl\Units;
use Asl\View;

final class AdminController
{
    private const MAX_IMAGES = 12;

    private function boot(bool $requireAuth = true): ?array
    {
        $l = $_SESSION['admin_locale'] ?? 'en';
        I18n::setLocale(is_string($l) ? $l : 'en');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        return $requireAuth ? Auth::require() : null;
    }

    private function render(string $view, array $data = []): void
    {
        View::render('admin/' . $view, $data, 'admin/layout');
    }

    private function id(string $id): int
    {
        if (!ctype_digit($id) || (int) $id < 1) {
            Http::abort(404);
        }
        return (int) $id;
    }

    /* ---------------------------------------------------------------- auth */

    public function loginForm(): void
    {
        $this->boot(false);
        if (Auth::user()) {
            Http::redirect(admin_url());
        }
        View::render('admin/login', ['title' => t('admin.login')], 'admin/layout');
    }

    public function login(): void
    {
        $this->boot(false);
        $email = mb_substr(Http::post('email'), 0, 190);
        $result = Auth::attempt($email, (string) ($_POST['password'] ?? ''), Http::post('code'));
        if ($result === 'ok') {
            Http::redirect(admin_url());
        }
        if ($result === 'locked') {
            http_response_code(429);
        }
        Security::flash('error', t($result === 'locked' ? 'admin.login_locked' : 'admin.login_invalid'));
        Http::redirect(admin_url('login'));
    }

    public function logout(): void
    {
        $this->boot(false);
        Auth::logout();
        Http::redirect(admin_url('login'));
    }

    public function language(): void
    {
        $l = Http::post('locale');
        if (I18n::isLocale($l)) {
            $_SESSION['admin_locale'] = $l;
        }
        $back = Http::post('back');
        Http::redirect(str_starts_with($back, admin_url()) ? $back : admin_url());
    }

    /* ----------------------------------------------------------- dashboard */

    public function dashboard(): void
    {
        $this->boot();
        $this->render('dashboard', [
            'title' => t('admin.dashboard'),
            'stats' => [
                'products' => (int) Db::value('SELECT COUNT(*) FROM products'),
                'paid' => (int) Db::value("SELECT COUNT(*) FROM orders WHERE status IN ('paid','processing')"),
                'revenue' => (int) Db::value("SELECT COALESCE(SUM(total_cents),0) FROM orders WHERE status IN ('paid','processing','shipped','delivered')"),
                'review' => (int) Db::value("SELECT COUNT(*) FROM orders WHERE status = 'review'"),
            ],
            'recent' => Db::all("SELECT * FROM orders WHERE status <> 'pending' ORDER BY id DESC LIMIT 10"),
        ]);
    }

    /* ------------------------------------------------------------ products */

    public function products(): void
    {
        $this->boot();
        $rows = Products::withCoverImages(Db::all('SELECT * FROM products ORDER BY sort_order ASC, id DESC'));
        $this->render('products', ['title' => t('admin.products'), 'products' => $rows]);
    }

    public function productForm(?string $id = null): void
    {
        $this->boot();
        $product = null;
        if ($id !== null) {
            $product = Products::find($this->id($id));
            if (!$product) {
                Http::abort(404);
            }
        }
        $old = $_SESSION['_old'] ?? null;
        unset($_SESSION['_old']);
        $this->render('product_form', [
            'title' => $product ? t('admin.edit_product') : t('admin.new_product'),
            'product' => $product,
            'old' => is_array($old) ? $old : null,
            'images' => $product ? Products::images((int) $product['id']) : [],
        ]);
    }

    public function productSave(?string $id = null): void
    {
        $this->boot();
        $existing = null;
        if ($id !== null) {
            $existing = Products::find($this->id($id));
            if (!$existing) {
                Http::abort(404);
            }
        }

        $errors = [];
        $data = [];
        $slug = strtolower(Http::post('slug'));
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug) || strlen($slug) > 120) {
            $errors[] = t('admin.err_slug');
        } elseif (Db::value('SELECT COUNT(*) FROM products WHERE slug = ? AND id <> ?', [$slug, $existing['id'] ?? 0])) {
            $errors[] = t('admin.err_slug_taken');
        }
        $data['slug'] = $slug;

        foreach (I18n::LOCALES as $l) {
            $name = Http::post('name_' . $l);
            $summary = Http::post('summary_' . $l);
            $desc = str_replace("\r\n", "\n", Http::post('description_' . $l));
            if ($name === '' || mb_strlen($name) > 200) {
                $errors[] = t('admin.err_name', ['lang' => I18n::NAMES[$l]]);
            }
            if (mb_strlen($summary) > 500 || mb_strlen($desc) > 20000) {
                $errors[] = t('admin.err_too_long', ['lang' => I18n::NAMES[$l]]);
            }
            $data['name_' . $l] = $name;
            $data['summary_' . $l] = $summary;
            $data['description_' . $l] = $desc;
        }

        $kg = Money::parse(Http::post('price_per_kg'));
        $lt = Money::parse(Http::post('price_per_l'));
        if ($kg === -1 || $lt === -1 || $kg === 0 || $lt === 0) {
            $errors[] = t('admin.err_price');
        }
        $data['price_per_kg_cents'] = ($kg !== null && $kg > 0) ? $kg : null;
        $data['price_per_l_cents'] = ($lt !== null && $lt > 0) ? $lt : null;

        $units = array_values(array_intersect(array_keys(Units::UNITS), (array) ($_POST['units'] ?? [])));
        foreach ($units as $u) {
            $needs = Units::dimension($u) === 'mass' ? 'price_per_kg_cents' : 'price_per_l_cents';
            if ($data[$needs] === null) {
                $errors[] = t('admin.err_unit_price', ['unit' => Units::label($u)]);
            }
        }
        if (!$units) {
            $errors[] = t('admin.err_units');
        }
        $data['units'] = implode(',', $units);
        $data['is_active'] = Http::post('is_active') === '1' ? 1 : 0;
        $data['is_featured'] = Http::post('is_featured') === '1' ? 1 : 0;
        $data['sort_order'] = max(-9999, min(9999, (int) Http::post('sort_order', '0')));

        if ($errors) {
            foreach (array_unique($errors) as $err) {
                Security::flash('error', $err);
            }
            $_SESSION['_old'] = $_POST;
            unset($_SESSION['_old']['_csrf']);
            Http::redirect($existing ? admin_url('products/' . $existing['id']) : admin_url('products/new'));
        }

        $data['updated_at'] = Db::now();
        if ($existing) {
            Db::update('products', $data, 'id = :id', ['id' => $existing['id']]);
            $pid = (int) $existing['id'];
        } else {
            $data['created_at'] = Db::now();
            $pid = Db::insert('products', $data);
        }
        Security::flash('success', t('admin.saved'));
        Http::redirect(admin_url('products/' . $pid));
    }

    public function productDelete(string $id): void
    {
        $this->boot();
        $pid = $this->id($id);
        foreach (Products::images($pid) as $img) {
            ImageUpload::delete($img['filename']);
        }
        Db::run('DELETE FROM product_images WHERE product_id = ?', [$pid]);
        Db::run('DELETE FROM products WHERE id = ?', [$pid]);
        Security::flash('success', t('admin.deleted'));
        Http::redirect(admin_url('products'));
    }

    public function imageUpload(string $id): void
    {
        $this->boot();
        $pid = $this->id($id);
        if (!Products::find($pid)) {
            Http::abort(404);
        }
        $count = (int) Db::value('SELECT COUNT(*) FROM product_images WHERE product_id = ?', [$pid]);
        $order = (int) Db::value('SELECT COALESCE(MAX(sort_order), 0) FROM product_images WHERE product_id = ?', [$pid]);
        $stored = 0;
        foreach (ImageUpload::files('images') as $file) {
            if (($file['error'] ?? 0) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($count >= self::MAX_IMAGES) {
                Security::flash('error', t('admin.err_max_images', ['max' => self::MAX_IMAGES]));
                break;
            }
            try {
                $name = ImageUpload::store($file);
                Db::insert('product_images', ['product_id' => $pid, 'filename' => $name, 'sort_order' => ++$order, 'created_at' => Db::now()]);
                $count++;
                $stored++;
            } catch (\RuntimeException $e) {
                Security::flash('error', t('admin.' . $e->getMessage()));
            }
        }
        if ($stored > 0) {
            Security::flash('success', t('admin.images_uploaded', ['n' => $stored]));
        }
        Http::redirect(admin_url('products/' . $pid) . '#images');
    }

    public function imageDelete(string $id): void
    {
        $this->boot();
        $img = Db::one('SELECT * FROM product_images WHERE id = ?', [$this->id($id)]);
        if (!$img) {
            Http::abort(404);
        }
        Db::run('DELETE FROM product_images WHERE id = ?', [$img['id']]);
        ImageUpload::delete($img['filename']);
        Security::flash('success', t('admin.deleted'));
        Http::redirect(admin_url('products/' . $img['product_id']) . '#images');
    }

    public function imageMove(string $id): void
    {
        $this->boot();
        $img = Db::one('SELECT * FROM product_images WHERE id = ?', [$this->id($id)]);
        if (!$img) {
            Http::abort(404);
        }
        $images = Products::images((int) $img['product_id']);
        $ids = array_map(static fn ($i) => (int) $i['id'], $images);
        $pos = array_search((int) $img['id'], $ids, true);
        $target = Http::post('direction') === 'up' ? $pos - 1 : $pos + 1;
        if ($pos !== false && isset($ids[$target])) {
            [$ids[$pos], $ids[$target]] = [$ids[$target], $ids[$pos]];
            Db::transaction(static function () use ($ids) {
                foreach ($ids as $i => $imgId) {
                    Db::run('UPDATE product_images SET sort_order = ? WHERE id = ?', [$i, $imgId]);
                }
            });
        }
        Http::redirect(admin_url('products/' . $img['product_id']) . '#images');
    }

    /* -------------------------------------------------------------- orders */

    public function orders(): void
    {
        $this->boot();
        $status = Http::query('status');
        $page = max(1, (int) Http::query('page', '1'));
        $perPage = 50;
        $where = in_array($status, Orders::STATUSES, true) ? 'WHERE status = ?' : "WHERE status <> 'pending'";
        $params = in_array($status, Orders::STATUSES, true) ? [$status] : [];
        $total = (int) Db::value("SELECT COUNT(*) FROM orders $where", $params);
        $rows = Db::all("SELECT * FROM orders $where ORDER BY id DESC LIMIT $perPage OFFSET " . (($page - 1) * $perPage), $params);
        $this->render('orders', [
            'title' => t('admin.orders'),
            'orders' => $rows,
            'status' => $status,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ]);
    }

    public function order(string $id): void
    {
        $this->boot();
        $order = Db::one('SELECT * FROM orders WHERE id = ?', [$this->id($id)]);
        if (!$order) {
            Http::abort(404);
        }
        $address = json_decode((string) $order['shipping_address'], true);
        $this->render('order', [
            'title' => t('admin.order') . ' #' . $order['id'],
            'order' => $order,
            'items' => Orders::items((int) $order['id']),
            'address' => is_array($address) ? $address : [],
        ]);
    }

    public function orderUpdate(string $id): void
    {
        $this->boot();
        $oid = $this->id($id);
        $status = Http::post('status');
        if (!in_array($status, Orders::STATUSES, true)) {
            Http::abort(400);
        }
        Db::update('orders', [
            'status' => $status,
            'admin_note' => mb_substr(Http::post('admin_note'), 0, 5000),
            'updated_at' => Db::now(),
        ], 'id = :id', ['id' => $oid]);
        Security::flash('success', t('admin.saved'));
        Http::redirect(admin_url('orders/' . $oid));
    }

    /* --------------------------------------------------------------- texts */

    /** Keys that are editable from the "Texts" screen (shop-facing strings). */
    private function editableKeys(): array
    {
        return array_values(array_filter(array_keys(I18n::defaults('en')), static fn ($k) => !str_starts_with($k, 'admin.')));
    }

    public function texts(): void
    {
        $this->boot();
        $this->render('texts', [
            'title' => t('admin.texts'),
            'keys' => $this->editableKeys(),
            'overrides' => I18n::overrides(),
        ]);
    }

    public function textsSave(): void
    {
        $this->boot();
        $input = $_POST['t'] ?? [];
        if (!is_array($input)) {
            Http::abort(400);
        }
        $overrides = I18n::overrides();
        Db::transaction(function () use ($input, $overrides) {
            foreach ($this->editableKeys() as $key) {
                $row = $input[$key] ?? null;
                if (!is_array($row)) {
                    continue;
                }
                $vals = [];
                $isDefault = true;
                foreach (I18n::LOCALES as $l) {
                    $v = is_string($row[$l] ?? null) ? mb_substr(trim(str_replace("\r\n", "\n", $row[$l])), 0, 5000) : '';
                    $vals[$l] = $v;
                    if ($v !== '' && $v !== (I18n::defaults($l)[$key] ?? '')) {
                        $isDefault = false;
                    }
                }
                if ($isDefault) {
                    Db::run('DELETE FROM translations WHERE tkey = ?', [$key]);
                } elseif (isset($overrides[$key])) {
                    Db::update('translations', $vals + ['updated_at' => Db::now()], 'tkey = :k', ['k' => $key]);
                } else {
                    Db::insert('translations', ['tkey' => $key] + $vals + ['updated_at' => Db::now()]);
                }
            }
        });
        I18n::clearCache();
        Security::flash('success', t('admin.saved'));
        Http::redirect(admin_url('texts'));
    }

    /* ------------------------------------------------------------ settings */

    public function settings(): void
    {
        $this->boot();
        $this->render('settings', ['title' => t('admin.settings')]);
    }

    public function settingsSave(): void
    {
        $this->boot();
        $flat = Money::parse(Http::post('shipping_flat'));
        $free = Money::parse(Http::post('free_shipping_from'));
        $countries = strtoupper(preg_replace('/\s+/', '', Http::post('shipping_countries')) ?? '');
        $email = Http::post('contact_email');
        $errors = [];
        if ($flat === -1 || $free === -1) {
            $errors[] = t('admin.err_price');
        }
        if (!preg_match('/^[A-Z]{2}(,[A-Z]{2})*$/', $countries)) {
            $errors[] = t('admin.err_countries');
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = t('admin.err_email');
        }
        if ($errors) {
            foreach ($errors as $err) {
                Security::flash('error', $err);
            }
            Http::redirect(admin_url('settings'));
        }
        Settings::set('shipping_flat_cents', (string) ($flat ?? 0));
        Settings::set('free_shipping_from_cents', (string) ($free ?? 0));
        Settings::set('shipping_countries', $countries);
        Settings::set('contact_email', $email);
        Settings::set('contact_phone', mb_substr(preg_replace('/[^0-9+ ()-]/', '', Http::post('contact_phone')) ?? '', 0, 40));
        Security::flash('success', t('admin.saved'));
        Http::redirect(admin_url('settings'));
    }

    /* ------------------------------------------------------------- account */

    public function account(): void
    {
        $user = $this->boot();
        $pending = $_SESSION['totp_pending'] ?? null;
        $this->render('account', [
            'title' => t('admin.account'),
            'user' => $user,
            'pendingSecret' => is_string($pending) ? $pending : null,
        ]);
    }

    private function checkPassword(array $user): bool
    {
        $hash = (string) Db::value('SELECT password_hash FROM admin_users WHERE id = ?', [$user['id']]);
        return password_verify((string) ($_POST['current_password'] ?? ''), $hash);
    }

    public function passwordSave(): void
    {
        $user = $this->boot();
        $new = (string) ($_POST['new_password'] ?? '');
        if (!$this->checkPassword($user)) {
            Security::flash('error', t('admin.err_current_password'));
        } elseif (!Auth::validatePassword($new)) {
            Security::flash('error', t('admin.err_password_length', ['n' => Auth::MIN_PASSWORD]));
        } elseif (!hash_equals($new, (string) ($_POST['confirm_password'] ?? ''))) {
            Security::flash('error', t('admin.err_password_match'));
        } else {
            Db::update('admin_users', ['password_hash' => Auth::hash($new)], 'id = :id', ['id' => $user['id']]);
            session_regenerate_id(true);
            Security::flash('success', t('admin.password_changed'));
        }
        Http::redirect(admin_url('account'));
    }

    public function twoFactorStart(): void
    {
        $this->boot();
        $_SESSION['totp_pending'] = Totp::generateSecret();
        Http::redirect(admin_url('account'));
    }

    public function twoFactorEnable(): void
    {
        $user = $this->boot();
        $secret = $_SESSION['totp_pending'] ?? null;
        $step = is_string($secret) ? Totp::verify($secret, Http::post('code')) : null;
        if ($step === null) {
            Security::flash('error', t('admin.err_code'));
        } else {
            Db::update('admin_users', ['totp_secret' => $secret, 'totp_last_step' => $step], 'id = :id', ['id' => $user['id']]);
            unset($_SESSION['totp_pending']);
            Security::flash('success', t('admin.2fa_enabled'));
        }
        Http::redirect(admin_url('account'));
    }

    public function twoFactorDisable(): void
    {
        $user = $this->boot();
        $step = $user['totp_secret'] ? Totp::verify($user['totp_secret'], Http::post('code'), $user['totp_last_step'] !== null ? (int) $user['totp_last_step'] : null) : null;
        if (!$this->checkPassword($user) || $step === null) {
            Security::flash('error', t('admin.err_code'));
        } else {
            Db::update('admin_users', ['totp_secret' => null, 'totp_last_step' => null], 'id = :id', ['id' => $user['id']]);
            Security::flash('success', t('admin.2fa_disabled'));
        }
        Http::redirect(admin_url('account'));
    }
}
