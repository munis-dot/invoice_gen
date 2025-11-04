<?php
require_once __DIR__ . '/../../config/DB.php'; // Adjusted to match your folder path

try {
    // ✅ Get PDO instance from your DB class
    $pdo = DB::connect();

    // Ensure PDO throws exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
    }

    // 1️⃣ users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 2️⃣ products table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            tax_rate DECIMAL(5,2) DEFAULT 0.00,
            stock INT DEFAULT 0,
            image_url VARCHAR(255),
            product_type ENUM('physical','digital') NOT NULL DEFAULT 'physical',
            sku VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 3️⃣ customers table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            phone VARCHAR(20),
            email VARCHAR(150),
            address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 4️⃣ invoices table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_number VARCHAR(50) NOT NULL UNIQUE,
            customer_id INT NOT NULL,
            date DATE NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            discount DECIMAL(10,2) DEFAULT 0.00,
            tax DECIMAL(10,2) DEFAULT 0.00,
            total DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
            pdf_path VARCHAR(255),
            address TEXT,
            email VARCHAR(150),
            company_logo VARCHAR(255),
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 5️⃣ invoice_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS invoice_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            product_id INT NOT NULL,
            description VARCHAR(255),
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            total DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // 6️⃣ payments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            product_type VARCHAR(50) NOT NULL DEFAULT 'physical',
            FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");



    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    

    // echo "✅ All tables created successfully with relationships.";
    


} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Error creating tables: " . $e->getMessage();
}
?>
