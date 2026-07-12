<?php
require_once __DIR__ . '/BaseHttpScraper.php';

/**
 * Best-effort price lookup scoped to a specific store: Kyle H-E-B plus!, store #14
 * (https://www.heb.com/heb-store/tx/kyle/kyle-h-e-b-plus--14).
 *
 * HEB's storefront is a client-rendered app; store-specific pricing is normally selected
 * via a cookie/API call triggered by clicking "Shop this store" in a browser, not just by
 * loading the store info page. This class visits the store page first to pick up whatever
 * cookies it sets, then reuses them (via a shared cookie jar) for the search request, on
 * the chance that's enough to scope results to Kyle. This is unverified — this sandbox has
 * no outbound access to heb.com, so confirm/adjust once it runs somewhere with real
 * internet access. If it doesn't pick up store-specific pricing, manual entry remains the
 * reliable path.
 */
class HebScraper extends BaseHttpScraper {
    private const STORE_PAGE_URL = 'https://www.heb.com/heb-store/tx/kyle/kyle-h-e-b-plus--14';

    protected string $searchUrlTemplate = 'https://www.heb.com/search?q=%s';

    public function lookupPrice(string $productName): ?array {
        if (trim($productName) === '') {
            return null;
        }

        $cookieJar = tempnam(sys_get_temp_dir(), 'heb_cookie_');
        if ($cookieJar === false) {
            return null;
        }

        try {
            // Establish store context first, then search within the same cookie jar.
            $this->fetchWithCookies(self::STORE_PAGE_URL, $cookieJar);
            $html = $this->fetchWithCookies(
                sprintf($this->searchUrlTemplate, rawurlencode($productName)),
                $cookieJar
            );
        } finally {
            @unlink($cookieJar);
        }

        return $html === null ? null : $this->extractBestMatch($html, $productName);
    }

    private function fetchWithCookies(string $url, string $cookieJar): ?string {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_COOKIEJAR      => $cookieJar,
            CURLOPT_COOKIEFILE     => $cookieJar,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                . '(KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        ]);

        $body  = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        return ($errno === 0 && is_string($body) && $body !== '') ? $body : null;
    }
}
