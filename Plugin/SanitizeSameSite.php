<?php
namespace Merlin\SameSiteGuard\Plugin;

use Magento\Framework\HTTP\Header;

class SanitizeSameSite
{
    /** @var Header */
    private $header;

    public function __construct(Header $header) { $this->header = $header; }

    public function beforeSetCookieSameSite(
        \Magento\Framework\Session\Config $subject,
        string $cookieSameSite = 'Lax'
    ): array {
        $ua = $this->header->getHttpUserAgent() ?: '';

        // Normalize incoming value
        $normalized = ucfirst(strtolower(trim((string)$cookieSameSite)));
        if (!in_array($normalized, ['Lax','Strict','None'], true)) {
            $normalized = 'Lax';
        }

        // If UA is incompatible, never use None
        if (\Merlin\SameSiteGuard\Plugin\SameSiteUa::isNoneIncompatible($ua)) {
            $normalized = 'Lax';
        }

        // If using None, always enforce Secure
        if ($normalized === 'None' && method_exists($subject, 'setCookieSecure')) {
            $subject->setCookieSecure(true);
        }

        return [$normalized];
    }
}
