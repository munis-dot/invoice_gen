<?php
require_once __DIR__ . '/../../components/header.php';
?>
<div class="container">
    <div class="row mb-3">
        <div class="col">
            <h2>View Product</h2>
        </div>
        <div class="col text-end">
            <a href="list.php" class="btn btn-secondary">Back to List</a>
            <button id="editProduct" class="btn btn-primary">Edit Product</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-bordered">
                        <tr>
                            <th style="width: 150px;">SKU</th>
                            <td id="productSku"></td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td id="productName"></td>
                        </tr>
                        <tr>
                            <th>Price</th>
                            <td id="productPrice"></td>
                        </tr>
                        <tr>
                            <th>Tax Rate</th>
                            <td id="productTaxRate"></td>
                        </tr>
                        <tr>
                            <th>Stock</th>
                            <td id="productStock"></td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <div class="text-center">
                        <img id="productImage" src="" alt="Product Image" class="img-fluid img-thumbnail" style="max-width: 300px;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="module" src="/frontend/assets/js/product_view.js"></script>
<?php
require_once __DIR__ . '/../../components/footer.php';
?>