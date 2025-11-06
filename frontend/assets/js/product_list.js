import { ListHandler } from './list_handler.js';

export default class ProductList {
    constructor() {
        this.init();
    }

    init() {
        // Initialize product list handler
        this.listHandler = new ListHandler({
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
        <div class="action-buttons">
            <button onclick="loadPage('products/view', { id: ${item.id} })" class="btn-action btn-view" title="View Product">
                <i class="fas fa-eye"></i>
            </button>
            <button onclick="loadPage('products/create', { id: ${item.id} })" class="btn-action btn-edit" title="Edit Product">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn-action btn-delete" onclick="deleteProduct(${item.id})" title="Delete Product">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `
                }
            ]
        });

        // Set up delete product handler
        window.deleteProduct = this.deleteProduct.bind(this);
    }

    async deleteProduct(id) {
        if (!confirm('Are you sure you want to delete this product?')) {
            return;
        }

        try {
            const payload = {
                url: `/invoice_gen/backend/public/api/products?id=${id}`,
                method: 'DELETE',
                data: null
            };

            const response = await fetch('utils/api_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });

            const result = await response.json();
            if (result && !result.error) {
                // Refresh the list using the list handler instead of page reload
                this.listHandler.loadData();
            } else {
                alert(result.error || 'Failed to delete product');
            }
        } catch (err) {
            alert('Error deleting product: ' + err.message);
        }
    }
}