<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class ProductController extends Controller {

    public function index(): void {
        AuthMiddleware::handle();
        $this->json(Product::all());
    }

    public function show(int $id): void {
        AuthMiddleware::handle();
        $product = Product::find($id);
        if (!$product) {
            $this->json(['error' => 'Product not found'], 404);
            return;
        }
        $this->json($product);
    }

    public function store(): void {
        AuthMiddleware::handle(true); // admin only
        $data = $this->input();
        if (Product::create($data)) {
            $this->json(['message' => 'Product created successfully']);
        } else {
            $this->json(['error' => 'Failed to create product'], 500);
        }
    }

    public function update(int $id): void {
        AuthMiddleware::handle(true);
        $data = $this->input();
        if (Product::update($id, $data)) {
            $this->json(['message' => 'Product updated successfully']);
        } else {
            $this->json(['error' => 'Failed to update product'], 500);
        }
    }

    public function destroy(int $id): void {
        AuthMiddleware::handle(true);
        if (Product::delete($id)) {
            $this->json(['message' => 'Product deleted successfully']);
        } else {
            $this->json(['error' => 'Failed to delete product'], 500);
        }
    }

     public function list()
    {
        AuthMiddleware::handle();
        $search = $_GET['search'] ?? '';
        $limit = $_GET['limit'] ?? 10;
        $page = $_GET['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $products = Product::getPaginatedAndFiltered($search, $limit, $offset);
        $total = Product::getTotalCount($search);

        $this->json([
            'data' => $products,
            'total' => $total,
            'page' => (int)$page,
            'limit' => (int)$limit,
        ]);
    }

    /**
     * Batch create multiple products
     */
    public function batchCreate(): void
    {
        AuthMiddleware::handle(true); // admin only
        
        $data = $this->input();
        if (!isset($data) || !is_array($data)) {
            $this->json(['error' => 'Invalid input format. Expected array of items.'], 400);
            return;
        }

        $result = Product::createAll($data);
        
        if ($result['success']) {
            $this->json([
                'message' => 'Products created successfully',
                'summary' => [
                    'total' => $result['total'],
                    'successful' => $result['successful'],
                    'failed' => $result['failed']
                ]
            ], 201);
        } else {
            $this->json([
                'error' => 'Failed to create some or all products',
                'summary' => [
                    'total' => $result['total'],
                    'successful' => $result['successful'],
                    'failed' => $result['failed']
                ],
                'errors' => $result['errors']
            ], 500);
        }
    }
}
