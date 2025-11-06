<?php
$conn = new mysqli("localhost","root","","invoice_db");
$res = $conn->query("SELECT id, name FROM invoice_templates");
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
