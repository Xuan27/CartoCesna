<?php
/**
 * Database connection for Vanessa's Recipe Book.
 *
 * Lives in the same 'cartocesna' database as the rest of the app (the
 * `categories` and `recipes` tables), using the shared .env credentials via
 * the same Env/Database convention as the rest of the codebase.
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
        $dbName = Env::get('DB_NAME', 'cartocesna');

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
