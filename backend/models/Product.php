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
            "INSERT INTO products (name, price, stock, product_type, image_url, sku, tax_rate) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['name'], 
            $data['price'], 
            $data['stock'], 
            $data['product_type'],
            $data['image_url'] ?? null,
            $data['sku'],
            $data['tax_rate']
        ]);
    }

    public static function update(int $id, array $data): bool
    {
        $stmt = DB::connect()->prepare(
            "UPDATE products SET name = ?, price = ?, stock = ?, product_type = ?, image_url = ?, sku = ?, tax_rate = ? WHERE id = ?"
        );
        return $stmt->execute([
            $data['name'], 
            $data['price'], 
            $data['stock'], 
            $data['product_type'],
            $data['image_url'] ?? null,
            $data['sku'],
            $data['tax_rate'],
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = DB::connect()->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Create multiple products at once
     * @param array $items Array of product data arrays
     * @return array Array containing success status and any errors
     */
    public static function createAll(array $items): array
    {
        $requiredFields = ['name', 'price', 'stock', 'tax_rate', 'image_url', 'product_type', 'sku'];
        $errors = [];
        
        // First validate all items before starting transaction
        foreach ($items as $index => $item) {
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($item[$field]) || trim($item[$field]) === '') {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                $errors[] = [
                    'index' => $index,
                    'item' => $item,
                    'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                ];
            }
        }
        
        // If any validation errors, return early
        if (!empty($errors)) {
            return [
                'success' => false,
                'total' => count($items),
                'successful' => 0,
                'failed' => count($items),
                'errors' => $errors,
                'message' => 'Validation failed: All fields are required'
            ];
        }
        
        $db = DB::connect();
        $db->beginTransaction();
        
        $stmt = $db->prepare(
            "INSERT INTO products (name, price, stock, tax_rate, image_url, product_type, sku) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        
        $successCount = 0;
        
        try {
            foreach ($items as $index => $item) {
                try {
                    if ($stmt->execute([
                        $item['name'],
                        $item['price'],
                        $item['stock'],
                        $item['tax_rate'],
                        $item['image_url'],
                        $item['product_type'],
                        $item['sku']
                    ])) {
                        $successCount++;
                    } else {
                        $errors[] = [
                            'index' => $index,
                            'item' => $item,
                            'message' => 'Failed to insert product'
                        ];
                    }
                } catch (PDOException $e) {
                    $errors[] = [
                        'index' => $index,
                        'item' => $item,
                        'message' => $e->getMessage()
                    ];
                }
            }
            
            if (empty($errors)) {
                $db->commit();
            } else {
                $db->rollBack();
            }
            
            return [
                'success' => empty($errors),
                'total' => count($items),
                'successful' => $successCount,
                'failed' => count($errors),
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'total' => count($items),
                'successful' => $successCount,
                'failed' => count($items) - $successCount,
                'errors' => [[
                    'message' => 'Transaction failed: ' . $e->getMessage()
                ]]
            ];
        }
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

