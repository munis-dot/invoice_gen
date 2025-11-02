<?php
require_once __DIR__ . '/../../utils/generic_processor.php';

// Define entity configuration for products
$productConfig = [
    'entityName' => 'products',
    'apiEndpoint' => '/api/products',
    'batchEndpoint' => '/api/products/batch',
    'successMessage' => 'Product added successfully',
    'fields' => [
        'name' => [
            'fileField' => 'name',
            'required' => true,
            'transform' => 'string'
        ],
        'sku' => [
            'fileField' => 'sku',
            'required' => true,
            'transform' => 'string'
        ],
        'price' => [
            'fileField' => 'price',
            'required' => true,
            'transform' => 'float',
            'validate' => ['numeric' => true, 'min' => 0]
        ],
        'tax_rate' => [
            'fileField' => 'tax_rate',
            'required' => true,
            'transform' => 'float',
            'validate' => ['numeric' => true, 'min' => 0]
        ],
        'stock' => [
            'fileField' => 'stock',
            'required' => true,
            'transform' => 'int',
            'validate' => ['numeric' => true, 'min' => 0]
        ]
    ]
];

// Special handling for file upload
class ProductProcessor extends GenericProcessor {
    public function getById($id) {
        try {
            $endpoint = 'backend/public/index.php/api/products/' . $id;
            $response = apiRequest($endpoint, 'GET');
            
            if (isset($response['error'])) {
                throw new Exception($response['error']);
            }
            
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    protected function beforeProcess($data) {
        // Handle image removal
        if (isset($data['remove_image']) && $data['remove_image'] === '1') {
            $data['image_url'] = null;
            unset($data['remove_image']);
            return $data;
        }

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Please upload a JPEG, PNG, or GIF image.');
            }

            // Validate file size (5MB max)
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                throw new Exception('File size too large. Maximum size is 5MB.');
            }

            $uploadDir = __DIR__ . '/../../uploads/products/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = uniqid('product_') . '_' . basename($file['name']);
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $data['image_url'] = '/frontend/uploads/products/' . $fileName;
            } else {
                throw new Exception('Failed to upload image. Please try again.');
            }
        }
        return $data;
    }
}

// Handle incoming requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $processor = new ProductProcessor($productConfig);
    header('Content-Type: application/json');
    
    try {
        if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
            // Get product details
            $result = $processor->getById($_GET['id']);
        } elseif (isset($_FILES['file'])) {
            // Bulk file upload processing
            $result = $processor->processFileUpload($_FILES['file']);
        } else {
            // Manual form submission
            $data = $_POST;
            $result = $processor->processManualSubmission($data);
        }
        
        if (!isset($result['success'])) {
            $result = ['success' => true, 'data' => $result];
        }
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>