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

    /**
     * Create multiple customers at once
     * @param array $items Array of customer data arrays
     * @return array Array containing success status and any errors
     */
    public static function createAll(array $items): array {
        $requiredFields = ['name', 'email', 'phone'];
        $errors = [];
        
        // First validate all items before starting transaction
        foreach ($items as $index => $item) {
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($item[$field]) || trim($item[$field]) === '') {
                    $missingFields[] = $field;
                }
            }
            
            // Validate email format
            if (!empty($item['email']) && !filter_var($item['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = [
                    'index' => $index,
                    'item' => $item,
                    'message' => 'Invalid email format'
                ];
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
                'message' => 'Validation failed: All required fields must be provided'
            ];
        }
        
        $db = DB::connect();
        $db->beginTransaction();
        
        $stmt = $db->prepare(
            "INSERT INTO customers (name, email, phone, address) VALUES (?, ?, ?, ?)"
        );
        
        $successCount = 0;
        
        try {
            foreach ($items as $index => $item) {
                try {
                    if ($stmt->execute([
                        $item['name'],
                        $item['email'],
                        $item['phone'],
                        $item['address'] ?? null
                    ])) {
                        $successCount++;
                    } else {
                        $errors[] = [
                            'index' => $index,
                            'item' => $item,
                            'message' => 'Failed to insert customer'
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
