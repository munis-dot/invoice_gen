export default class CustomerList {
    constructor() {
       initUI();
    }


}
document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
        initUI()
    }, 2000)
    const container = document.getElementsByClassName('container');
    console.log(container)
});

function initUI() {
    const invoicePreview = document.getElementById('invoice-preview');
    const elementItems = document.querySelectorAll('.element-item');
    const positionBtns = document.querySelectorAll('.position-btn');
    // const exportBtn = document.getElementById('export-btn');
    const editModeBtn = document.getElementById('edit-mode-btn');
    const previewModeBtn = document.getElementById('preview-mode-btn');
    let activeElement = null;
    let isDragging = false;
    let dragOffset = { x: 0, y: 0 };
    let isEditMode = false;
    let invoiceData = null; // To store fetched invoice data

    // Default positions for elements
    // const defaultPositions = {
    //     'header': { top: 20, left: 300, width: 400 },
    //     'invoice-details': { top: 80, left: 700, width: 250 },
    //     'from-address': { top: 200, left: 50, width: 250 },
    //     'to-address': { top: 200, left: 700, width: 250 },
    //     'items-table': { top: 550, left: 50, width: 900 },
    //     'company-logo': { top: 80, left: 50, width: 150 },
    //     'total-summary': { top: 600, left: 650, width: 200 },
    //     'footer': { top: 650, left: 200, width: 400 }
    // };

    const defaultPositions = {
    'company-logo': { top: 40, left: 50, width: 120, height: 60 },
    'header': { top: 40, left: 200, width: 400, height: 80 },
    'invoice-details': { top: 40, left: 650, width: 300, height: 120 },
    'from-address': { top: 150, left: 50, width: 280, height: 140 },
    'to-address': { top: 150, left: 650, width: 280, height: 140 },
    'items-table': { top: 320, left: 50, width: 800, height: 200 },
    'total-summary': { top: 540, left: 650, width: 200, height: 120 },
    'payment-info': { top: 540, left: 450, width: 180, height: 120 },
    'footer': { top: 680, left: 50, width: 800, height: 60 },
    'notes': { top: 580, left: 50, width: 380, height: 80 }
};

    // Function to get URL param
    function getUrlParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    // Function to fetch invoice data
    async function fetchInvoiceData() {
        const invoice_id = getUrlParam('id');
        if (!invoice_id) {
            console.error('Invoice ID not found in URL params');
            return;
        }

        const payload = {
            url: `/invoice_gen/backend/public/api/invoice?id=${invoice_id}`,
            method: "GET",
            data: null
        };

        try {
            const resp = await fetch('utils/api_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();
            if (data && !data.error) { // Assuming API returns error on failure
                invoiceData = data;
                console.log('Invoice data loaded:', invoiceData);
            } else {
                console.error('Failed to load invoice data:', data);
            }
        } catch (error) {
            console.error('Error fetching invoice:', error);
        }
    }

    // Initialize with default layout after fetching data
    async function initializeInvoice() {
        await fetchInvoiceData(); // Fetch data first

        // Clear the invoice preview
        if (invoicePreview) {

            // Add all elements with default positions
            Object.keys(defaultPositions).forEach(type => {
                addElementToInvoice(type, defaultPositions[type].left, defaultPositions[type].top, defaultPositions[type].width);
            });

            updateSidebarItems();
        }
    }

    setTimeout(() => {
        initializeInvoice();
    }, 1000);

    // Double-click to add elements from sidebar
    elementItems.forEach(item => {
        item.addEventListener('dblclick', function () {
            const elementType = item.dataset.type;
            addElementToInvoice(elementType, 50, 50);
            updateSidebarItems();
        });
    });

    // Position buttons functionality
    positionBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            if (!activeElement || !isEditMode) return;

            // Remove active class from all buttons
            positionBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            btn.classList.add('active');

            const position = btn.dataset.position;
            positionElement(activeElement, position);
        });
    });


    // Mode toggle functionality
    editModeBtn.addEventListener('click', function () {
        if (isEditMode) return;
        setEditMode(true);
    });

    previewModeBtn.addEventListener('click', function () {
        if (!isEditMode) return;
        setEditMode(false);
    });

    function setEditMode(edit) {
        isEditMode = edit;

        if (edit) {
            document.body.classList.remove('preview-mode');
            editModeBtn.classList.add('active');
            previewModeBtn.classList.remove('active');

            // Make all elements draggable again
            document.querySelectorAll('.invoice-element').forEach(element => {
                makeElementDraggable(element);
            });
        } else {
            document.body.classList.add('preview-mode');
            editModeBtn.classList.remove('active');
            previewModeBtn.classList.add('active');

            // Remove dragging capability
            document.querySelectorAll('.invoice-element').forEach(element => {
                element.style.cursor = 'default';
                element.removeEventListener('mousedown', element.dragHandler);
            });

            // Remove active element
            if (activeElement) {
                activeElement.classList.remove('active');
                activeElement = null;
            }
        }
    }

    function updateSidebarItems() {
        // Get all existing element types in the invoice
        const existingElements = Array.from(document.querySelectorAll('.invoice-element')).map(el => el.dataset.type);

        // Update sidebar items
        elementItems.forEach(item => {
            const type = item.dataset.type;
            if (existingElements.includes(type)) {
                item.classList.add('disabled');
            } else {
                item.classList.remove('disabled');
            }
        });
    }

    function addElementToInvoice(type, left, top, width = 200) {
        // Check if element already exists
        if (document.querySelector(`.invoice-element[data-type="${type}"]`)) {
            return;
        }

        const element = document.createElement('div');
        element.className = 'invoice-element';
        element.dataset.type = type;
        element.style.left = left + 'px';
        element.style.top = top + 'px';
        element.style.width = width + 'px';

        // Add controls
        const controls = document.createElement('div');
        controls.className = 'element-controls';
        controls.innerHTML = `
                    <div class="control-btn delete-btn">Delete</div>
                `;
        element.appendChild(controls);

        // Add content based on type, using invoiceData if available
        let content = '';
        switch (type) {
            case 'header':
                content = `<div class="invoice-header">${'INVOICE'}</div>`;
                break;
            case 'invoice-details':
                const dueDate = invoiceData ? new Date(invoiceData.date).toLocaleDateString() : new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toLocaleDateString();
                content = `
                            <div class="invoice-details">
                                <p><strong>Invoice #:</strong> ${invoiceData ? invoiceData.invoice_number : 'INV-001'}</p>
                                <p><strong>Date:</strong> ${invoiceData ? new Date(invoiceData.date).toLocaleDateString() : new Date().toLocaleDateString()}</p>
                            </div>
                        `;
                break;
            case 'from-address':
                content = `
                            <div class="from-address">
                                <h3>From:</h3>
                                <p>${invoiceData ? invoiceData.address || 'Your Company Name' : 'Your Company Name'}</p>
                                <p>${invoiceData ? invoiceData.email || 'info@company.com' : 'info@company.com'}</p>
                                <p>Phone: (123) 456-7890</p> <!-- Add phone if available in data -->
                            </div>
                        `;
                break;
            case 'to-address':
                const customer = invoiceData ? invoiceData.customer : null;
                content = `
                            <div class="to-address">
                                <h3>To:</h3>
                                <p>${customer ? customer.name : 'Client Company Name'}</p>
                                <p>${customer ? customer.address : '456 Client Avenue'}</p>
                                <p>Email: ${customer ? customer.email : 'contact@client.com'}</p>
                                <p>Phone: ${customer ? customer.phone : '(987) 654-3210'}</p>
                            </div>
                        `;
                break;
            case 'items-table':
                let tableRows = '';
                if (invoiceData && invoiceData.items) {
                    invoiceData.items.forEach(item => {
                        tableRows += `
                            <tr>
                                <td>${item.product.name}</td>
                                <td>${item.quantity}</td>
                                <td>$${item.price}</td>
                                <td>$${item.total}</td>
                            </tr>
                        `;
                    });
                } else {
                    // Fallback rows
                    tableRows = `
                        <tr><td>Product 1</td><td>2</td><td>$50.00</td><td>$100.00</td></tr>
                        <tr><td>Product 2</td><td>1</td><td>$75.00</td><td>$75.00</td></tr>
                        <tr><td>Service 1</td><td>3</td><td>$30.00</td><td>$90.00</td></tr>
                    `;
                }
                content = `
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th>Description</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${tableRows}
                                </tbody>
                            </table>
                        `;
                break;
            case 'company-logo':
                const logoSrc = invoiceData ? invoiceData.company_logo : null;
                content = `
                            <div class="company-logo">
                                ${logoSrc ? `<img src="${logoSrc}" alt="Company Logo" style="width: 100%; height: auto;">` : '<div class="logo-placeholder">Company Logo</div>'}
                            </div>
                        `;
                break;
            case 'total-summary':
                content = `
                            <div class="total-summary">
                                <p>Subtotal: $${invoiceData ? invoiceData.subtotal : '265.00'}</p>
                                <p>Tax: $${invoiceData ? invoiceData.tax : '26.50'}</p>
                                ${invoiceData?.['discount'] > 0 ? '<p>Discount: $' + invoiceData.discount + '</p>' : ''}
                                <p>Total: $${invoiceData ? invoiceData.total : '291.50'}</p>
                            </div>
                        `;
                break;
            case 'footer':
                content = `
                            <div class="footer">
                                <p>Thank you for your business!</p>
                                <p>Terms & Conditions: Payment due within 30 days</p>
                            </div>
                        `;
                break;
        }

        element.innerHTML += content;
        invoicePreview.appendChild(element);

        // Add event listeners for the new element
        makeElementDraggable(element);

        // Add delete functionality
        const deleteBtn = element.querySelector('.delete-btn');
        deleteBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            element.remove();
            updateSidebarItems();
        });

        // Update sidebar items
        updateSidebarItems();
    }

    function makeElementDraggable(element) {
        // Store the handler so we can remove it later
        element.dragHandler = function (e) {
            if (e.target.classList.contains('control-btn') || !isEditMode) return;

            // Set as active element
            setActiveElement(element);

            isDragging = true;
            dragOffset.x = e.clientX - element.getBoundingClientRect().left;
            dragOffset.y = e.clientY - element.getBoundingClientRect().top;

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
        };

        element.addEventListener('mousedown', element.dragHandler);

        element.addEventListener('click', function (e) {
            if (e.target.classList.contains('control-btn') || !isEditMode) return;
            setActiveElement(element);
        });
    }

    function setActiveElement(element) {
        if (!isEditMode) return;

        // Remove active class from all elements
        document.querySelectorAll('.invoice-element').forEach(el => {
            el.classList.remove('active');
        });

        // Add active class to clicked element
        element.classList.add('active');
        activeElement = element;
    }

    function onMouseMove(e) {
        if (!isDragging || !activeElement || !isEditMode) return;

        const previewRect = invoicePreview.getBoundingClientRect();
        let newLeft = e.clientX - previewRect.left - dragOffset.x;
        let newTop = e.clientY - previewRect.top - dragOffset.y;

        // Constrain to preview area
        newLeft = Math.max(0, Math.min(newLeft, previewRect.width - activeElement.offsetWidth));
        newTop = Math.max(0, Math.min(newTop, previewRect.height - activeElement.offsetHeight));

        activeElement.style.left = newLeft + 'px';
        activeElement.style.top = newTop + 'px';
    }

    function onMouseUp() {
        isDragging = false;
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    function positionElement(element, position) {
        const previewRect = invoicePreview.getBoundingClientRect();
        const elementWidth = element.offsetWidth;
        const elementHeight = element.offsetHeight;

        let newLeft, newTop;

        switch (position) {
            case 'top-left':
                newLeft = 20;
                newTop = 20;
                break;
            case 'top-center':
                newLeft = (previewRect.width - elementWidth) / 2;
                newTop = 20;
                break;
            case 'top-right':
                newLeft = previewRect.width - elementWidth - 20;
                newTop = 20;
                break;
            case 'middle-left':
                newLeft = 20;
                newTop = (previewRect.height - elementHeight) / 2;
                break;
            case 'middle-center':
                newLeft = (previewRect.width - elementWidth) / 2;
                newTop = (previewRect.height - elementHeight) / 2;
                break;
            case 'middle-right':
                newLeft = previewRect.width - elementWidth - 20;
                newTop = (previewRect.height - elementHeight) / 2;
                break;
            case 'bottom-left':
                newLeft = 20;
                newTop = previewRect.height - elementHeight - 20;
                break;
            case 'bottom-center':
                newLeft = (previewRect.width - elementWidth) / 2;
                newTop = previewRect.height - elementHeight - 20;
                break;
            case 'bottom-right':
                newLeft = previewRect.width - elementWidth - 20;
                newTop = previewRect.height - elementHeight - 20;
                break;
        }

        element.style.left = newLeft + 'px';
        element.style.top = newTop + 'px';
    }
    setEditMode(false);

}