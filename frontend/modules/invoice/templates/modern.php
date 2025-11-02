<?php
$invoice = json_decode(file_get_contents('php://input'), true);
?>
<div class="invoice-template modern">
    <div class="invoice-header">
        <div class="row align-items-center">
            <div class="col-6">
                <?php if ($invoice['companyLogoUrl']): ?>
                    <div class="company-logo">
                        <img src="<?php echo htmlspecialchars($invoice['companyLogoUrl']); ?>" alt="Company Logo">
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-6 text-right">
                <h1 class="invoice-title">INVOICE</h1>
                <div class="invoice-number">#<?php echo htmlspecialchars($invoice['invoice_number']); ?></div>
            </div>
        </div>
    </div>

    <div class="invoice-meta">
        <div class="row">
            <div class="col-6">
                <div class="issued-date">
                    <span class="label">Issue Date:</span>
                    <span class="value"><?php echo htmlspecialchars($invoice['date']); ?></span>
                </div>
            </div>
            <div class="col-6 text-right">
                <div class="due-date">
                    <span class="label">Due Date:</span>
                    <span class="value"><?php echo date('Y-m-d', strtotime($invoice['date'] . ' + 30 days')); ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="invoice-addresses">
        <div class="row">
            <div class="col-6">
                <div class="from-address modern-card">
                    <h4>From</h4>
                    <div class="company-details">
                        <h5>Your Company Name</h5>
                        <p>Your Company Address</p>
                        <p>Phone: Your Phone</p>
                        <p>Email: Your Email</p>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="to-address modern-card">
                    <h4>Bill To</h4>
                    <div class="client-details">
                        <h5><?php echo htmlspecialchars($invoice['customer']['name']); ?></h5>
                        <p><?php echo htmlspecialchars($invoice['customer']['address']); ?></p>
                        <p>Phone: <?php echo htmlspecialchars($invoice['customer']['phone']); ?></p>
                        <p>Email: <?php echo htmlspecialchars($invoice['customer']['email']); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="invoice-items modern-card">
        <table class="table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice['items'] as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['description']); ?></strong>
                    </td>
                    <td class="text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td class="text-right"><?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($item['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="invoice-summary">
        <div class="row">
            <div class="col-7">
                <div class="payment-details modern-card">
                    <h4>Payment Details</h4>
                    <p>Bank: Your Bank Name</p>
                    <p>Account: Your Account Number</p>
                    <p>SWIFT: Your SWIFT Code</p>
                </div>
            </div>
            <div class="col-5">
                <div class="total-amount modern-card">
                    <div class="amount-row">
                        <span>Subtotal:</span>
                        <span><?php echo number_format($invoice['subtotal'], 2); ?></span>
                    </div>
                    <?php if ($invoice['discount'] > 0): ?>
                    <div class="amount-row">
                        <span>Discount:</span>
                        <span>-<?php echo number_format($invoice['discount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($invoice['tax'] > 0): ?>
                    <div class="amount-row">
                        <span>Tax:</span>
                        <span><?php echo number_format($invoice['tax'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="amount-row total">
                        <span>Total:</span>
                        <span><?php echo number_format($invoice['total'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="invoice-footer">
        <p>Thank you for your business!</p>
    </div>
</div>

<style>
.invoice-template.modern {
    padding: 40px;
    background-color: #f8f9fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.modern-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.invoice-title {
    color: #2c3e50;
    font-size: 48px;
    font-weight: 700;
    margin: 0;
}

.invoice-number {
    color: #7f8c8d;
    font-size: 24px;
}

.invoice-meta {
    margin: 30px 0;
    color: #7f8c8d;
}

.invoice-meta .label {
    font-weight: 600;
    margin-right: 10px;
}

.company-logo img {
    max-height: 80px;
}

.invoice-addresses {
    margin-bottom: 30px;
}

.invoice-addresses h4 {
    color: #2c3e50;
    margin-bottom: 15px;
}

.invoice-addresses h5 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.invoice-items table {
    margin: 0;
}

.invoice-items thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.amount-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
}

.amount-row.total {
    border-top: 2px solid #dee2e6;
    margin-top: 10px;
    padding-top: 15px;
    font-weight: 700;
    font-size: 1.2em;
}

.invoice-footer {
    text-align: center;
    margin-top: 40px;
    color: #7f8c8d;
}

@media print {
    .invoice-template.modern {
        background-color: white;
    }
    
    .modern-card {
        box-shadow: none;
        border: 1px solid #dee2e6;
    }
}
</style>