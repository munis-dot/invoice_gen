import { ListHandler } from './list_handler.js';

export default class TransactionList {
    constructor() {
        this.init();
    }

    init() {
        // Initialize transaction list handler
        this.listHandler = new ListHandler({
            tableId: 'transactionTable',
            searchInputId: 'transactionSearch',
            paginationContainerId: 'transactionPagination',
            apiEndpoint: '/invoice_gen/backend/public/api/invoices',
            itemsPerPage: 10,
            columns: [
                { field: 'id', title: 'ID' },
                { field: 'invoice_number', title: 'Invoice Number' },
                { 
                    field: 'customer', 
                    title: 'Customer',
                    render: (_, item) => item.customer_name ? item.customer_name : 'N/A'
                },
                { 
                    field: 'date', 
                    title: 'Date',
                    render: (value) => new Date(value).toLocaleDateString()
                },
                { 
                    field: 'total', 
                    title: 'Amount',
                    render: (value) => new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD'
                    }).format(value)
                },
                { 
                    field: 'payment_method', 
                    title: 'Payment Method',
                    render: (value) => value ? value.charAt(0).toUpperCase() + value.slice(1) : 'N/A'
                },
                {
    field: 'actions',
    title: 'Actions',
    render: (_, item) => `
        <div class="action-buttons">
            <button data-link="transactions/transaction_view?id=${item.id}" class="btn-action btn-view" title="View Transaction">
                <i class="fas fa-eye"></i>
            </button>
            <button class="btn-action btn-delete" onclick="deleteTransaction(${item.id})" title="Delete Transaction">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `
}
            ]
        });

        // Set up delete transaction handler
        window.deleteTransaction = this.deleteTransaction.bind(this);
    }

    async deleteTransaction(id) {
        if (!confirm('Are you sure you want to delete this invoice?')) {
            return;
        }
        
        try {
            const payload = {
                url: `/invoice_gen/backend/public/api/invoices?id=${id}`,
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
                // Refresh the list using the list handler
                this.listHandler.loadData();
            } else {
                alert(result.error || 'Failed to delete invoice');
            }
        } catch (err) {
            alert('Error deleting invoice: ' + err.message);
        }
    }
}