<?php
require_once __DIR__ . '/../../utils/api_client.php';

$count =  apiRequest("/invoice_gen/backend/public/api/dashboard", 'GET');

$customerCount = $count['customer'];
$invoiceCount = $count['invoice'];
$productCount = $count['product'];
?>
<html>
  <head>
  <link rel="stylesheet" href="assets/css/dashboard.css">
  </head>
</html>
  <div class="dashboard-layout">     
          <div class="content-header">
            <h1 class="dashboard-title">
              <i class="fas fa-chart-line"></i>
              Dashboard Overview
            </h1>
            <div class="header-actions">
              <button data-link="transactions/transaction_add" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                New Invoice
              </button>
            </div>
          </div>
          
          <!-- Stats Cards -->
          <div class="stats-container">
            <div class="stats-card card-customers">
              <div class="card-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="card-content">
                <h3><?php echo $customerCount; ?></h3>
                <p>Total Customers</p>
                <span class="card-trend positive">
                  <i class="fas fa-arrow-up"></i>
                  12% increase
                </span>
              </div>
            </div>
            
            <div class="stats-card card-invoices">
              <div class="card-icon">
                <i class="fas fa-file-invoice"></i>
              </div>
              <div class="card-content">
                <h3><?php echo $invoiceCount; ?></h3>
                <p>Total Invoices</p>
                <span class="card-trend positive">
                  <i class="fas fa-arrow-up"></i>
                  8% increase
                </span>
              </div>
            </div>
            
            <div class="stats-card card-products">
              <div class="card-icon">
                <i class="fas fa-boxes"></i>
              </div>
              <div class="card-content">
                <h3><?php echo $productCount; ?></h3>
                <p>Total Products</p>
                <span class="card-trend positive">
                  <i class="fas fa-arrow-up"></i>
                  5% increase
                </span>
              </div>
            </div>
            
           
          </div>
          
        </div>
  </div>