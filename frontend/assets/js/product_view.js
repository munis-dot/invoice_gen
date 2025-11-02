document.addEventListener('DOMContentLoaded', async function() {
    // Get product ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const productId = urlParams.get('id');

    if (!productId) {
        window.location.href = 'list.php';
        return;
    }

    // Load product data
    try {
        const response = await fetch(`/api/products/${productId}`);
        const product = await response.json();

        if (response.ok) {
            // Display product information
            document.getElementById('productSku').textContent = product.sku;
            document.getElementById('productName').textContent = product.name;
            document.getElementById('productPrice').textContent = new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(product.price);
            document.getElementById('productTaxRate').textContent = `${product.tax_rate}%`;
            document.getElementById('productStock').textContent = product.stock;

            // Handle image
            const productImage = document.getElementById('productImage');
            if (product.image_url) {
                productImage.src = product.image_url;
            } else {
                productImage.src = '/frontend/assets/img/no-image.png';
                productImage.alt = 'No image available';
            }

            // Setup edit button
            document.getElementById('editProduct').addEventListener('click', () => {
                window.location.href = `create.php?id=${productId}`;
            });
        } else {
            showError(product.message || 'Failed to load product details');
        }
    } catch (err) {
        showError('Error loading product details: ' + err.message);
    }
});

function showError(message) {
    const container = document.querySelector('.container');
    container.innerHTML = `
        <div class="alert alert-danger mt-3">
            ${message}
            <br>
            <a href="list.php" class="btn btn-primary mt-2">Back to List</a>
        </div>
    `;
}