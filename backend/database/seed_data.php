<?php
// database/seed_data.php

require_once __DIR__ . '/../config/db.php'; 

try {
    // ✅ Connect to database using DB class
    $pdo = DB::connect();

    // Securely hash password
    $adminPass = password_hash('admin123', PASSWORD_DEFAULT);

    // Check if admin already exists
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $checkStmt->execute(['admin@gmail.com']);
    $exists = $checkStmt->fetchColumn();

    if (!$exists) {
        // Insert default admin
        $insertStmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role)
            VALUES (:name, :email, :password, :role)
        ");
        $insertStmt->execute([
            ':name' => 'Admin',
            ':email' => 'admin@gmail.com',
            ':password' => $adminPass,
            ':role' => 'admin'
        ]);
        // echo "✅ Admin user created successfully.<br>";
    } else {
        // echo "ℹ️ Admin user already exists.<br>";
    }

} catch (PDOException $e) {
    echo "❌ Error seeding data: " . $e->getMessage();
}
?>
