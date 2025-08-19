# M2-SameSiteGuard
Merlin_SameSiteGuard — README

Harden Magento’s cookie SameSite handling across all areas (frontend, admin, web APIs).
This module normalizes invalid values passed to Magento’s session config and guarantees that SameSite=None is always paired with Secure. It also protects legacy/broken browsers that can’t handle SameSite=None.

Features

Sanitizes any value sent to Magento\Framework\Session\Config::setCookieSameSite()
→ coerces to one of Lax | Strict | None (anything else becomes Lax).

Enforces Secure when SameSite=None for cookies set via Magento’s PhpCookieManager.

UA-aware guard: known-incompatible browsers (e.g., iOS 12 Safari/WebView, macOS 10.14 Safari 12, Chrome 51–66, UC < 12.13.2) are prevented from using None and fall back to Lax.

Works in all areas (frontend, adminhtml, webapi), with no Admin UI changes.

Designed to coexist with other modules; runs late (sortOrder=999) to normalize bad inputs.

Requirements

Magento: 2.3.4+ (tested on 2.4.x, including 2.4.5-p6)

PHP: 7.4+ (tested on PHP 8.1)

HTTPS: strongly recommended. Browsers require Secure for SameSite=None.

Installation

Copy the module

If you received a zip:

unzip Merlin_SameSiteGuard-*.zip -d app/code/


You should now have:

app/code/Merlin/SameSiteGuard/


Enable & register

php bin/magento module:enable Merlin_SameSiteGuard
php bin/magento setup:upgrade
php bin/magento cache:flush


No configuration is required. The guard activates automatically.

How it works

A before plugin on Magento\Framework\Session\Config::setCookieSameSite():

Normalizes the value to Lax|Strict|None; otherwise → Lax.

Detects legacy/broken UAs and blocks None (forces Lax).

If None survives, it forces cookie_secure = true on the same config instance.

A before plugin on Magento\Framework\Stdlib\Cookie\PhpCookieManager::setPublicCookie():

If cookie metadata is SameSite=None, it sets Secure=true to satisfy modern browser rules.

Important: This module governs cookies set via Magento’s cookie managers. If third-party code sends raw header("Set-Cookie: ..."), Magento (and this module) cannot modify those headers.

Browser compatibility behavior
Browser / Engine	Result
Modern Chromium/Firefox/Safari (incl. Android 10 Chrome)	SameSite=None; Secure allowed (if configured or required)
iOS 12 Safari/WebView/Chrome (WebKit engine)	None blocked → coerced to Lax
macOS 10.14 Safari 12	None blocked → Lax
Chrome 51–66	None blocked → Lax
UC Browser < 12.13.2	None blocked → Lax
Verifying it works

From your web node (replace the domain):

# Modern Android 10 Chrome (should allow None; Secure)
UA="Mozilla/5.0 (Linux; Android 10; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36"
curl -sI -A "$UA" https://your-domain/ | grep -i set-cookie

# iOS 12 Safari (should block None → Lax)
UA_IOS12="Mozilla/5.0 (iPhone; CPU iPhone OS 12_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1 Mobile/15E148 Safari/604.1"
curl -sI -A "$UA_IOS12" https://your-domain/ | grep -i set-cookie


Look for:

Modern UA → cookies that may require cross-site behavior include SameSite=None; Secure.

iOS 12/old Chrome → SameSite=None not present (expect Lax or your configured value).

Best practices & notes

In Stores → Configuration → General → Web → Default Cookie Settings:

Set Cookie Same Site to None if you rely on cross-site cookies (SSO, embedded UIs, etc.).

Ensure Cookie Secure = Yes.

Ensure your reverse proxy sends X-Forwarded-Proto: https and Magento’s Offloader header is set to X-Forwarded-Proto (so Magento recognizes HTTPS and emits secure cookies).

If you run a module that manipulates cookies directly, keep this guard enabled to normalize outputs.

Troubleshooting

Still see “Invalid Samesite attribute” in logs?

Some module may call setCookieSameSite() with an invalid value very early. This guard intercepts that call; make sure the module is enabled and caches are flushed:

php bin/magento module:status | grep Merlin_SameSiteGuard
php bin/magento cache:flush


Check effective config:

php bin/magento config:show web/cookie/samesite
php bin/magento config:show web/cookie/cookie_secure
php bin/magento config:show web/cookie/cookie_httponly


Search for offenders:

grep -R "cookie_samesite\|setCookieSameSite" app/code vendor -n


(The guard will still sanitize, but patching the source is ideal.)

Redirect loops (ERR_TOO_MANY_REDIRECTS)?

Ensure there’s one HTTP→HTTPS redirect (usually at your edge LB).

Pass X-Forwarded-Proto: https through your proxies; set Magento Offloader header = X-Forwarded-Proto.

Cookies set via JavaScript (document.cookie)

This module can’t rewrite client-side cookies. Ensure your JS sets Secure + correct SameSite when needed (JS can set SameSite in modern browsers).

Uninstall
php bin/magento module:disable Merlin_SameSiteGuard
rm -rf app/code/Merlin/SameSiteGuard
php bin/magento setup:upgrade
php bin/magento cache:flush

Changelog

1.1.0

Added UA-aware block list to prevent SameSite=None on legacy/broken engines.

Continued enforcement of Secure when None.

1.0.0

Initial release: normalize values and enforce Secure with None.

License

MIT (or project default). Copyright © Merlin.
