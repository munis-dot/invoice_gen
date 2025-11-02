<?php
require_once __DIR__ . '/../config/db.php';

class User {
    public static function findByEmail(string $email): ?array {
        $stmt = DB::connect()->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }
}
