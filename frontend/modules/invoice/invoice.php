<?php
require_once __DIR__ . '/../../utils/api_client.php';
require_once __DIR__ . '/../../components/header.php';

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    header('Location: ../dashboard/index.php');
    exit;
}

$invoice_data = apiRequest("/invoice_gen/backend/public/api/invoice?id={$invoice_id}");
if (isset($invoice_data['error'])) {
    echo '<div class="alert alert-danger">Error loading invoice: ' . htmlspecialchars($invoice_data['error']) . '</div>';
    exit;
}

// Store the raw data for JavaScript
$invoice_json = json_encode($invoice_data);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo '<div class="alert alert-danger">Error processing invoice data</div>';
    exit;
}

?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Invoice View</h1>
                </div>
                <div class="col-sm-6">
                    <div class="float-right">
                        <div class="btn-group">
                            <button type="button" class="btn btn-primary" onclick="printInvoice()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button type="button" class="btn btn-success" onclick="downloadPDF()">
                                <i class="fas fa-download"></i> Download PDF
                            </button>
                            <button type="button" class="btn btn-info" onclick="emailInvoice()">
                                <i class="fas fa-envelope"></i> Email
                            </button>
                            <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#templateModal">
                                <i class="fas fa-edit"></i> Change Layout
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body" id="invoice-container">
                    <!-- Invoice template will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Template Selection Modal -->
<div class="modal fade" id="templateModal" tabindex="-1" role="dialog" aria-labelledby="templateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="templateModalLabel">Select Invoice Template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="template-options">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="template" id="template1" value="classic" checked>
                        <label class="form-check-label" for="template1">
                            Classic Template
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="template" id="template2" value="modern">
                        <label class="form-check-label" for="template2">
                            Modern Template
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="template" id="template3" value="minimal">
                        <label class="form-check-label" for="template3">
                            Minimal Template
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="changeTemplate()">Apply Template</button>
            </div>
        </div>
    </div>
</div>

<!-- Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1" role="dialog" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailModalLabel">Send Invoice</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="emailForm">
                    <div class="form-group">
                        <label for="emailTo">Email To:</label>
                        <input type="email" class="form-control" id="emailTo" 
                            value="<?php echo htmlspecialchars($invoice_data['customer']['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="emailSubject">Subject:</label>
                        <input type="text" class="form-control" id="emailSubject" 
                            value="Invoice #<?php echo htmlspecialchars($invoice_data['invoice_number'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="emailMessage">Message:</label>
                        <textarea class="form-control" id="emailMessage" rows="3">Please find attached the invoice for your recent purchase.</textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="sendEmail()">Send Email</button>
            </div>
        </div>
    </div>
</div>

<!-- Load required scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM ready');
    try {
        let invoiceData = <?php echo json_encode($invoice_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
        console.log('Invoice data:', invoiceData);

        let currentTemplate = 'classic';
        const templatePath = '<?php echo str_replace("\\", "/", __DIR__); ?>/templates';

        loadTemplate(currentTemplate);

    } catch (e) {
        console.error('Script initialization failed:', e);
    }
});

function loadTemplate(templateName) {
    const templateUrl = `${templatePath}/${templateName}.php`;
    console.log('Loading template:', templateUrl);

    fetch(templateUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(invoiceData)
    })
    .then(res => res.text())
    .then(html => {
        document.getElementById('invoice-container').innerHTML = html;
    })
    .catch(err => console.error('Template load failed:', err));
}

function showError(message) {
    document.getElementById('invoice-container').innerHTML = `
        <div class="alert alert-danger">
            <strong>Error:</strong> ${message}
        </div>
    `;
}

function changeTemplate() {
    const templateInput = document.querySelector('input[name="template"]:checked');
    if (!templateInput) {
        alert('Please select a template');
        return;
    }
    loadTemplate(templateInput.value);
    $('#templateModal').modal('hide');
}

function printInvoice() {
    window.print();
}

function downloadPDF() {
    const invoiceId = <?php echo $invoice_id; ?>;
    window.location.href = `download_pdf.php?id=${invoiceId}&template=${currentTemplate}`;
}

function emailInvoice() {
    $('#emailModal').modal('show');
}

function sendEmail() {
    const emailTo = document.getElementById('emailTo').value;
    if (!emailTo) {
        alert('Please enter an email address');
        return;
    }

    const emailData = {
        invoice_id: <?php echo json_encode($invoice_id); ?>,
        template: currentTemplate,
        to: emailTo,
        subject: document.getElementById('emailSubject').value,
        message: document.getElementById('emailMessage').value
    };

    const sendButton = document.querySelector('#emailModal .btn-primary');
    sendButton.disabled = true;
    sendButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

    fetch('send_email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(emailData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(result => {
        if (result.success) {
            alert('Email sent successfully!');
            $('#emailModal').modal('hide');
        } else {
            throw new Error(result.message || 'Failed to send email');
        }
    })
    .catch(error => {
        console.error('Error sending email:', error);
        alert(`Failed to send email: ${error.message}`);
    })
    .finally(() => {
        sendButton.disabled = false;
        sendButton.innerHTML = 'Send Email';
    });
}
</script>
