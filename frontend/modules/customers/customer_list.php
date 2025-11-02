<?php
require_once __DIR__ . '/../../components/header.php';
?>
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>Customer List</h2>
        </div>
        <div class="col d-flex justify-content-end align-items-center">
            <div class="input-group me-3" style="max-width: 300px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="customerSearch" class="form-control" placeholder="Search customers...">
            </div>
            <button data-link="customers/customer_add" class="btn btn-primary">Add Customer</button>
        </div>
    </div>

    <table class="table table-bordered" id="customerTable">
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
        </tbody>
    </table>

    <div id="customerPagination" class="d-flex justify-content-between align-items-center">
        <!-- Pagination will be injected by JS -->
    </div>
</div>

<script type="module" src="/frontend/assets/js/customer_list.js"></script>
<?php
require_once __DIR__ . '/../../components/footer.php';
?>
