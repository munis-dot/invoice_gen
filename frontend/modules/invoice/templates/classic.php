<?php
require_once __DIR__ . '/../../../utils/api_client.php';

$invoice_id = $_GET['id'] ?? null;
if (!$invoice_id) {
    header('Location: ../dashboard/index.php');
    exit;
}

$invoice = apiRequest("/invoice_gen/backend/public/api/invoice?id={$invoice_id}");
if (isset($invoice_data['error'])) {
    echo '<div class="alert alert-danger">Error loading invoice: ' . htmlspecialchars($invoice_data['error']) . '</div>';
    exit;
}
$email = htmlspecialchars($invoice['customer']['email']);
$invoice_number = htmlspecialchars($invoice['invoice_number']);
?>
<div>
    <button onclick="downloadElementAsPDF('invoice-<?php echo date('Y-m-d'); ?>.pdf')">Download PDF</button>
    <button onclick="emailElementAsPDF('<?php echo $email; ?>', 'Invoice #<?php echo $invoice_number; ?>')">Email PDF</button>
    <button onclick="printElement()">Print</button>
</div>
<div class="invoice-template classic">
    <div class="invoice-header">
            <div class="company-logo">
                <img src="<?php echo htmlspecialchars("assets/img/Depositphotos_13687440_s-2019.jpg"); ?>" alt="Company Logo">
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
            <p>ABC Company</p>
            <p>123 Main St, City, Sivakasi</p>
            <p>Phone: 123-456-7890</p>
            <p>Email: info@abccompany.com</p>
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
</div>

