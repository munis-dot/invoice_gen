<?php
require_once __DIR__ . '/../config/db.php';

class Customer {
    public static function all(): array {
        $stmt = DB::connect()->query("SELECT * FROM customers ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $stmt = DB::connect()->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): bool {
        $stmt = DB::connect()->prepare("
            INSERT INTO customers (name, phone, email, address)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$data['name'], $data['phone'], $data['email'], $data['address']]);
    }

    public static function update(int $id, array $data): bool {
        $stmt = DB::connect()->prepare("
            UPDATE customers SET name=?, phone=?, email=?, address=? WHERE id=?
        ");
        return $stmt->execute([$data['name'], $data['phone'], $data['email'], $data['address'], $id]);
    }

    public static function delete(int $id): bool {
        $stmt = DB::connect()->prepare("DELETE FROM customers WHERE id = ?");
        return $stmt->execute([$id]);
    }

     public static function getPaginatedAndFiltered($search = '', $limit = 10, $offset = 0)
    {
        $query = "SELECT * FROM customers WHERE name LIKE :search OR email LIKE :search LIMIT :limit OFFSET :offset";
        $stmt =  DB::connect()->prepare($query);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getTotalCount($search = '')
    {
        $query = "SELECT COUNT(*) as total FROM customers WHERE name LIKE :search OR email LIKE :search";
        $stmt =  DB::connect()->prepare($query);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}