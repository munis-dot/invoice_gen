// Logic: Initialize generic form handler for customers
import { initializeFormHandler } from '../../assets/js/form_handler.js';

initializeFormHandler({
    formId: 'customerForm',
    uploadFormId: 'customerBulkUploadForm',
    resultDivId: 'addCustomerResult',
    apiEndpoint: '/invoice_gen/backend/public/api/customers',
    apiEndpointBulk: '/invoice_gen/backend/public/api/customers/batch',
    onSuccess: function(data, message) {
        if (data.success!=true && !data.message) {
            throw new Error(data.error);
        }
        alert(message);
        loadPage('customers/customer_list');
    }
});