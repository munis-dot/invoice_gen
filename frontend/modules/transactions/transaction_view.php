<?php
// UI: Transaction View
require_once __DIR__ . '/../../components/header.php';
?>
<div class="container">
    <h2>Transaction Details</h2>
    <div id="transactionDetails">
        <!-- Transaction details will be injected by JS -->
    </div>
    <a href="transaction_list.php" class="btn btn-secondary">Back to List</a>
</div>
<script src="/frontend/assets/js/transaction_view.js"></script>
<?php
require_once __DIR__ . '/../../components/footer.php';
?>
