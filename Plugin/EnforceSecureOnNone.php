<?php
namespace Merlin\SameSiteGuard\Plugin;

use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

class EnforceSecureOnNone
{
    public function beforeSetPublicCookie(
        PhpCookieManager $subject,
        $name,
        $value,
        PublicCookieMetadata $metadata = null
    ) {
        if ($metadata) {
            $sameSite = method_exists($metadata,'getSameSite') ? $metadata->getSameSite() : null;
            $sameSite = $sameSite ? ucfirst(strtolower((string)$sameSite)) : null;
            if ($sameSite === 'None') {
                $metadata->setSecure(true);
            }
        }
        return [$name, $value, $metadata];
    }
}
