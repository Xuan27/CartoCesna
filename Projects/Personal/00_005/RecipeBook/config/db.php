<?php
/**
 * Database connection for Vanessa's Recipe Book.
 *
 * Reuses the app's shared Database class (Private/db_config.php), the same
 * connection every other page uses, so it works with whatever credentials
 * that file supplies on each server (.env-driven or hand-edited).
 */
require_once __DIR__ . '/../../../../../Private/db_config.php';

class RecipeDB {
    private static $conn = null;

    public static function connect(): PDO {
        if (self::$conn === null) {
            self::$conn = (new Database())->getConnection();
        }
        return self::$conn;
    }
}
