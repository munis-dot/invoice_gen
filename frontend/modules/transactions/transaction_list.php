<?php
// UI: Transaction List
require_once __DIR__ . '/../../components/header.php';
?>
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>Transaction List</h2>
        </div>
        <div class="col d-flex justify-content-end align-items-center">
            <div class="input-group me-3" style="max-width: 300px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="transactionSearch" class="form-control" placeholder="Search transactions...">
            </div>
            <button data-link="transactions/transaction_add" class="btn btn-primary">Add Transaction</button>
        </div>
    </div>

    <table class="table table-bordered" id="transactionTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Invoice Number</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Amount</th>
                <th>Payment Method</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Transaction rows will be injected by JS -->
        </tbody>
    </table>

    <div id="transactionPagination" class="d-flex justify-content-between align-items-center">
        <!-- Pagination will be injected by JS -->
    </div>
</div>

<script type="module" src="/frontend/assets/js/transaction_list.js"></script>
<?php
require_once __DIR__ . '/../../components/footer.php';
?>
