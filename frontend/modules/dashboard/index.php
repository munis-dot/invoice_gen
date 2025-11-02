<?php
 $customerCount = 10;
    $invoiceCount = 10;
    $transactionCount = 10;
?>
<!DOCTYPE html>
<html lang="en">
<body>
  <div class="dashboard-container">
    <div class="dashboard-card card-customers">
      <h2><?php echo $customerCount; ?></h2>
      <p>Customers</p>
    </div>

    <div class="dashboard-card card-invoices">
      <h2><?php echo $invoiceCount; ?></h2>
      <p>Invoices</p>
    </div>

    <div class="dashboard-card card-transactions">
      <h2><?php echo $transactionCount; ?></h2>
      <p>Transactions</p>
    </div>
  </div>
</body>
</html>
