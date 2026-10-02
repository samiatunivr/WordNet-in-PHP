# Asl (عسل): Yemeni honey web shop

A trilingual shop (Arabic, English, Dutch) with Stripe Checkout, per-unit quantities
(mg, g, kg, ml, litre) and an admin panel. It's plain PHP 8.1+ with no Composer
dependencies.

```
codebase/   application code, config, language files, views, database, CLI tools (NOT web-served)
public/     web root: index.php, assets, uploaded product images
```

## Requirements
PHP ≥ 8.1 with `pdo_sqlite` (or `pdo_mysql`), `curl`, `gd` (with WebP), `fileinfo`, `mbstring`, and `intl` (recommended, for currency formatting).

## Install
```bash
cp codebase/config/config.example.php codebase/config/config.php   # then edit it
php codebase/bin/install.php            # add --demo for three sample products
php codebase/bin/admin.php create you@example.com
```
Point the web server's document root at **`public/`** (never at the repo root).
The web server user needs write access to `codebase/storage/` and `public/uploads/products/`.

Local development:
```bash
# set 'app_env' => 'development' and 'app_url' => 'http://localhost:8000' in config.php
php -S localhost:8000 -t public codebase/bin/dev-router.php
```

### Nginx
```nginx
root /var/www/asl/public;
index index.php;
client_max_body_size 30m;
location / { try_files $uri /index.php$is_args$args; }
location ~ ^/uploads/ { location ~ \.php$ { return 403; } add_header X-Content-Type-Options nosniff; }
location = /index.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/index.php; fastcgi_pass unix:/run/php/php-fpm.sock; }
location ~ \.php$ { return 404; }
location ~ /\. { deny all; }
```
Apache works out of the box through `public/.htaccess` (needs `mod_rewrite` and `AllowOverride All`).

## Stripe
1. Put your secret key (`sk_live_…` / `sk_test_…`) in `stripe_secret_key`.
2. In the Stripe Dashboard → Developers → Webhooks, add the endpoint `{app_url}/stripe/webhook` with these events:
   `checkout.session.completed`, `checkout.session.async_payment_succeeded`,
   `checkout.session.async_payment_failed`, `checkout.session.expired`.
   Put its signing secret in `stripe_webhook_secret`.
3. Enable the payment methods you want (cards, iDEAL, Bancontact, Apple Pay…) in the Dashboard. Checkout shows them automatically.

Local testing: `stripe listen --forward-to localhost:8000/stripe/webhook`.

## Languages
- The shop lives under `/ar`, `/en` and `/nl`. Arabic is the default and is laid out right-to-left.
- Nothing is machine-translated. Product names, summaries and descriptions are written by the admin for each language, side by side.
- Every storefront text (hero, about, buttons, messages, units…) has a hand-written default in `codebase/lang/*.php`, and the admin can change any of them for each language under **Admin › Texts**.
- The admin panel itself can be switched between the three languages.

## Installable app (PWA)
Customers can install the shop on their phone's home screen, where it opens full screen like a native app:
- On Android and Chrome/Edge, an **Install app** button appears in the header (using the browser's own install prompt).
- On iPhone and iPad, the button shows the Safari steps (Share → Add to Home Screen).
- The app manifest (`/{lang}/manifest.webmanifest`) is localised, so the installed app's name and text direction match the language the customer installed it from.
- `public/sw.js` caches only static files and product images. Pages are always fetched fresh, so prices, cart and security tokens are never stale. Without a connection the customer sees a translated offline page.
- Installation requires HTTPS (or `localhost` for testing). After changing CSS, JS or icons, raise `VERSION` in `public/sw.js` so installed apps pick up the new files.
- The icons in `public/assets/img/` (`icon-192.png`, `icon-512.png`, `icon-maskable-512.png`, `apple-touch-icon.png`) are made from `logo.svg`. Replace them with your own artwork if you like.

## App look on phones
On screens up to 768px wide, and in the installed app, the storefront switches to a native-app layout:
- **App bar:** logo and compact language switch (ع / EN / NL). Inner pages get a back button and the page title.
- **Bottom tab bar:** Home, Shop, Cart (with item badge) and More. More is a settings-style screen with language, install app, our story, cookie settings and contact.
- **Product page:** full-width swipeable photo carousel with dots, and a fixed bar with the live price and **Add to cart**.
- **Cart:** fixed checkout bar with the total.
- **Messages:** shown as toasts. Success messages fade out; errors stay until tapped.
- **Lists:** 2-column product grid and swipeable feature cards.
- **Polish:** smooth page transitions (View Transitions, switched off for reduced-motion users), safe-area support for notched phones, and no text selection on app chrome when installed.
- **Admin:** on phones the admin gets a sticky header with a swipeable section bar.

Desktop keeps the regular website layout.

## Cookie consent
A consent banner appears on the first visit, with **Accept all**, **Only essential** and **Customise** choices. It works without JavaScript.
- `/{lang}/cookies` holds the cookie policy and a preferences form. The footer link "Cookie settings" lets visitors change their choice at any time.
- The choice is stored for 6 months in the `asl_consent` cookie. Raise `Consent::VERSION` when the policy changes so everyone is asked again.
- The shop itself only sets **essential** cookies (the session for cart, checkout and security, plus the consent cookie), which are allowed without consent under the GDPR/ePrivacy rules. The *Statistics* and *Marketing* categories are opt-in. If you ever add analytics or marketing scripts, load them only when `Asl\Consent::allows('analytics')` or `allows('marketing')` returns true.
- All banner and policy texts can be edited in the three languages under Admin › Texts.

## Quantities and pricing
Each product has a price per kg and/or per litre, and the admin chooses which units customers can buy in.
Quantities are kept as whole milligrams or millilitres, so there is no floating-point rounding. The line price is
`quantity × price` rounded to the cent and always worked out on the server. Arabic-Indic digits (١٫٥) are accepted. Each line is capped at 100 kg or 100 L.

## Security measures
| Threat | Mitigation |
|---|---|
| Code/config exposure | Only `public/` is web-served. Config, DB, logs and source sit outside it. `.htaccess` lets only `index.php` run |
| SQL injection | PDO prepared statements everywhere, emulated prepares off |
| XSS | All output escaped with `e()`. Strict CSP (`script-src 'self'`, no inline scripts or styles). Product text is plain text, not HTML |
| CSRF | Per-session token on every POST plus an `Origin` check. SameSite cookies |
| Clickjacking | `frame-ancestors 'none'`, `X-Frame-Options: DENY` |
| Session hijack / fixation | HttpOnly, Secure, `__Host-` cookie on HTTPS, strict mode, ID regenerated on login/logout, user-agent binding, 30-min admin idle timeout |
| Brute force | 5 failures per e-mail or 20 per IP in 15 min locks login. Constant-time checks that don't reveal which accounts exist |
| Weak auth | Argon2id hashes, minimum 12 chars, optional TOTP 2FA with replay protection. Admins are created only from the CLI |
| Price tampering | The cart stores only product ID, unit and quantity. Prices are re-read from the DB at checkout. The webhook checks the paid amount and currency against the order |
| Payment spoofing | Orders are marked paid only from Stripe data (signed webhook with HMAC-SHA256 and a 5-min tolerance, or a server-side API fetch). Events are deduplicated |
| Card data | Never touches the server. Stripe-hosted Checkout |
| Malicious uploads | Size limit, magic-byte MIME check, pixel-bomb limit, full GD re-encode (strips payloads and EXIF), random filenames, no execution in `uploads/` |
| IDOR on orders | Only the browser session that placed an order can see its confirmation |
| Cookies | Consent cookie is HttpOnly and SameSite=Lax. No optional cookies are set before opt-in. The service worker never caches HTML or non-GET requests |
| Transport | HSTS and `upgrade-insecure-requests` when `app_url` is HTTPS |

Operational must-dos: serve over HTTPS only, keep PHP updated, change `admin_path` to something
non-obvious, enable 2FA for every admin, and back up `codebase/storage/database/`.
