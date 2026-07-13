<?php
require_once __DIR__ . '/BaseHttpScraper.php';

/**
 * Best-effort price lookup scoped to a specific store: Kyle H-E-B plus!, store #14
 * (https://www.heb.com/heb-store/tx/kyle/kyle-h-e-b-plus--14).
 *
 * heb.com is a Next.js storefront that server-renders product data directly into the
 * HTML (confirmed from a real page capture), so a plain HTTP GET does see prices — good.
 * It has no JSON-LD, though, so this overrides the generic base-class extractor with one
 * tailored to HEB's actual markup: product cards carry data-component="product-card",
 * each with a data-qe-id="productTitle" element (title attribute = clean product name)
 * and a data-component="product-price-sale-price" element (current price).
 *
 * Store-specific pricing is normally selected via a cookie/API call triggered by clicking
 * "Shop this store" in a browser, not just by loading the store info page. This class
 * visits the store page first to pick up whatever cookies it sets, then reuses them (via
 * a shared cookie jar) for the search request, on the chance that's enough to scope
 * results to Kyle — that part is still unverified. The product-card structure below is
 * confirmed from a real captured page, but that capture was the "similar items" carousel
 * on a single product's detail page (search redirects there for a close/exact match)
 * rather than the multi-result /search grid itself; if search results ever render with
 * different markup, this will need a follow-up adjustment.
 */
class HebScraper extends BaseHttpScraper {
    private const STORE_PAGE_URL = 'https://www.heb.com/heb-store/tx/kyle/kyle-h-e-b-plus--14';

    protected string $searchUrlTemplate = 'https://www.heb.com/search?q=%s';

    public function lookupPrice(string $productName): ?array {
        $this->lastError = null;

        if (trim($productName) === '') {
            $this->lastError = 'No search term given.';
            return null;
        }

        $cookieJar = tempnam(sys_get_temp_dir(), 'heb_cookie_');
        if ($cookieJar === false) {
            $this->lastError = 'Could not create a temp file for the cookie jar.';
            return null;
        }

        try {
            // Establish store context first (best effort — a failure here isn't fatal,
            // the search request below still runs without it), then search using the
            // same cookie jar.
            $this->fetchWithCookies(self::STORE_PAGE_URL, $cookieJar);
            $html = $this->fetchWithCookies(
                sprintf($this->searchUrlTemplate, rawurlencode($productName)),
                $cookieJar
            );
        } finally {
            @unlink($cookieJar);
        }

        if ($html === null) {
            return null; // lastError already set by fetchWithCookies()
        }

        $result = $this->extractBestMatch($html, $productName);
        if ($result === null && $this->lastError === null) {
            $this->lastError = "Fetched HEB's page okay, but couldn't find a confident product match in the results.";
        }
        return $result;
    }

    protected function extractBestMatch(string $html, string $productName): ?array {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $needle = strtolower($productName);
        $best   = null;

        // Search-result / recommendation cards
        foreach ($xpath->query('//*[@data-component="product-card"]') as $card) {
            $titleNodes = $xpath->query('.//*[@data-qe-id="productTitle"]', $card);
            $priceNodes = $xpath->query('.//*[@data-component="product-price-sale-price"]', $card);
            $candidate  = $this->buildCandidate($titleNodes, $priceNodes, $needle);
            if ($candidate !== null && ($best === null || $candidate['match_percent'] > $best['match_percent'])) {
                $best = $candidate;
            }
        }

        // Fallback: a single product detail page (search redirected to an exact match)
        if ($best === null) {
            $titleNodes = $xpath->query('//h1');
            $priceNodes = $xpath->query('//*[@data-component="product-price-sale-price"]');
            $candidate  = $this->buildCandidate($titleNodes, $priceNodes, $needle);
            if ($candidate !== null) {
                $best = $candidate;
            }
        }

        if ($best === null) {
            $this->lastError = "Fetched HEB's page okay, but didn't recognize any product cards in it "
                . '(the page markup may have changed, or HEB served a bot-check/error page instead of results).';
            return null;
        }

        if ($best['match_percent'] < $this->minMatchPercent) {
            $this->lastError = "Closest match was \"{$best['matched_name']}\", which wasn't a confident enough match "
                . "for \"$productName\" (try a more specific or simpler search term).";
            return null;
        }

        unset($best['match_percent']);
        return $best;
    }

    private function buildCandidate(DOMNodeList $titleNodes, DOMNodeList $priceNodes, string $needle): ?array {
        if ($titleNodes->length === 0 || $priceNodes->length === 0) {
            return null;
        }

        $titleNode = $titleNodes->item(0);
        $name = $titleNode->hasAttribute('title')
            ? trim($titleNode->getAttribute('title'))
            : trim(preg_replace('/\s+/', ' ', $titleNode->textContent));

        $priceText = trim($priceNodes->item(0)->textContent);
        if ($name === '' || !preg_match('/\$([\d,]+\.\d{2})/', $priceText, $m)) {
            return null;
        }

        $price = (float) str_replace(',', '', $m[1]);
        similar_text($needle, strtolower($name), $percent);

        return [
            'price'         => $price,
            'matched_name'  => $name,
            'raw_snippet'   => mb_substr($name . ' - $' . number_format($price, 2), 0, 200),
            'match_percent' => $percent,
        ];
    }

    private function fetchWithCookies(string $url, string $cookieJar): ?string {
        if (!function_exists('curl_init')) {
            $this->lastError = 'The PHP cURL extension is not available on this server.';
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

        $body     = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            $this->lastError = "Network error reaching HEB: $error";
            return null;
        }
        if ($httpCode >= 400) {
            $this->lastError = "HEB's site returned HTTP $httpCode for $url "
                . '(it may be blocking automated requests from this server).';
            return null;
        }
        if (!is_string($body) || $body === '') {
            $this->lastError = "HEB's site returned an empty response for $url.";
            return null;
        }

        return $body;
    }
}
