<?php
require_once __DIR__ . '/utils/session.php';
checkSession();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Invoice Generator</title>
  
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/login.css">
  <link rel="stylesheet" href="assets/css/invoice.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>

<body>
  <?php include __DIR__ . '/components/header.php'; ?>

  <div class="app">
    <?php if (isset($_SESSION['user'])): ?>
      <!-- ✅ Logged-in user view -->
      <?php include __DIR__ . '/components/sidebar.php'; ?>
      <div class="content" id="app-content">
        <?php include __DIR__ . '/modules/dashboard/index.php'; ?>
      </div>
    <?php else: ?>
      <!-- 🚪 Login page for guests -->
      <div class="content" id="app-content">
        <?php include __DIR__ . '/modules/login.php'; ?>
      </div>
    <?php endif; ?>
  </div>

  <?php include __DIR__ . '/components/footer.php'; ?>
  <script src="assets/js/app.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</body>

</html>