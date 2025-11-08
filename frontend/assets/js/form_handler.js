export function initializeFormHandler(config) {
    const {
        formId,
        uploadFormId,
        resultDivId,
        apiEndpoint,
        apiEndpointBulk,
        parseFileUrl = 'utils/parse_excel.php',
        onSuccess,
        onError
    } = config;

    const form       = document.getElementById(formId);
    const uploadForm = document.getElementById(uploadFormId);
    const resultDiv  = document.getElementById(resultDivId);

    const showResult = (success, message) => {
        const alertClass = success ? 'alert-success' : 'alert-danger';
        resultDiv.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    };

    // --------------------------------------------------------------
    // Helper: get the *lowest* product price via api_proxy.php
    // --------------------------------------------------------------
    const getMinProductPrice = async () => {
        const payload = {
            action: 'GET_MIN_PRICE'          // <-- our special flag
        };
        const resp = await fetch('utils/api_proxy.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        return Number(data.min_price) || 0;
    };

    // --------------------------------------------------------------
    // --- Manual form submission ---
    // --------------------------------------------------------------
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            resultDiv.textContent = 'Submitting...';

            try {
                const formData   = new FormData(form);
                const imageFile  = formData.get('image_url') ?? formData.get('company_logo');
                const data       = Object.fromEntries(formData);
                const isEdit     = !!data.id;

                // ---------- IMAGE HANDLING (unchanged) ----------
                if (imageFile && imageFile?.size > 0) {
                    const timestamp = Date.now();
                    const fileName  = `${timestamp}_${imageFile.name}`;
                    const imagePath = `assets/img/products/${fileName}`;

                    await fetch('utils/create_folder.php', {
                        method: 'POST',
                        body: JSON.stringify({ path: 'assets/img/products' })
                    }).catch(() => {});

                    const imgForm = new FormData();
                    imgForm.append('image', imageFile);
                    imgForm.append('path', imagePath);

                    const up = await fetch('utils/upload_file.php', { method: 'POST', body: imgForm });
                    if (!up.ok) throw new Error('Image upload failed');

                    data.image_url = data.company_logo = imagePath;
                } else if (!isEdit) {
                    data.image_url = data.company_logo = null;
                } else if (isEdit && !imageFile?.size > 0) {
                    data.image_url = data.company_logo = data.image;
                }
                delete data.image;

                // ---------- AMOUNT VALIDATION ----------
                const submittedAmount = Number(data.amount) || 0;
                const minProductPrice  = await getMinProductPrice();

                if (submittedAmount > 0 && submittedAmount < minProductPrice) {
                    // **Your exact rule**
                    alert('Unable to generate invoice: Transaction amount is too low.');
                    showResult(false, 'Transaction amount is too low.');
                    if (onError) onError('amount_too_low');
                    return;                 // abort the real API call
                }

                // ---------- SEND TO API ----------
                const payload = {
                    url: isEdit ? `${apiEndpoint}?id=${data.id}` : apiEndpoint,
                    method: isEdit ? 'PUT' : 'POST',
                    data: data
                };

                const response = await fetch('utils/api_proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result && !result.error) {
                    const msg = data.id ? 'Updated successfully!' : 'Added successfully!';
                    showResult(true, msg);
                    if (!data.id) form.reset();
                    if (onSuccess) onSuccess(result, msg);
                } else {
                    const err = result.error || `Failed to ${data.id ? 'update' : 'add'} item.`;
                    showResult(false, err);
                    if (onError) onError(result.error);
                }
            } catch (err) {
                showResult(false, `Error: ${err.message}`);
                if (onError) onError(err);
            }
        });
    }

    // --------------------------------------------------------------
    // --- CSV/XLSX Upload (unchanged) ---
    // --------------------------------------------------------------
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            resultDiv.textContent = 'Uploading & processing...';

            try {
                const formData = new FormData(uploadForm);
                const parseResp = await fetch(parseFileUrl, { method: 'POST', body: formData });
                const parsed = await parseResp.json();

                if (!parsed || !Array.isArray(parsed.items)) {
                    throw new Error('Invalid file or parsing error.');
                }

                const payload = { url: apiEndpointBulk, method: 'POST', data: parsed.items };
                const apiResp = await fetch('utils/api_proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await apiResp.json();
                if (result && !result.error) {
                    showResult(true, 'Bulk upload successful!');
                    uploadForm.reset();
                    if (onSuccess) onSuccess(result, 'Bulk upload successful!');
                } else {
                    showResult(false, result.error || 'Bulk upload failed.');
                    if (onError) onError(result.error);
                }
            } catch (err) {
                showResult(false, `Error: ${err.message}`);
                if (onError) onError(err);
            }
        });
    }
}