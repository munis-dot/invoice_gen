// Logic: Initialize generic form handler for transactions
import { initializeFormHandler } from './form_handler.js';

initializeFormHandler({
    formId: 'transactionForm',
    uploadFormId: 'transactionBulkUploadForm',
    resultDivId: 'addTransactionResult',
    apiEndpoint: '/invoice_gen/backend/public/api/invoices/generate',
    apiEndpointBulk: 'invoice_gen/backend/public/api/invoices/batch',
    onSuccess: function(data) {
        if (data.error) {
            throw new Error(data.error);
        }
        alert('Invoice generated successfully!');
    }
});
