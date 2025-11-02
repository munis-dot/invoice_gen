<?php
require_once __DIR__ . '/../config/db.php';

class Customer {
    public static function all(): array {
        $stmt = DB::connect()->query("SELECT * FROM customers ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array {
        $stmt = DB::connect()->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): bool {
        $stmt = DB::connect()->prepare("
            INSERT INTO customers (name, phone, email, address)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['name'], 
            $data['phone'] ?? null, 
            $data['email'] ?? null, 
            $data['address'] ?? null
        ]);
    }

    public static function update(int $id, array $data): bool {
        $stmt = DB::connect()->prepare("
            UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?
        ");
        return $stmt->execute([
            $data['name'], 
            $data['phone'] ?? null, 
            $data['email'] ?? null, 
            $data['address'] ?? null, 
            $id
        ]);
    }

    public static function delete(int $id): bool {
        $stmt = DB::connect()->prepare("DELETE FROM customers WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getPaginatedAndFiltered(string $search = '',int $limit = 10, int $offset= 0) : array
{
    $limit = max(1, (int) $limit);
    $offset = max(0, (int) $offset);

    $pdo = DB::connect();

    $query = "
        SELECT * FROM customers 
        WHERE name LIKE :search OR email LIKE :email 
        ORDER BY id DESC
        LIMIT $limit OFFSET $offset
";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
    $stmt->bindValue(':email', '%' . $search . '%', PDO::PARAM_STR);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}


    public static function getTotalCount(string $search = ''): int {
        $pdo = DB::connect();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM customers 
            WHERE name LIKE :search OR email LIKE :email
        ");
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':email', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
