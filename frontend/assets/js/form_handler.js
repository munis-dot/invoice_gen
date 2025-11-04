// Generic form handler for both manual submissions and file uploads
export function initializeFormHandler(config) {
    const {
        formId,
        uploadFormId,
        resultDivId,
        apiEndpoint,
        apiEndpointBulk,
        parseFileUrl = 'utils/parse_excel.php', // optional backend parser
        onSuccess,
        onError
    } = config;

    const form = document.getElementById(formId);
    const uploadForm = document.getElementById(uploadFormId);
    const resultDiv = document.getElementById(resultDivId);
    console.log(uploadForm)
    const showResult = (success, message) => {
        const alertClass = success ? 'alert-success' : 'alert-danger';
        resultDiv.innerHTML = `<div class="alert ${alertClass}">${message}</div>`;
    };

    // --- Manual form submission ---
    if (form) {
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            resultDiv.textContent = 'Submitting...';

            try {
                const formData = new FormData(form);
                const imageFile = formData.get('image_url') ?? formData.get('company_logo');
                const data = Object.fromEntries(formData);
                const isEdit = !!data.id; // Check if we have an ID (edit mode)
                // Handle image upload if present
                if (imageFile && imageFile?.size > 0) {
                    const timestamp = Date.now();
                    const fileName = `${timestamp}_${imageFile.name}`;
                    const imagePath = `assets/img/products/${fileName}`;
                    
                    // Create a folder if it doesn't exist
                    try {
                        await fetch('utils/create_folder.php', {
                            method: 'POST',
                            body: JSON.stringify({ path: 'assets/img/products' })
                        });
                    } catch (err) {
                        console.error('Error creating folder:', err);
                    }
                    
                    // Upload the image
                    const imageFormData = new FormData();
                    imageFormData.append('image', imageFile);
                    imageFormData.append('path', imagePath);
                    
                    const uploadResponse = await fetch('utils/upload_file.php', {
                        method: 'POST',
                        body: imageFormData
                    });
                    
                    if (!uploadResponse.ok) {
                        throw new Error('Failed to upload image');
                    }
                    console.log(imagePath)
                    // Replace file with path in data
                    data.image_url = imagePath;
                    data.company_logo = imagePath;
                } else if (!isEdit) {
                    // For new records without image
                    data.image_url = null;
                    data.company_logo = null;
                }
                // For edit mode without new image, keep existing image_url
                else if (isEdit && !imageFile?.size > 0) {
                    data.image_url = data.image;
                    data.company_logo = data.image;
                }
                delete data.image; // Remove the file object
                
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
                console.log(result)
                if (result && !result.error) {
                    const message = data.id ? 'Updated successfully!' : 'Added successfully!';
                    showResult(true, message);
                    if (!data.id) {
                        form.reset(); // Only reset form for new items
                    }
                    if (onSuccess) onSuccess(result, message ?? 'success');
                } else {
                    const action = data.id ? 'update' : 'add';
                    showResult(false, result.error || `Failed to ${action} item.`);
                    if (onError) onError(result.error);
                }
            } catch (err) {
                showResult(false, `Error: ${err}`);
                if (onError) onError(err);
            }
        });
    }

    // --- CSV/XLSX Upload ---
    if (uploadForm) {
        uploadForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            resultDiv.textContent = 'Uploading & processing...';

            try {
                const formData = new FormData(uploadForm);
                // Step 1: Parse file using backend helper (returns JSON data)
                const parseResponse = await fetch(parseFileUrl, {
                    method: 'POST',
                    body: formData
                });
                const parsed = await parseResponse.json();

                if (!parsed || !Array.isArray(parsed.items)) {
                    throw new Error('Invalid file or parsing error.');
                }

                // Step 2: Send parsed data to your API via api_proxy.php
                const payload = {
                    url: apiEndpointBulk,
                    method: 'POST',
                    data: parsed.items // your parsed rows
                };

                const apiResponse = await fetch('utils/api_proxy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await apiResponse.json();
                if (result && !result.error) {
                    showResult(true, 'Bulk upload successful!');
                    uploadForm.reset();
                    if (onSuccess) onSuccess(result);
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
