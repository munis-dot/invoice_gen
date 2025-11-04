<?php
require_once __DIR__ . '/../../utils/api_client.php';

$count =  apiRequest("/invoice_gen/backend/public/api/dashboard", 'GET');

$customerCount = $count['customer'];
$invoiceCount = $count['invoice'];
$productCount = $count['product'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>

  <!-- 🌀 Floating Background Circles -->
  <div class="bg-shape shape1"></div>
  <div class="bg-shape shape2"></div>
  <div class="bg-shape shape3"></div>
  <div class="bg-shape shape4"></div>

  <div class="dashboard-wrapper">
    <h1 class="dashboard-title">📊 Dashboard Overview</h1>

    <div class="dashboard-container">
      <div class="dashboard-card card-customers">
        <div class="icon">👥</div>
        <h2><?php echo $customerCount; ?></h2>
        <p>Customers</p>
      </div>

      <div class="dashboard-card card-invoices">
        <div class="icon">🧾</div>
        <h2><?php echo $invoiceCount; ?></h2>
        <p>Invoices</p>
      </div>

      <div class="dashboard-card card-transactions">
        <div class="icon">💸</div>
        <h2><?php echo $productCount; ?></h2>
        <p>Products</p>
      </div>
    </div>
  </div>

</div>
</body>
</html>
