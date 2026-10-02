<?php
declare(strict_types=1);

namespace Asl;

use Asl\Controllers\AdminController;
use Asl\Controllers\ShopController;
use Asl\Controllers\WebhookController;

final class App
{
    public static function run(): void
    {
        Security::sendHeaders();
        $method = Http::method();
        $path = Http::path();

        try {
            // Stripe webhooks are authenticated by signature, not by session/CSRF.
            if ($path === '/stripe/webhook') {
                if ($method !== 'POST') {
                    Http::abort(405);
                }
                WebhookController::handle();
                return;
            }

            Security::startSession();
            if ($method === 'POST') {
                Security::verifyPost();
            }
            self::router()->dispatch($method, $path);
        } catch (\Throwable $e) {
            error_log('[asl] ' . $e::class . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            if (!headers_sent()) {
                http_response_code(500);
            }
            View::error(500, Config::isProduction() ? '' : $e->getMessage());
        }
    }

    private static function router(): Router
    {
        $r = new Router();
        $r->get('/', static fn () => Http::redirect('/' . I18n::negotiate(), 302));

        foreach (I18n::LOCALES as $l) {
            $shop = new ShopController($l);
            $r->get("/$l", [$shop, 'home']);
            $r->get("/$l/product/{slug}", [$shop, 'product']);
            $r->get("/$l/cart", [$shop, 'cart']);
            $r->post("/$l/cart/add", [$shop, 'cartAdd']);
            $r->post("/$l/cart/update", [$shop, 'cartUpdate']);
            $r->post("/$l/cart/remove", [$shop, 'cartRemove']);
            $r->post("/$l/checkout", [$shop, 'checkout']);
            $r->get("/$l/checkout/success", [$shop, 'success']);
            $r->get("/$l/checkout/cancel", [$shop, 'cancel']);
        }

        $a = '/' . Config::adminPath();
        $admin = new AdminController();
        $r->get("$a/login", [$admin, 'loginForm']);
        $r->post("$a/login", [$admin, 'login']);
        $r->post("$a/logout", [$admin, 'logout']);
        $r->post("$a/language", [$admin, 'language']);
        $r->get($a, [$admin, 'dashboard']);
        $r->get("$a/products", [$admin, 'products']);
        $r->get("$a/products/new", [$admin, 'productForm']);
        $r->post("$a/products/new", [$admin, 'productSave']);
        $r->get("$a/products/{id}", [$admin, 'productForm']);
        $r->post("$a/products/{id}", [$admin, 'productSave']);
        $r->post("$a/products/{id}/delete", [$admin, 'productDelete']);
        $r->post("$a/products/{id}/images", [$admin, 'imageUpload']);
        $r->post("$a/images/{id}/delete", [$admin, 'imageDelete']);
        $r->post("$a/images/{id}/move", [$admin, 'imageMove']);
        $r->get("$a/orders", [$admin, 'orders']);
        $r->get("$a/orders/{id}", [$admin, 'order']);
        $r->post("$a/orders/{id}", [$admin, 'orderUpdate']);
        $r->get("$a/texts", [$admin, 'texts']);
        $r->post("$a/texts", [$admin, 'textsSave']);
        $r->get("$a/settings", [$admin, 'settings']);
        $r->post("$a/settings", [$admin, 'settingsSave']);
        $r->get("$a/account", [$admin, 'account']);
        $r->post("$a/account/password", [$admin, 'passwordSave']);
        $r->post("$a/account/2fa/start", [$admin, 'twoFactorStart']);
        $r->post("$a/account/2fa/enable", [$admin, 'twoFactorEnable']);
        $r->post("$a/account/2fa/disable", [$admin, 'twoFactorDisable']);
        return $r;
    }
}
