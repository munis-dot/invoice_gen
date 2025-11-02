// Logic: Initialize generic form handler for customers
import { initializeFormHandler } from '../../assets/js/form_handler.js';

initializeFormHandler({
    formId: 'customerForm',
    uploadFormId: 'uploadForm',
    resultDivId: 'addCustomerResult',
    processorUrl: '/frontend/modules/customers/customer_processor.php',
    apiEndpoint: '/invoice_gen/backend/public/api/customers',
    onSuccess: function(data) {
        if (data.success!=true) {
            throw new Error(data.error);
        }
        alert('Customer added successfully!');
    }
});