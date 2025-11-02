<?php
require_once __DIR__ . '/../config/db.php';

class Invoice
{

    protected $pdo;

    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    // ---------------- CRUD ----------------
    public static function all(): array
    {
        $stmt = DB::connect()->query("
            SELECT i.*, c.name AS customer_name, u.name AS created_by_name
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            LEFT JOIN users u ON i.created_by = u.id
            ORDER BY i.id DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $stmt = DB::connect()->prepare("
            SELECT i.*, c.name AS customer_name, u.name AS created_by_name
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            LEFT JOIN users u ON i.created_by = u.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // ---------------- Fetch invoice with items ----------------
    public static function allWithItems(): array
    {
        $pdo = DB::connect();

        // Fetch all invoices with customer
        $stmt = $pdo->query("
            SELECT i.*, c.id AS customer_id, c.name AS customer_name, c.phone AS customer_phone, c.email AS customer_email, c.address AS customer_address,
                   u.name AS created_by_name
            FROM invoices i
            LEFT JOIN customers c ON i.customer_id = c.id
            LEFT JOIN users u ON i.created_by = u.id
            ORDER BY i.id DESC
        ");
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($invoices as &$invoice) {
            // Attach customer as object
            $invoice['customer'] = [
                'id' => $invoice['customer_id'],
                'name' => $invoice['customer_name'],
                'phone' => $invoice['customer_phone'],
                'email' => $invoice['customer_email'],
                'address' => $invoice['customer_address']
            ];

            // Remove redundant fields
            unset($invoice['customer_id'], $invoice['customer_name'], $invoice['customer_phone'], $invoice['customer_email'], $invoice['customer_address']);

            // Fetch invoice items with product info
            $stmtItems = $pdo->prepare("
                SELECT ii.*, p.id AS product_id, p.name AS product_name, p.price AS product_price, 
                       p.tax_rate AS product_tax_rate, p.product_type
                FROM invoice_items ii
                LEFT JOIN products p ON ii.product_id = p.id
                WHERE ii.invoice_id = ?
            ");
            $stmtItems->execute([$invoice['id']]);
            $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as &$item) {
                $item['product'] = [
                    'id' => $item['product_id'],
                    'name' => $item['product_name'],
                    'price' => $item['product_price'],
                    'tax_rate' => $item['product_tax_rate'],
                    'product_type' => $item['product_type']
                ];

                unset($item['product_id'], $item['product_name'], $item['product_price'], $item['product_tax_rate'], $item['product_type']);
            }

            $invoice['items'] = $items;
        }

        return $invoices;
    }

    public static function findByIdWithItems(int $id): ?array
    {
        $pdo = DB::connect();

        // Single query using LEFT JOINs to fetch invoice + customer + user + items + products
        $stmt = $pdo->prepare("
        SELECT 
            i.id AS invoice_id, i.invoice_number, i.date, i.subtotal, i.discount, i.tax, i.total, 
            i.created_at, i.created_by,
            
            c.id AS customer_id, c.name AS customer_name, c.phone AS customer_phone, 
            c.email AS customer_email, c.address AS customer_address,
            
            u.name AS created_by_name,
            
            ii.id AS item_id, ii.quantity, ii.price AS item_price, ii.total AS item_total,
            
            p.id AS product_id, p.name AS product_name, p.price AS product_price, 
            p.tax_rate AS product_tax_rate, p.product_type
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN users u ON i.created_by = u.id
        LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
        LEFT JOIN products p ON ii.product_id = p.id
        WHERE i.id = :id
    ");
        $stmt->execute([':id' => $id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return null;
        }

        // Build structured result
        $first = $rows[0];
        $invoice = [
            'id' => $first['invoice_id'],
            'invoice_number' => $first['invoice_number'],
            'date' => $first['date'],
            'subtotal' => $first['subtotal'],
            'discount' => $first['discount'],
            'tax' => $first['tax'],
            'total' => $first['total'],
            'created_at' => $first['created_at'],
            'created_by' => $first['created_by'],
            'created_by_name' => $first['created_by_name'],
            'customer' => [
                'id' => $first['customer_id'],
                'name' => $first['customer_name'],
                'phone' => $first['customer_phone'],
                'email' => $first['customer_email'],
                'address' => $first['customer_address'],
            ],
            'items' => [],
        ];

        // Collect all items
        foreach ($rows as $row) {
            if (!$row['item_id']) {
                continue; // handle invoices with no items
            }

            $invoice['items'][] = [
                'id' => $row['item_id'],
                'quantity' => $row['quantity'],
                'price' => $row['item_price'],
                'total' => $row['item_total'],
                'product' => [
                    'id' => $row['product_id'],
                    'name' => $row['product_name'],
                    'price' => $row['product_price'],
                    'tax_rate' => $row['product_tax_rate'],
                    'product_type' => $row['product_type'],
                ],
            ];
        }

        return $invoice;
    }


    // ---------------- Invoice generation flow ----------------
    public function getAllProducts(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM products WHERE stock > 0");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createInvoice(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO invoices (invoice_number, customer_id, date, subtotal, discount, tax, total, created_by, pdf_path)
            VALUES (:invoice_number, :customer_id, :date, :subtotal, :discount, :tax, :total, :created_by, :pdf_path)
        ");
        $stmt->execute([
            ':invoice_number' => $data['invoice_number'],
            ':customer_id' => $data['customer_id'],
            ':date' => $data['date'],
            ':subtotal' => $data['subtotal'],
            ':discount' => $data['discount'] ?? 0,
            ':tax' => $data['tax'] ?? 0,
            ':total' => $data['total'],
            ':created_by' => $data['created_by'] ?? null,
            ':pdf_path' => $data['pdf_path'] ?? null
        ]);
        return $this->pdo->lastInsertId();
    }

    public function createInvoiceItems(int $invoiceId, array $items)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO invoice_items 
            (invoice_id, product_id, description, quantity, price, total)
            VALUES (:invoice_id, :product_id, :description, :quantity, :price, :total)
        ");

        foreach ($items as $item) {
            $stmt->execute([
                ':invoice_id' => $invoiceId,
                ':product_id' => $item['id'],
                ':description' => $item['name'],
                ':quantity' => $item['qty'],
                ':price' => $item['price'],
                ':total' => $item['total']
            ]);

            // Optional: Reduce product stock
            // if ($item['type'] === 'physical') {
            //     $this->pdo->exec("UPDATE products SET stock = stock - {$item['qty']} WHERE id = {$item['id']}");
            // }
        }
    }

    public static function getPaginatedAndFiltered(string $search = '', int $limit = 10, int $offset = 0): array
    {
        $limit = max(1, (int) $limit);
        $offset = max(0, (int) $offset);

        $db = DB::connect();

        $query = "
        SELECT 
            i.*, 
            c.name AS customer_name, 
            u.name AS created_by_name
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        LEFT JOIN users u ON i.created_by = u.id
        WHERE i.invoice_number LIKE :searchNum
           OR c.name LIKE :searchName
        ORDER BY i.id DESC
        LIMIT $limit OFFSET $offset
    ";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':searchNum', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':searchName', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getTotalCount(string $search = ''): int
    {
        $db = DB::connect();

        $query = "
        SELECT COUNT(*) 
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        WHERE i.invoice_number LIKE :search
           OR c.name LIKE :search2
    ";

        $stmt = $db->prepare($query);
        $stmt->bindValue(':search2', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    /**
     * Create multiple invoices at once
     * @param array $invoices Array of invoice data with their items
     * @return array Array containing success status and any errors
     */
    public static function createAll(array $invoices): array {
        require_once __DIR__ . '/Customer.php';
        
        $errors = [];
        $db = DB::connect();
        $db->beginTransaction();
        $invoiceService = new InvoiceService();
        
        // Required fields validation
        $requiredFields = ['invoiceNumber', 'paymentMethod', 'customerId', 'date', 'amount', 'discount'];
        $successCount = 0;
        $processedInvoices = [];
        
        try {
            foreach ($invoices as $index => $invoice) {
                // Validate required fields
                $missingFields = [];
                foreach ($requiredFields as $field) {
                    if (!isset($invoice[$field])) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    $errors[] = [
                        'index' => $index,
                        'invoice' => $invoice,
                        'message' => 'Missing required fields: ' . implode(', ', $missingFields)
                    ];
                    continue;
                }
                
                // Validate customer exists
                $customer = Customer::find($invoice['customerId']);
                if (!$customer) {
                    $errors[] = [
                        'index' => $index,
                        'invoice' => $invoice,
                        'message' => "Customer not found with ID: {$invoice['customer_id']}"
                    ];
                    continue;
                }

                try {
                    // Process invoice using service
                    $result = $invoiceService->processInvoice([
                        'customerId' => $invoice['customerId'],
                        'invoiceNumber' => $invoice['invoiceNumber'],
                        'date' => $invoice['date'],
                        'amount' => $invoice['amount'],
                        'created_by' => $invoice['created_by'] ?? null,
                        'discount' => $invoice['discount'] ?? true,
                        'paymentMethod' => $invoice['paymentMethod'] ?? 'cash'
                    ]);
                    
                    $processedInvoices[] = $result;
                    $successCount++;
                    
                } catch (Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'invoice' => $invoice,
                        'message' => "Failed to process invoice: " . $e->getMessage()
                    ];
                    continue;
                }
            }
            
            if (empty($errors)) {
                $db->commit();
                return [
                    'success' => true,
                    'total' => count($invoices),
                    'successful' => $successCount,
                    'failed' => 0,
                    'data' => $processedInvoices,
                    'message' => 'All invoices created and processed successfully'
                ];
            } else {
                $db->rollBack();
                return [
                    'success' => false,
                    'total' => count($invoices),
                    'successful' => 0,
                    'failed' => count($invoices),
                    'errors' => $errors,
                    'message' => 'Failed to create invoices due to validation errors'
                ];
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'total' => count($invoices),
                'successful' => $successCount,
                'failed' => count($invoices) - $successCount,
                'errors' => [[
                    'message' => 'Transaction failed: ' . $e->getMessage()
                ]],
                'message' => 'Failed to create invoices'
            ];
        }
    }

}



