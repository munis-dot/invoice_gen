<?php
require_once __DIR__ . '/../../../utils/api_client.php';

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    // header('Location: ../dashboard/index.php');
    exit;
}

$invoice = apiRequest("/invoice_gen/backend/public/api/invoice?id={$invoice_id}");
if (isset($invoice_data['error'])) {
    echo '<div class="alert alert-danger">Error loading invoice: ' . htmlspecialchars($invoice_data['error']) . '</div>';
    exit;
}
$email = htmlspecialchars($invoice['customer']['email']);
$invoice_number = htmlspecialchars($invoice['invoice_number']);
// echo json_encode($invoice);
?>
 <div class="view-footer">
            <div class="quick-actions">
                <button  onclick="printElement()"  class="quick-btn print-btn">
                    <i class="fas fa-print"></i>
                    Print
                </button>
                <button onclick="emailElementAsPDF('<?php echo $email; ?>', 'Invoice #<?php echo $invoice_number; ?>')" class="quick-btn share-btn">
                    <i class="fas fa-share-alt"></i>
                    Email
                </button>
                <button onclick="downloadElementAsPDF('invoice-<?php echo date('Y-m-d'); ?>.pdf')"class="quick-btn export-btn">
                    <i class="fas fa-download"></i>
                    Export
                </button>
            </div>
        </div>

<!-- <div class="invoice-template classic">
    <div class="invoice-header">
            <div class="company-logo">
                <img src="<?php echo htmlspecialchars($invoice['company_logo']); ?>" alt="Company Logo">
            </div>
        <div class="invoice-info">
            <h2>INVOICE</h2>
            <div class="invoice-number">
                #<?php echo htmlspecialchars($invoice['invoice_number']); ?>
            </div>
            <div class="invoice-date">
                Date: <?php echo htmlspecialchars($invoice['date']); ?>
            </div>
        </div>
    </div>

    <div class="invoice-addresses">
        <div class="from-address">
            <h4>From</h4>
            <p><?php echo htmlspecialchars($invoice['address']); ?></p>
            <p>Email: <?php echo htmlspecialchars($invoice['email']); ?></p>
        </div>
        <div class="to-address">
            <h4>Bill To</h4>
            <p><?php echo htmlspecialchars($invoice['customer']['name']); ?></p>
            <p><?php echo htmlspecialchars($invoice['customer']['address']); ?></p>
            <p>Phone: <?php echo htmlspecialchars($invoice['customer']['phone']); ?></p>
            <p>Email: <?php echo htmlspecialchars($invoice['customer']['email']); ?></p>
        </div>
    </div>

    <div class="invoice-items">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice['items'] as $index => $item): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($item['product']['name']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td><?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="invoice-summary">
        <table class="summary-table">
            <tr>
                <td>Subtotal:</td>
                <td><?php echo number_format($invoice['subtotal'], 2); ?></td>
            </tr>
            <?php if ($invoice['tax'] > 0): ?>
            <tr>
                <td>Tax:</td>
                <td><?php echo number_format($invoice['tax'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($invoice['discount'] > 0): ?>
            <tr>
                <td>Discount:</td>
                <td>-<?php echo number_format($invoice['discount'], 2); ?></td>
            </tr>
            <?php endif; ?>
            <tr class="total-row">
                <td>Total:</td>
                <td><?php echo number_format($invoice['total'], 2); ?></td>
            </tr>
        </table>
    </div>

    <div class="invoice-footer">
        <p>Thank you for your business!</p>
    </div>
</div> -->
<html>
    <head>
        <link rel="stylesheet" href="assets/css/invoice_builder.css" />
        
    </head>
<div class="container">
        <header>
            <div class="mode-toggle">
                <span>Mode:</span>
                <button id="edit-mode-btn" class="toggle-btn active">Edit</button>
                <button id="preview-mode-btn" class="toggle-btn">Preview</button>
            </div>
        </header>
        
        
        
        <div class="panel invoice-preview" id="invoice-preview">
            <!-- <h2>Invoice Preview</h2> -->
            <!-- Elements will be added here dynamically -->
        </div>
        
        <div class="panel elements-panel">
            <h2>Invoice Elements</h2>
            <div class="element-item" data-type="header">Header</div>
            <div class="element-item" data-type="invoice-details">Invoice Details</div>
            <div class="element-item" data-type="from-address">From Address</div>
            <div class="element-item" data-type="to-address">To Address</div>
            <div class="element-item" data-type="items-table">Items Table</div>
            <div class="element-item" data-type="company-logo">Company Logo</div>
            <div class="element-item" data-type="total-summary">Total Summary</div>
            <div class="element-item" data-type="footer">Footer</div>
            
            <div class="position-options">
                <h3>Position Options</h3>
                <div class="position-grid">
                    <div class="position-btn" data-position="top-left">Top Left</div>
                    <div class="position-btn" data-position="top-center">Top Center</div>
                    <div class="position-btn" data-position="top-right">Top Right</div>
                    <div class="position-btn" data-position="middle-left">Middle Left</div>
                    <div class="position-btn" data-position="middle-center">Middle Center</div>
                    <div class="position-btn" data-position="middle-right">Middle Right</div>
                    <div class="position-btn" data-position="bottom-left">Bottom Left</div>
                    <div class="position-btn" data-position="bottom-center">Bottom Center</div>
                    <div class="position-btn" data-position="bottom-right">Bottom Right</div>
                </div>
            </div>
        </div>
    </div>
  <script src="assets/js/invoice_builder.js"></script>
