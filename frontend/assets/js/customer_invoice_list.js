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
            apiEndpoint: '/invoice_gen/backend/public/api/invoices/customer?customerId=' + getUrlParameter('id'),
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
                        <div class="btn-group">
                            <button data-link="transactions/transaction_view?id=${item.id}" class="btn btn-info btn-sm">View</button>
                        </div>
                    `
                }
            ]
        });

    }

}