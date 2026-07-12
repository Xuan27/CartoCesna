<?php
require_once __DIR__ . '/BaseHttpScraper.php';

/**
 * Best-effort price lookup against HEB's public search results.
 * Search URL unverified from this environment (no outbound access to
 * heb.com) — confirm/adjust once run somewhere with real internet access.
 */
class HebScraper extends BaseHttpScraper {
    protected string $searchUrlTemplate = 'https://www.heb.com/search/?q=%s';
}
