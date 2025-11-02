<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link rel="stylesheet" href="../assets/css/login.css">
</head>
<?php
// Login UI only. POSTs to login_action.php which contains the authentication logic
require_once __DIR__ . '/../utils/session.php';
// Styles are loaded from the HTML <head> (see frontend/index.php). Do not include CSS with PHP.
// Show any flash error stored in session or passed via GET
$error = '';
if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
} elseif (!empty($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}
?>

<div class="login-container">
  <form id="login-form" method="POST" action="login_action.php">
    <h3>Login</h3>
    <input name="username" type="email" placeholder="Email" required>
    <input name="password" type="password" placeholder="Password" required>
    <button type="submit">Login</button>
    <div id="login-error" style="color:red; display:none;"></div>
    <?php if (!empty($error)) echo "<p style='color:red'>" . htmlspecialchars($error) . "</p>"; ?>
  </form>
</div>
