<?php
require_once __DIR__ . '/../../components/header.php';
?>
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>Products List</h2>
        </div>
        <div class="col d-flex justify-content-end align-items-center">
            <div class="input-group me-3" style="max-width: 300px;">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="productSearch" class="form-control" placeholder="Search products...">
            </div>
            <button data-link="products/create" class="btn btn-primary">Add Product</button>
        </div>
    </div>

    <table class="table table-bordered" id="productTable">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>SKU</th>
                <th>Name</th>
                <th>Price</th>
                <th>Tax Rate</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <!-- Product rows will be injected by JS -->
        </tbody>
    </table>

    <div id="productPagination" class="d-flex justify-content-between align-items-center">
        <!-- Pagination will be injected by JS -->
    </div>
</div>
