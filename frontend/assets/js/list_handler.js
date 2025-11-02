// Generic list handler with pagination and search
export class ListHandler {
    constructor(config) {
        this.config = {
            tableId: '',
            searchInputId: '',
            paginationContainerId: '',
            apiEndpoint: '',
            itemsPerPage: 10,
            searchDebounceMs: 300,
            columns: [],
            ...config
        };

        this.currentPage = 1;
        this.totalPages = 1;
        this.searchTerm = '';
        this.searchTimeout = null;
        
        // Bind methods to maintain context
        this.loadData = this.loadData.bind(this);
        this.renderData = this.renderData.bind(this);
        this.renderPagination = this.renderPagination.bind(this);
        this.goToPage = this.goToPage.bind(this);
        this.handleSearch = this.handleSearch.bind(this);
        this.handlePaginationClick = this.handlePaginationClick.bind(this);
        
        this.init();
    }

    init() {
        // Initialize table
        this.table = document.getElementById(this.config.tableId);
        this.tbody = this.table.querySelector('tbody');
        
        // Initialize search
        this.searchInput = document.getElementById(this.config.searchInputId);
        this.searchInput?.addEventListener('input', (e) => this.handleSearch(e));
        
        // Initialize pagination
        this.paginationContainer = document.getElementById(this.config.paginationContainerId);
        
        // Load initial data
        this.loadData();
    }
async loadData() {
    try {
        // Build query params (page, limit, search)
        const queryParams = new URLSearchParams({
            page: this.currentPage,
            limit: this.config.itemsPerPage,
            search: this.searchTerm
        });

        // Prepare payload for the PHP proxy
        const payload = {
            url: `${this.config.apiEndpoint}?${queryParams.toString()}`,
            method: 'GET',
            data: {}
        };

        // Call PHP proxy instead of backend directly
        const response = await fetch('utils/api_proxy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await response.json();
        console.log(data)
        if (data.error) {
            throw new Error(data.error);
        }

        // Render your table and pagination
        this.renderData(data.data);
        this.renderPagination(data.total, Math.ceil(data.total / this.config.itemsPerPage));
    } catch (error) {
        console.error('Error loading data:', error);
        this.showError(error.message);
    }
}


    renderData(items) {
        this.tbody.innerHTML = '';
        
        if (!items || items.length === 0) {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td colspan="${this.config.columns.length}">No records found</td>`;
            this.tbody.appendChild(tr);
            return;
        }

        items.forEach(item => {
            const tr = document.createElement('tr');
            
            this.config.columns.forEach(column => {
                const td = document.createElement('td');
                if (column.render) {
                    td.innerHTML = column.render(item[column.field], item);
                } else {
                    td.textContent = item[column.field] || '';
                }
                tr.appendChild(td);
            });

            this.tbody.appendChild(tr);
        });
    }

    renderPagination(total, totalPages) {
        this.totalPages = totalPages;
        if (!this.paginationContainer) return;

        let html = '<div class="pagination">';
        
        // Previous button
        html += `<button class="btn btn-sm page-nav" data-page="${this.currentPage - 1}" 
                        ${this.currentPage === 1 ? 'disabled' : ''}>
                    Previous
                </button>`;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= this.currentPage - 2 && i <= this.currentPage + 2)) {
                html += `<button class="btn btn-sm page-number ${i === this.currentPage ? 'active' : ''}" 
                                data-page="${i}">${i}</button>`;
            } else if (i === this.currentPage - 3 || i === this.currentPage + 3) {
                html += '<span>...</span>';
            }
        }

        // Next button
        html += `<button class="btn btn-sm page-nav" data-page="${this.currentPage + 1}"
                        ${this.currentPage === totalPages ? 'disabled' : ''}>
                    Next
                </button>`;

        html += `<span class="pagination-info">Showing ${(this.currentPage - 1) * this.config.itemsPerPage + 1} 
                to ${Math.min(this.currentPage * this.config.itemsPerPage, total)} 
                of ${total} entries</span>`;

        html += '</div>';
        
        this.paginationContainer.innerHTML = html;
        
        // Add event delegation for pagination
        this.paginationContainer.removeEventListener('click', this.handlePaginationClick);
        this.paginationContainer.addEventListener('click', this.handlePaginationClick);
    }

    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        this.currentPage = page;
        this.loadData();
    }

    handleSearch(event) {
        // Debounce search
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        this.searchTimeout = setTimeout(() => {
            this.searchTerm = event.target.value;
            this.currentPage = 1; // Reset to first page when searching
            this.loadData();
        }, this.config.searchDebounceMs);
    }

    showError(message) {
        this.tbody.innerHTML = `
            <tr>
                <td colspan="${this.config.columns.length}" class="text-danger">
                    Error: ${message}
                </td>
            </tr>
        `;
    }

    handlePaginationClick(event) {
        const button = event.target.closest('button');
        if (!button || button.disabled) return;
        
        const page = parseInt(button.dataset.page);
        if (!isNaN(page)) {
            this.goToPage(page);
        }
    }
}