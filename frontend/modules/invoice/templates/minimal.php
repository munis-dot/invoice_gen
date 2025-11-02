<?php
$invoice = json_decode(file_get_contents('php://input'), true);
?>
<div class="invoice-template minimal">
    <div class="minimal-header">
        <div class="minimal-logo">
            <?php if ($invoice['companyLogoUrl']): ?>
                <img src="<?php echo htmlspecialchars($invoice['companyLogoUrl']); ?>" alt="Company Logo">
            <?php endif; ?>
        </div>
        <div class="minimal-title">
            Invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?>
        </div>
    </div>

    <div class="minimal-info">
        <div class="info-block">
            <span class="label">Date:</span>
            <span class="value"><?php echo htmlspecialchars($invoice['date']); ?></span>
        </div>
    </div>

    <div class="minimal-parties">
        <div class="party from">
            <div class="party-label">From</div>
            <div class="party-details">
                Your Company Name<br>
                Your Company Address<br>
                Phone: Your Phone<br>
                Email: Your Email
            </div>
        </div>
        <div class="party to">
            <div class="party-label">To</div>
            <div class="party-details">
                <?php echo htmlspecialchars($invoice['customer']['name']); ?><br>
                <?php echo htmlspecialchars($invoice['customer']['address']); ?><br>
                Phone: <?php echo htmlspecialchars($invoice['customer']['phone']); ?><br>
                Email: <?php echo htmlspecialchars($invoice['customer']['email']); ?>
            </div>
        </div>
    </div>

    <table class="minimal-items">
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($invoice['items'] as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['description']); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td><?php echo number_format($item['price'], 2); ?></td>
                <td><?php echo number_format($item['total'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="minimal-totals">
        <div class="total-row">
            <span>Subtotal</span>
            <span><?php echo number_format($invoice['subtotal'], 2); ?></span>
        </div>
        <?php if ($invoice['discount'] > 0): ?>
        <div class="total-row">
            <span>Discount</span>
            <span>-<?php echo number_format($invoice['discount'], 2); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($invoice['tax'] > 0): ?>
        <div class="total-row">
            <span>Tax</span>
            <span><?php echo number_format($invoice['tax'], 2); ?></span>
        </div>
        <?php endif; ?>
        <div class="total-row final">
            <span>Total</span>
            <span><?php echo number_format($invoice['total'], 2); ?></span>
        </div>
    </div>

    <div class="minimal-footer">
        Thank you for your business
    </div>
</div>

<style>
.invoice-template.minimal {
    max-width: 800px;
    margin: 0 auto;
    padding: 40px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    color: #333;
    line-height: 1.6;
}

.minimal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.minimal-logo img {
    max-height: 60px;
}

.minimal-title {
    font-size: 24px;
    font-weight: 300;
}

.minimal-info {
    margin-bottom: 30px;
}

.info-block {
    margin-bottom: 10px;
}

.info-block .label {
    color: #666;
    margin-right: 10px;
}

.minimal-parties {
    display: flex;
    justify-content: space-between;
    margin-bottom: 40px;
}

.party {
    flex: 1;
    max-width: 300px;
}

.party-label {
    text-transform: uppercase;
    font-size: 12px;
    color: #666;
    margin-bottom: 10px;
    letter-spacing: 1px;
}

.party-details {
    font-size: 14px;
}

.minimal-items {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 40px;
}

.minimal-items th {
    text-align: left;
    padding: 10px;
    border-bottom: 1px solid #ddd;
    font-weight: normal;
    color: #666;
}

.minimal-items td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.minimal-items td:not(:first-child) {
    text-align: right;
}

.minimal-totals {
    width: 300px;
    margin-left: auto;
}

.total-row {
    display: flex;
    justify-content: space-between;
    padding: 5px 0;
}

.total-row.final {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
    font-weight: 500;
}

.minimal-footer {
    margin-top: 40px;
    text-align: center;
    font-size: 14px;
    color: #666;
}

@media print {
    .minimal-items {
        page-break-inside: avoid;
    }
    
    .minimal-totals {
        page-break-inside: avoid;
    }
}
</style>