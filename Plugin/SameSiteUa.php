<?php
namespace Merlin\SameSiteGuard\Plugin;

class SameSiteUa
{
    /**
     * Return true if the UA is known to be incompatible with SameSite=None.
     * Covers: iOS 12 Safari/WebView, macOS 10.14 Safari 12, Chrome 51-66, old UC Browser.
     */
    public static function isNoneIncompatible(string $ua): bool
    {
        $ua = strtolower($ua);

        // iOS 12 Safari / WebView / Chrome on iOS share WebKit
        if (preg_match('#(cpu iphone os 12_|cpu os 12_)#', $ua)) {
            return true;
        }
        if (preg_match('#(ipad; cpu os 12_)#', $ua)) {
            return true;
        }
        // macOS 10.14 Safari 12
        if (strpos($ua, 'mac os x 10_14') !== false && preg_match('#version/12#', $ua) && strpos($ua, 'safari') !== false && strpos($ua, 'chrome') === false) {
            return true;
        }
        // Chrome 51â€“66
        if (preg_match('#(?:chrome|crios)/(5[1-9]|6[0-6])#', $ua) && strpos($ua, 'edge') === false) {
            return true;
        }
        // UC Browser < 12.13.2
        if (preg_match('#ucbrowser/([0-9]+)\.([0-9]+)\.([0-9]+)#', $ua, $m)) {
            $ver = ((int)$m[1])*10000 + ((int)$m[2])*100 + ((int)$m[3]);
            if ($ver < 121302) return true;
        }
        return false;
    }
}
