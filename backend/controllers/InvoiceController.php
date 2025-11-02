<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once '../services/InvoiceService.php';

class InvoiceController extends Controller {
    protected $service;
    public function __construct() {
        $this->service = new InvoiceService();
    }

    // POST /api/invoice/generate
    public function generateInvoice() {
        try {
            // Get JSON payload
            $payload = json_decode(file_get_contents("php://input"), true);
            if (!$payload || !isset($payload['customerId'], $payload['invoiceNumber'], $payload['date'], $payload['amount'], $payload['paymentMethod'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid payload']);
                return;
            }

            // Call service to process invoice
            $result = $this->service->processInvoice($payload);
            echo json_encode($result);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }
    }

    public function index() {
        AuthMiddleware::handle(true);
        $invoices = Invoice::allWithItems(); // fetch all invoices with items
        echo json_encode($invoices, JSON_PRETTY_PRINT);
    }
    
    public function findById(int $id): void {
        AuthMiddleware::handle(true);
        $invoice = Invoice::findByIdWithItems($id);
        $this->json($invoice ?? ['error' => 'Invoice not found'], $invoice ? 200 : 404);
    }
    

    public function show(int $id): void {
        AuthMiddleware::handle(true);
        $invoice = Invoice::find($id);
        $this->json($invoice ?? ['error' => 'Invoice not found'], $invoice ? 200 : 404);
    }

    public function store(): void {
        $user = AuthMiddleware::handle(true);
        $data = $this->input();
        $data['created_by'] = $user['id'];
        $ok = Invoice::create($data);
        $this->json(['success' => $ok]);
    }

    public function update(int $id): void {
        $user = AuthMiddleware::handle(true);
        $data = $this->input();
        $ok = Invoice::update($id, $data);
        $this->json(['success' => $ok]);
    }

    public function destroy(int $id): void {
        $user = AuthMiddleware::handle(true);
        $ok = Invoice::delete($id);
        $this->json(['success' => $ok]);
    }

     public function list()
    {
        AuthMiddleware::handle();
        $search = $_GET['search'] ?? '';
        $limit = $_GET['limit'] ?? 10;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $products = Invoice::getPaginatedAndFiltered($search, $limit, $offset);
        $total = Invoice::getTotalCount($search);

        $this->json([
            'data' => $products,
            'total' => $total,
            'page' => (int)$page,
            'limit' => (int)$limit,
        ]);
    }

    /**
     * Create multiple invoices at once
     */
    public function createBatch(): void
    {
        $user = AuthMiddleware::handle(true);
        $data = $this->input();
        
        if (!isset($data) || !is_array($data)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid request format. Expected array of invoices.'
            ], 400);
            return;
        }

        // Add created_by to each invoice
        foreach ($data as &$invoice) {
            $invoice['created_by'] = $user['id'];
        }

        try {
            $result = Invoice::createAll($data);
            
            if ($result['success']) {
                $this->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result
                ]);
            } else {
                $this->json([
                    'success' => false,
                    'message' => $result['message'],
                    'data' => $result
                ], 400);
            }
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to process batch invoice creation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getByCustomerId(int $customerId): void {
        AuthMiddleware::handle(true);
        $invoices = Invoice::getByCustomerId($customerId);
        $this->json($invoices ?? ['error' => 'Invoices not found'], $invoices ? 200 : 404);
    }
}
