<?php

interface PriceScraperInterface {
    /**
     * Best-effort lookup of a product's price by name.
     * Returns ['price' => float, 'matched_name' => string, 'raw_snippet' => string] on a
     * confident match, or null when nothing usable was found. Never throws — callers treat
     * null as "fall back to manual entry".
     */
    public function lookupPrice(string $productName): ?array;
}
