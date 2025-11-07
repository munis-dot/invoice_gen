<?php
require_once __DIR__ . '/utils/session.php';
checkSession();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice Generator</title>
  
  <!-- CSS Files -->
  <link rel="stylesheet" href="assets/css/sidebar.css">
  <link rel="stylesheet" href="assets/css/header.css">
  <link rel="stylesheet" href="assets/css/list.css">
  <link rel="stylesheet" href="assets/css/app.css">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <?php if (isset($_SESSION['user'])): ?>
    <!-- ✅ Logged-in user view -->
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <div class="app">
      <?php include __DIR__ . '/components/sidebar.php'; ?>
      
      <main class="main-content" id="app-content">
        <!-- Dashboard content will be loaded here by default -->
        <div class="content-area">
          <?php 
          // Determine which module to load based on URL or default to dashboard
          $requestedModule = $_GET['module'] ?? 'dashboard';
          $modulePath = __DIR__ . "/modules/{$requestedModule}/index.php";
          
          if (file_exists($modulePath)) {
              include $modulePath;
          } else {
              // Fallback to dashboard
              include __DIR__ . '/modules/dashboard/index.php';
          }
          ?>
        </div>
      </main>
    </div>
  <?php else: ?>
    <!-- 🚪 Login page for guests -->
    <div class="login-container">
      <?php include __DIR__ . '/modules/login.php'; ?>
    </div>
  <?php endif; ?>

  <?php include __DIR__ . '/components/footer.php'; ?>

  <!-- JavaScript Libraries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Application Scripts -->
  <script src="assets/js/app.js"></script>
  <script src="assets/js/invoice_builder.js"></script>
  <!-- <script src="assets/js/dashboard.js"></script>
  <script src="assets/js/navigation.js"></script> -->
</body>

</html>