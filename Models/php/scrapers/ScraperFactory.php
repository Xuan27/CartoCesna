<?php
require_once __DIR__ . '/PriceScraperInterface.php';
require_once __DIR__ . '/HebScraper.php';
require_once __DIR__ . '/SproutsScraper.php';
require_once __DIR__ . '/CostcoScraper.php';

class ScraperFactory {
    private static array $scrapers = [
        'heb'     => HebScraper::class,
        'sprouts' => SproutsScraper::class,
        'costco'  => CostcoScraper::class,
    ];

    public static function forStore(string $storeName): ?PriceScraperInterface {
        $class = self::$scrapers[strtolower(trim($storeName))] ?? null;
        return $class ? new $class() : null;
    }

    /** @return string[] */
    public static function supportedStores(): array {
        return array_keys(self::$scrapers);
    }
}
