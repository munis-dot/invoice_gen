// Generic form handler for both manual submissions and file uploads
export function initializeFormHandler(config) {
    const {
        formId,
        uploadFormId,
        resultDivId,
        processorUrl,
        apiEndpoint,
        onSuccess,
        onError
    } = config;

    const form = document.getElementById(formId);
    const uploadForm = document.getElementById(uploadFormId);
    const resultDiv = document.getElementById(resultDivId);

    const showResult = (success, message) => {
        const alertClass = success ? 'alert-success' : 'alert-danger';
        resultDiv.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    };

    // Handle manual form submission
    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        resultDiv.textContent = 'Submitting...';

        try {
            const formData = Object.fromEntries(new FormData(form));
            const payload = {
                url: `${apiEndpoint}`,
                method: 'POST',
                data: formData
            }
            const response = await fetch('utils/api_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            console.log(result);

            if (result && !result.error) {
                showResult(true, 'Item added successfully!');
                form.reset();
                if (result) onSuccess(result);
            } else {
                showResult(false, result.error || 'Failed to add item.');
                if (onError) onError(result.error);
            }
        } catch (err) {
            showResult(false, `Error: ${err}`);
            if (onError) onError(err);
        }
    });

    // Handle file upload
    uploadForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        resultDiv.textContent = 'Uploading...';

        try {
            const formData = new FormData(uploadForm);
            const response = await fetch(processorUrl, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                showResult(true, result.message);
                uploadForm.reset();
                if (onSuccess) onSuccess(result);
            } else {
                showResult(false, result.message);
                if (onError) onError(result.message);
            }
        } catch (err) {
            showResult(false, `Error: ${err.message}`);
            if (onError) onError(err);
        }
    });
}