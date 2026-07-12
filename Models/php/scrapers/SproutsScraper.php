<?php
require_once __DIR__ . '/BaseHttpScraper.php';

/**
 * Best-effort price lookup against Sprouts Farmers Market's public search results.
 * Search URL unverified from this environment (no outbound access to
 * sprouts.com) — confirm/adjust once run somewhere with real internet access.
 */
class SproutsScraper extends BaseHttpScraper {
    protected string $searchUrlTemplate = 'https://www.sprouts.com/search?q=%s';
}
