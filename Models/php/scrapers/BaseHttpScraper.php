<?php
require_once __DIR__ . '/PriceScraperInterface.php';

/**
 * Best-effort scraper base.
 *
 * Fetches a store's public search results page and looks for schema.org
 * Product/Offer JSON-LD blocks, which many retail sites embed for SEO even
 * when the visible results grid is rendered client-side. This is a starting
 * point, not a guarantee: HEB, Sprouts, and Costco all render search results
 * with JavaScript, so a plain HTTP GET frequently will NOT see product data
 * for a keyword search, only for a direct product page. Expect this to need
 * per-store tuning (or a headless-browser fetch) once run against the real
 * sites — this sandbox has no outbound access to verify the search URLs or
 * markup. Every result should be shown to the user as "needs review" before
 * it's trusted, and manual price entry remains the reliable path.
 */
abstract class BaseHttpScraper implements PriceScraperInterface {
    /** sprintf template with a single %s for the URL-encoded search term. */
    protected string $searchUrlTemplate = '';
    protected int $timeoutSeconds = 8;
    /** Minimum similar_text() match percentage to accept a candidate. */
    protected int $minMatchPercent = 35;
    protected ?string $lastError = null;
    /** Raw body of the most recent fetch, kept for diagnostics regardless of outcome. */
    protected ?string $lastHtml = null;

    public function getLastError(): ?string {
        return $this->lastError;
    }

    /** Raw HTML from the last fetch attempt (even a failed/blocked one), or null if none yet. */
    public function getLastHtml(): ?string {
        return $this->lastHtml;
    }

    public function lookupPrice(string $productName): ?array {
        $this->lastError = null;

        if ($this->searchUrlTemplate === '' || trim($productName) === '') {
            $this->lastError = 'No search term given.';
            return null;
        }

        $url = sprintf($this->searchUrlTemplate, rawurlencode($productName));
        $html = $this->fetch($url);
        if ($html === null) {
            return null; // lastError already set by fetch()
        }

        $result = $this->extractBestMatch($html, $productName);
        if ($result === null && $this->lastError === null) {
            $this->lastError = "Fetched the page okay, but couldn't find a confident product match in the results.";
        }
        return $result;
    }

    protected function fetch(string $url): ?string {
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
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 '
                . '(KHTML, like Gecko) Chrome/124.0 Safari/537.36',
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml'],
        ]);

        $body     = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->lastHtml = is_string($body) ? $body : null;

        if ($errno !== 0) {
            $this->lastError = "Network error reaching the store's site: $error";
            return null;
        }
        if ($httpCode >= 400) {
            $this->lastError = "The store's site returned HTTP $httpCode "
                . '(it may be blocking automated requests from this server).';
            return null;
        }
        if (!is_string($body) || $body === '') {
            $this->lastError = "The store's site returned an empty response.";
            return null;
        }

        return $body;
    }

    protected function extractBestMatch(string $html, string $productName): ?array {
        if (!preg_match_all(
            '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is',
            $html,
            $matches
        )) {
            return null;
        }

        $needle = strtolower($productName);
        $best   = null;

        foreach ($matches[1] as $block) {
            $data = json_decode(trim($block), true);
            if ($data === null) {
                continue;
            }

            foreach ($this->flattenNodes($data) as $node) {
                if (!is_array($node) || ($node['@type'] ?? '') !== 'Product') {
                    continue;
                }

                $price = $this->extractOfferPrice($node['offers'] ?? null);
                if ($price === null) {
                    continue;
                }

                $name = (string) ($node['name'] ?? '');
                similar_text($needle, strtolower($name), $percent);

                if ($best === null || $percent > $best['match_percent']) {
                    $best = [
                        'price'         => $price,
                        'matched_name'  => $name,
                        'raw_snippet'   => mb_substr($name . ' - $' . number_format($price, 2), 0, 200),
                        'match_percent' => $percent,
                    ];
                }
            }
        }

        if ($best === null || $best['match_percent'] < $this->minMatchPercent) {
            return null;
        }

        unset($best['match_percent']);
        return $best;
    }

    /** @param mixed $offers */
    private function extractOfferPrice($offers): ?float {
        if (is_array($offers) && isset($offers['price']) && is_numeric($offers['price'])) {
            return (float) $offers['price'];
        }
        if (is_array($offers) && isset($offers[0]['price']) && is_numeric($offers[0]['price'])) {
            return (float) $offers[0]['price'];
        }
        return null;
    }

    /** @param mixed $data @return array<int, mixed> */
    private function flattenNodes($data): array {
        if (!is_array($data)) {
            return [];
        }
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            return array_values($data['@graph']);
        }
        $isList = array_keys($data) === range(0, count($data) - 1);
        return $isList ? array_values($data) : [$data];
    }
}
