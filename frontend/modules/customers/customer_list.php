<div class="list-container">
    <div class="list-header">
        <h2 class="page-title">
            <i class="fas fa-users"></i>
            Customer List
        </h2>

        <div class="header-actions">
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text search-icon">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="customerSearch" class="form-control" placeholder="Search customers...">
                </div>
            </div>

            <button data-link="customers/customer_add" class="btn-modern btn-gradient">
                <i class="fas fa-plus me-2"></i> Add Customer
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-modern" id="customerTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Customer rows will be injected by JS -->
                <tr class="loading-row">
                    <td colspan="7">
                        <div class="loading-spinner"></div>
                        Loading customers...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="customerPagination" class="pagination-wrapper">
        <!-- Pagination will be injected by JS -->
    </div>
</div>

<script type="module" src="/frontend/assets/js/customer_list.js"></script>
<?php
require_once __DIR__ . '/../../components/footer.php';
?>