<?php
require_once __DIR__ . '/../config/db.php';

class Product
{
    public static function all(): array
    {
        $stmt = DB::connect()->query("SELECT * FROM products ORDER BY id DESC");
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = DB::connect()->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): bool
    {
        $stmt = DB::connect()->prepare(
            "INSERT INTO products (name, price, stock, product_type) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([$data['name'], $data['price'], $data['stock'], $data['product_type']]);
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = DB::connect()->prepare(
            "UPDATE products SET name = ?, price = ?, stock = ?, product_type = ? WHERE id = ?"
        );
        return $stmt->execute([$data['name'], $data['price'], $data['stock'], $data['product_type'], $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = DB::connect()->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function getPaginatedAndFiltered(string $search = '', int $limit = 10, int $offset = 0): array
    {
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $db = DB::connect();

        $query = "
        SELECT *
        FROM products
        WHERE name LIKE :searchName OR product_type LIKE :searchType
        LIMIT $limit OFFSET $offset
    ";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':searchName', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':searchType', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }



    public static function getTotalCount(string $search = ''): int
    {
        $db = DB::connect();

        $query = "
        SELECT COUNT(*) 
        FROM products 
        WHERE name LIKE :searchName OR product_type LIKE :searchType
    ";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':searchName', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':searchType', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }
}

