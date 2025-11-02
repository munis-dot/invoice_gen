<?php
class DB {
    private static ?PDO $instance = null;

    public static function connect(): PDO {
        if (self::$instance === null) {
            $DB_HOST = 'localhost';
            $DB_NAME = 'invoice_gen';
            $DB_USER = 'root';
            $DB_PASS = '';

            try {
                // Step 1️⃣ Connect to MySQL (without selecting DB first)
                $pdo = new PDO("mysql:host={$DB_HOST};charset=utf8mb4", $DB_USER, $DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);

                // Step 2️⃣ Create the database if it doesn't exist
                $pdo->exec("
                    CREATE DATABASE IF NOT EXISTS `{$DB_NAME}`
                    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
                ");

                // Step 3️⃣ Connect directly to the created/existing database
                $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4";
                self::$instance = new PDO($dsn, $DB_USER, $DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                // Step 4️⃣ Confirm DB is in use
                self::$instance->query("USE `{$DB_NAME}`");

            } catch (PDOException $e) {
                // JSON-style error output (safe for APIs)
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Database connection failed: ' . $e->getMessage()
                ]);
                exit;
            }
        }

        return self::$instance;
    }
}

// ✅ Optional quick test — runs only when accessed directly via browser
if (basename(__FILE__) === basename($_SERVER["SCRIPT_FILENAME"])) {
    try {
        $pdo = DB::connect();
        echo "✅ Database connected successfully!";
    } catch (Exception $e) {
        echo "❌ " . $e->getMessage();
    }
}
?>
