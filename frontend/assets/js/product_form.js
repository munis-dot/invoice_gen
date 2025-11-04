import { initializeFormHandler } from "./form_handler.js";

initializeFormHandler({
    formId: 'productForm',
    uploadFormId: 'productBulkUploadForm',
    resultDivId: 'addProductResult',
    apiEndpoint: 'invoice_gen/backend/public/api/products',
    apiEndpointBulk: 'invoice_gen/backend/public/api/products/batch',
    onSuccess: function(data, message) {
        if (data.success!=true && !data.message) {
            throw new Error(data.error);
        }
        alert(message);
        loadPage('products/list');
    }
});