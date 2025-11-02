<?php
require_once __DIR__ . '/api_client.php';
$id = $_GET['id'] ?? null;
if (!$id) { die('Customer ID missing'); }

$customer = apiRequest("backend/public/api/customers/$id", 'GET');
?>
<div class="container">
  <h2>Customer Details</h2>

  <?php if (!empty($customer) && empty($customer['error'])): ?>
    <form id="updateForm">
      <input type="hidden" name="id" value="<?= $customer['id'] ?>">
      <div class="form-group">
        <label>Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($customer['name']) ?>">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($customer['email']) ?>">
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($customer['phone']) ?>">
      </div>
      <div class="form-group">
        <label>Address</label>
        <textarea name="address"><?= htmlspecialchars($customer['address']) ?></textarea>
      </div>
      <button type="submit">Update</button>
      <button type="button" id="back-to-list">Back</button>
    </form>
  <?php else: ?>
    <p>Error: <?= $customer['error'] ?? 'Not found' ?></p>
  <?php endif; ?>
</div>
