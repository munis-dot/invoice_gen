// Logic: Fetch and Render Transaction Details
import { apiRequest } from '../../utils/api_client.js';
function getQueryParam(name) {
    const url = new URL(window.location.href);
    return url.searchParams.get(name);
}
document.addEventListener('DOMContentLoaded', async function() {
    const id = getQueryParam('id');
    const detailsDiv = document.getElementById('transactionDetails');
    detailsDiv.textContent = 'Loading...';
    try {
        const response = await apiRequest(`/api/transactions/${id}`, 'GET');
        if (response && !response.error) {
            detailsDiv.innerHTML = `<ul>
                <li><strong>ID:</strong> ${response.id}</li>
                <li><strong>Invoice Number:</strong> ${response.invoice_number}</li>
                <li><strong>Customer:</strong> ${response.customer ? response.customer.name : ''}</li>
                <li><strong>Date:</strong> ${response.date}</li>
                <li><strong>Amount:</strong> ${response.amount}</li>
                <li><strong>Payment Method:</strong> ${response.payment_method}</li>
            </ul>`;
        } else {
            detailsDiv.textContent = response.error || 'Transaction not found.';
        }
    } catch (err) {
        detailsDiv.textContent = 'Error: ' + err;
    }
});
