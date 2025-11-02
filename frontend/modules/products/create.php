<?php
require_once __DIR__ . '/../../components/header.php';
$isEdit = isset($_GET['id']);
$pageTitle = $isEdit ? 'Edit Product' : 'Add New Product';
?>
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2><?php echo $pageTitle; ?></h2>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="productForm" class="needs-validation" novalidate enctype="multipart/form-data">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" id="productId" value="<?php echo htmlspecialchars($_GET['id']); ?>">
                <?php endif; ?>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="name" class="form-label">Product Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please enter a product name.</div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="sku" class="form-label">SKU</label>
                            <input type="text" class="form-control" id="sku" name="sku" required>
                            <div class="invalid-feedback">Please enter a SKU.</div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="price" class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                <div class="invalid-feedback">Please enter a valid price.</div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" min="0" required>
                                <span class="input-group-text">%</span>
                                <div class="invalid-feedback">Please enter a valid tax rate.</div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                            <div class="invalid-feedback">Please enter a valid stock quantity.</div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label for="image" class="form-label">Product Image</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*">
                            <div class="invalid-feedback">Please select a valid image file.</div>
                        </div>

                        <div id="imagePreviewContainer" class="mb-3 d-none">
                            <label class="form-label">Current Image</label>
                            <div class="position-relative">
                                <img id="imagePreview" src="" alt="Product image preview" class="img-thumbnail" style="max-width: 200px;">
                                <button type="button" id="removeImage" class="btn btn-danger btn-sm position-absolute top-0 end-0">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col">
                        <button type="submit" class="btn btn-primary">Save Product</button>
                        <button data-link="products/list" class="btn btn-secondary">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="formResult" class="mt-3"></div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>