import { ListHandler } from './list_handler.js';

export default class CustomerList {
    constructor() {
        this.init();
    }

    init() {
        // Initialize customer list handler
        this.listHandler = new ListHandler({
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
                       <div class="btn-group">
                            <button onclick="loadPage('customers/customer_view', { id: ${item.id} })" class="btn btn-info btn-sm">View</button>
                            <button onclick="loadPage('customers/customer_add', { id: ${item.id} })" class="btn btn-primary btn-sm">Edit</button>
                            <button class="btn btn-danger btn-sm" onclick="deleteCustomer(${item.id})">Delete</button>
                        </div>
                    `
                }
            ]
        });

        // Set up delete customer handler
        window.deleteCustomer = this.deleteCustomer.bind(this);
    }

    async deleteCustomer(id) {
        if (!confirm('Are you sure you want to delete this customer?')) {
            return;
        }
        
        try {
            const payload = {
                url: `/invoice_gen/backend/public/api/customers?id=${id}`,
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
                alert(result.error || 'Failed to delete customer');
            }
        } catch (err) {
            alert('Error deleting customer: ' + err.message);
        }
    }
}