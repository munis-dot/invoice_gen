// Logic: Initialize generic form handler for transactions
import { initializeFormHandler } from './form_handler.js';

initializeFormHandler({
    formId: 'transactionAddForm',
    uploadFormId: 'uploadForm',
    resultDivId: 'addTransactionResult',
    processorUrl: '/invoice_gen/frontend/modules/transactions/transaction_processor.php',
    apiEndpoint: '/invoice_gen/backend/public/api/invoices/generate',
    onSuccess: function(data) {
        if (data.error) {
            throw new Error(data.error);
        }
        alert('Invoice generated successfully!');
    }
});
