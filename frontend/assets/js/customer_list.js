import { ListHandler } from './list_handler.js';

// Initialize customer list handler
new ListHandler({
    tableId: 'customerTable',
    searchInputId: 'customerSearch',
    paginationContainerId: 'customerPagination',
    apiEndpoint: '/invoice_gen/backend/public/api/customers',
    itemsPerPage: 10,
    columns: [
        { field: 'id', title: 'ID' },
        { field: 'name', title: 'Name' },
        { field: 'email', title: 'Email' },
        { field: 'phone', title: 'Phone' },
        { field: 'address', title: 'Address' },
        {
            field: 'actions',
            title: 'Actions',
            render: (_, item) => `
                <button class="btn btn-sm btn-primary me-2 edit-btn" data-id="${item.id}">
                    Edit
                </button>
                <button class="btn btn-sm btn-danger delete-btn" data-id="${item.id}">
                    Delete
                </button>
            `
        }
    ]
});