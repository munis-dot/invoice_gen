<div class="list-container">
    <div class="list-header">
        <h2 class="page-title">
            <i class="fas fa-box"></i>
            Products List
        </h2>

        <div class="header-actions">
            <div class="search-box">
                <div class="input-group">
                    <span class="input-group-text search-icon">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" id="productSearch" class="form-control" placeholder="Search products...">
                </div>
            </div>

            <button data-link="products/create" class="btn-modern btn-gradient">
                <i class="fas fa-plus me-2"></i> Add Product
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-modern" id="productTable">
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
                <tr class="loading-row">
                    <td colspan="8">
                        <div class="loading-spinner"></div>
                        Loading products...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div id="productPagination" class="pagination-wrapper">
        <!-- Pagination will be injected by JS -->
    </div>
</div>

<script type="module" src="/frontend/assets/js/product_list.js"></script>