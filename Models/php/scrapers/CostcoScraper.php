<?php
require_once __DIR__ . '/BaseHttpScraper.php';

/**
 * Best-effort price lookup against Costco's public catalog search results.
 * Search URL unverified from this environment (no outbound access to
 * costco.com) — confirm/adjust once run somewhere with real internet access.
 * Costco also gates many prices behind membership login, so this will
 * frequently return null even when the search page itself loads.
 */
class CostcoScraper extends BaseHttpScraper {
    protected string $searchUrlTemplate = 'https://www.costco.com/CatalogSearch?dept=All&keyword=%s';
}
