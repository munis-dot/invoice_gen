import { ListHandler } from './list_handler.js';

// Initialize transaction list handler
new ListHandler({
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
            render: (value) => value.charAt(0).toUpperCase() + value.slice(1)
        },
        {
            field: 'actions',
            title: 'Actions',
            render: (_, item) => `
                <button data-link="invoice/templates/classic?id=${item.id}" class="btn btn-info btn-sm me-2">View</button>
                <button class="btn btn-sm btn-danger" onclick="deleteTransaction(${item.id})">Delete</button>
            `
        }
    ]
});
