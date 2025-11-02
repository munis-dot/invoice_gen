<?php
require_once __DIR__ . '/../../components/header.php';
?>
<div class="container">
  <h2>Add Customer</h2>
  <form id="customerForm">
    <div class="form-group">
      <label>Customer Name</label>
      <input type="text" name="name" required>
    </div>
    <div class="form-group">
      <label>Email</label>
      <input type="email" name="email" required>
    </div>
    <div class="form-group">
      <label>Phone</label>
      <input type="text" name="phone" required>
    </div>
    <div class="form-group">
      <label>Address</label>
      <textarea name="address" rows="3"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Save</button>
  </form>

  <hr>
  <h3>Bulk Upload (CSV / Excel)</h3>
  <form id="uploadForm" enctype="multipart/form-data">
    <div class="form-group">
      <input type="file" name="file" accept=".csv, .xlsx" required>
    </div>
    <button type="submit" class="btn btn-primary">Upload</button>
  </form>
  
  <div id="addCustomerResult"></div>
</div>
<script src="/frontend/assets/js/customer_add.js"></script>
<?php
require_once __DIR__ . '/../../components/footer.php';
?>
