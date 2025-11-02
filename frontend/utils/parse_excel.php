<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

if (empty($_FILES['file']['tmp_name'])) {
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

try {
    $filePath = $_FILES['file']['tmp_name'];
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();

    $headers = array_map('trim', array_shift($rows));
    $items = [];

    foreach ($rows as $row) {
        $item = [];
        foreach ($headers as $index => $header) {
            $item[$header] = $row[$index] ?? '';
        }
        $items[] = $item;
    }

    echo json_encode(['items' => $items]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
