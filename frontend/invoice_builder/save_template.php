<?php
$data = json_decode(file_get_contents("php://input"), true);

$conn = new mysqli("localhost","root","","invoice_db");

$stmt = $conn->prepare("INSERT INTO invoice_templates(name, layout_json) VALUES (?,?)");
$json = json_encode($data['layout']);
$stmt->bind_param("ss", $data['name'], $json);
$stmt->execute();

echo "Template Saved Successfully!";
