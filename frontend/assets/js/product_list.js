import { ListHandler } from './list_handler.js';

// Initialize product list handler
new ListHandler({
    tableId: 'productTable',
    searchInputId: 'productSearch',
    paginationContainerId: 'productPagination',
    apiEndpoint: '/invoice_gen/backend/public/api/products',
    itemsPerPage: 10,
    columns: [
        { field: 'id', title: 'ID' },
        { 
            field: 'image_url', 
            title: 'Image',
            render: (value) => value ? 
                `<img src="${value}" alt="Product" class="img-thumbnail" style="max-height: 50px;">` :
                '<span class="text-muted">No image</span>'
        },
        { field: 'sku', title: 'SKU' },
        { field: 'name', title: 'Name' },
        { 
            field: 'price', 
            title: 'Price',
            render: (value) => new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(value)
        },
        { 
            field: 'tax_rate', 
            title: 'Tax Rate',
            render: (value) => `${value}%`
        },
        { 
            field: 'stock',
            title: 'Stock',
            render: (value) => `<span class="badge ${value > 0 ? 'bg-success' : 'bg-danger'}">${value}</span>`
        },
        {
            field: 'actions',
            title: 'Actions',
            render: (_, item) => `
                <div class="btn-group">
                    <a href="view.php?id=${item.id}" class="btn btn-info btn-sm">View</a>
                    <a href="create.php?id=${item.id}" class="btn btn-primary btn-sm">Edit</a>
                    <button class="btn btn-danger btn-sm" onclick="deleteProduct(${item.id})">Delete</button>
                </div>
            `
        }
    ]
});

// Delete product function
window.deleteProduct = async function(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }
    
    try {
        const response = await fetch(`/api/products/${id}`, {
            method: 'DELETE'
        });
        
        if (response.ok) {
            // Refresh the list
            window.location.reload();
        } else {
            const error = await response.json();
            alert(error.message || 'Failed to delete product');
        }
    } catch (err) {
        alert('Error deleting product: ' + err.message);
    }
};