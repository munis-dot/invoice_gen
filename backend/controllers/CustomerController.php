<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Customer.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class CustomerController extends Controller {
    public function index(): void {
        AuthMiddleware::handle(); // ✅ Require auth
        $this->json(Customer::all());
    }

    public function show(int $id): void {
        AuthMiddleware::handle();
        $customer = Customer::find($id);
        $this->json($customer ?? ['error' => 'Customer not found'], $customer ? 200 : 404);
    }

    public function store(): void {
        $user = AuthMiddleware::handle(true); // only admin can add
        $data = $this->input();
        $ok = Customer::create($data);
        $this->json(['success' => $ok]);
    }

    public function update(int $id): void {
        $user = AuthMiddleware::handle(true);
        $data = $this->input();
        $ok = Customer::update($id, $data);
        $this->json(['success' => $ok]);
    }

    public function destroy(int $id): void {
        $user = AuthMiddleware::handle(true);
        $ok = Customer::delete($id);
        $this->json(['success' => $ok]);
    }

    /**
     * Batch create multiple customers
     */
    public function batchCreate(): void {
        AuthMiddleware::handle(true); // admin only
        
        $data = $this->input();
        if (!isset($data) || !is_array($data)) {
            $this->json(['error' => 'Invalid input format. Expected array of items.'], 400);
            return;
        }

        $result = Customer::createAll($data);
        
        if ($result['success']) {
            $this->json([
                'message' => 'Customers created successfully',
                'summary' => [
                    'total' => $result['total'],
                    'successful' => $result['successful'],
                    'failed' => $result['failed']
                ]
            ], 201);
        } else {
            $this->json([
                'error' => 'Failed to create some or all customers',
                'summary' => [
                    'total' => $result['total'],
                    'successful' => $result['successful'],
                    'failed' => $result['failed']
                ],
                'errors' => $result['errors']
            ], 500);
        }
    }

     public function list()
    {
        AuthMiddleware::handle();
        $search = $_GET['search'] ?? '';
        $limit = $_GET['limit'] ?? 10;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $customers = Customer::getPaginatedAndFiltered($search, $limit, $offset);
        $total = Customer::getTotalCount($search);

        $this->json([
            'data' => $customers,
            'total' => $total,
            'page' => (int)$page,
            'limit' => (int)$limit,
        ]);
    }
}
