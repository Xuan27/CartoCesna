<?php

interface PriceScraperInterface {
    /**
     * Best-effort lookup of a product's price by name.
     * Returns ['price' => float, 'matched_name' => string, 'raw_snippet' => string] on a
     * confident match, or null when nothing usable was found. Never throws — callers treat
     * null as "fall back to manual entry".
     */
    public function lookupPrice(string $productName): ?array;

    /**
     * Human-readable reason the most recent lookupPrice() call returned null (e.g. a
     * network error, an HTTP error from the store's site, or "fetched fine, nothing
     * matched"). Null if the last call succeeded or hasn't run yet.
     */
    public function getLastError(): ?string;
}
