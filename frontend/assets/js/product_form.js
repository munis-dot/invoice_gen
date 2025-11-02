// Handle product form submission and image preview
console.log('Product form script loaded');
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded for product form');
    const form = document.getElementById('productForm');
    const imageInput = document.getElementById('image');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');
    const removeImageBtn = document.getElementById('removeImage');
    const formResult = document.getElementById('formResult');
    const productId = document.getElementById('productId')?.value;
    
    // Initialize Bootstrap validation
    // form.addEventListener('submit', function(event) {
    //     console.log(event)
    //     if (!form.checkValidity()) {
    //         event.preventDefault();
    //         event.stopPropagation();
    //     }
    //     form.classList.add('was-validated');
    // }, false);

    // Load product data if in edit mode
    if (productId) {
        loadProductData(productId);
    }

    // Validate image file
    function validateImage(file) {
        const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!validTypes.includes(file.type)) {
            return { valid: false, message: 'Please upload a JPEG, PNG, or GIF image.' };
        }

        if (file.size > maxSize) {
            return { valid: false, message: 'Image size must be less than 5MB.' };
        }

        return { valid: true };
    }

    // Handle image preview
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const validation = validateImage(file);
            if (!validation.valid) {
                imageInput.value = '';
                showResult(false, validation.message);
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreviewContainer.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    });

    // Handle image removal
    removeImageBtn?.addEventListener('click', function() {
        imageInput.value = '';
        imagePreview.src = '';
        imagePreviewContainer.classList.add('d-none');
        
        // Add hidden input to indicate image removal for edit mode
        if (productId) {
            const removeImageInput = document.createElement('input');
            removeImageInput.type = 'hidden';
            removeImageInput.name = 'remove_image';
            removeImageInput.value = '1';
            form.appendChild(removeImageInput);
        }
    });

    // Validate form data
    function validateFormData() {
        const name = form.name.value.trim();
        const sku = form.sku.value.trim();
        const price = parseFloat(form.price.value);
        const taxRate = parseFloat(form.tax_rate.value);
        const stock = parseInt(form.stock.value);

        // Name validation
        if (name.length < 2) {
            return { valid: false, message: 'Product name must be at least 2 characters long' };
        }

        // SKU validation
        if (!/^[A-Za-z0-9-_]{3,}$/.test(sku)) {
            return { valid: false, message: 'SKU must be at least 3 characters and contain only letters, numbers, hyphens, and underscores' };
        }

        // Price validation
        if (isNaN(price) || price < 0) {
            return { valid: false, message: 'Please enter a valid price' };
        }

        // Tax rate validation
        if (isNaN(taxRate) || taxRate < 0 || taxRate > 100) {
            return { valid: false, message: 'Tax rate must be between 0 and 100' };
        }

        // Stock validation
        if (isNaN(stock) || stock < 0) {
            return { valid: false, message: 'Stock must be a non-negative number' };
        }

        return { valid: true };
    }

    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        // Validate form data
        const validation = validateFormData();
        if (!validation.valid) {
            showResult(false, validation.message);
            return;
        }

        // Validate file if selected
        if (imageInput.files[0]) {
            const imageValidation = validateImage(imageInput.files[0]);
            if (!imageValidation.valid) {
                showResult(false, imageValidation.message);
                return;
            }
        }

        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        try {
            const response = await fetch('frontend/modules/products/product_processor.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showResult(true, result.message || 'Product saved successfully');
                if (!productId) {
                    form.reset();
                    imagePreviewContainer.classList.add('d-none');
                }
                // Use the loadPage function from app.js for navigation
                setTimeout(() => {
                    loadPage('products/list');
                }, 1500);
            } else {
                showResult(false, result.message || 'Failed to save product');
            }
        } catch (err) {
            showResult(false, 'Error: ' + err.message);
        } finally {
            submitButton.disabled = false;
        }
    });

    async function loadProductData(id) {
        try {
            const response = await fetch(`/api/products/${id}`);
            const product = await response.json();

            if (response.ok) {
                // Fill form fields
                form.name.value = product.name;
                form.sku.value = product.sku;
                form.price.value = product.price;
                form.tax_rate.value = product.tax_rate;
                form.stock.value = product.stock;

                // Show image preview if exists
                if (product.image_url) {
                    imagePreview.src = product.image_url;
                    imagePreviewContainer.classList.remove('d-none');
                }
            } else {
                showResult(false, product.message || 'Failed to load product data');
            }
        } catch (err) {
            showResult(false, 'Error loading product data: ' + err.message);
        }
    }

    function showResult(success, message) {
        const alertClass = success ? 'alert-success' : 'alert-danger';
        formResult.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    }
});