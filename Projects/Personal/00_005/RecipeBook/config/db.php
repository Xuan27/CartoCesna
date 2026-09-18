<?php
/**
 * Database connection for Vanessa's Recipe Book.
 *
 * Reuses the CartoCesna app's DB host/user/password from the shared .env file
 * (so credentials live in exactly one place), but talks to its OWN dedicated
 * database — 'vanessa_recipes' — which is completely separate from the
 * survey-project business data in 'cartocesna'.
 */
require_once __DIR__ . '/../../../../../classes/Env.php';

class RecipeDB {
    private static $conn = null;

    public static function connect(): PDO {
        if (self::$conn !== null) {
            return self::$conn;
        }

        Env::load();

        $host = Env::get('DB_HOST', 'localhost');
        $username = Env::get('DB_USER', 'root');
        $password = Env::get('DB_PASS', '');
        $dbName = 'vanessa_recipes';

        try {
            $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";
            self::$conn = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('RecipeDB connection error: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }

        return self::$conn;
    }
}
