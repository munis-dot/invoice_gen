<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
<?php
// 🔐 Login UI only — authentication handled in login_action.php
require_once __DIR__ . '/../utils/session.php';

// ⚠️ Display error message if set
$error = '';
if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
} elseif (!empty($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>

  <!-- 🌈 Animated background shapes -->
  <div class="shape shape1"></div>
  <div class="shape shape2"></div>
  <div class="shape shape3"></div>

  <!-- 🧭 Login Form -->
  <div class="login-container">
    <form id="login-form" method="POST" action="login_action.php">
      <div class="login-icon">
        <i class="fa-solid fa-user-lock"></i>
      </div>
      <h3>Login</h3>
      <input name="username" type="email" placeholder="Email" required>
      <input name="password" type="password" placeholder="Password" required>
      <button type="submit">Login</button>

      <div id="login-error" style="color:red; display:none;"></div>
      <?php if (!empty($error)) echo "<p style='color:red'>" . htmlspecialchars($error) . "</p>"; ?>
    </form>
  </div>

</body>
</html>
