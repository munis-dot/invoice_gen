<?php
// UI: Transaction List
require_once __DIR__ . '/../../components/header.php';
?>
<link rel="stylesheet" href="/frontend/assets/css/transaction_list.css">

<div class="transaction-container container mt-4">
    <div class="transaction-header">
        <h2 class="page-title">
            <i class="fas fa-receipt"></i> Transaction List
        </h2>

        <div class="header-actions">
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text search-icon">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="transactionSearch" class="form-control" placeholder="Search transactions...">
                </div>
            </div>

            <button data-link="transactions/transaction_add" class="btn-modern btn-gradient">
                <i class="fas fa-plus me-2"></i> Add Transaction
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover table-bordered" id="transactionTable">
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
    </div>

    <div id="transactionPagination" class="pagination-wrapper">
        <!-- Pagination will be injected by JS -->
    </div>
</div>

<script type="module" src="/frontend/assets/js/transaction_list.js"></script>
<?php
require_once __DIR__ . '/../../components/footer.php';
?>
