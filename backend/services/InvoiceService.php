<?php
require_once __DIR__ . '/../models/Invoice.php';

class InvoiceService
{

    protected $invoiceModel;

    public function __construct()
    {
        $this->invoiceModel = new Invoice();
    }

    public function processInvoice(array $payload)
    {
        $customerId = $payload['customerId'];
        
        // Validate if customer exists
        require_once __DIR__ . '/../models/Customer.php';
        $customer = Customer::find($customerId);
        if (!$customer) {
            throw new Exception("Customer not found with ID: $customerId");
        }

        $date = $payload['date'];
        $invoiceNumber = $payload['invoiceNumber'];
        $targetAmount = (float) $payload['amount'];
        $paymentMethod = $payload['paymetMethod'] ?? 'cash';
        $userId = $payload['created_by'] ?? null;
        $discountEnabled = $payload['discount'] === 'false' ? false : true;
        $companyLogo = $payload['company_logo'] ?? null;
        $email = $payload['email'] ?? null;
        $address = $payload['address'] ?? null;

        // 1️⃣ Fetch all available products
        $products = $this->invoiceModel->getAllProducts();
        // echo $discountEnabled;
        // 2️⃣ Apply algorithm to select products & adjust discounts
        $productMix = $this->generateProductMix($products, $targetAmount, $discountEnabled);
        // 3️⃣ Save invoice
        if(!$productMix){
            throw new Exception("Unable to generate invoice for the given amount.");
        }
        $invoiceId = $this->invoiceModel->createInvoice([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customerId,
            'payment_method' => $paymentMethod,
            'date' => $date,
            'subtotal' => $productMix['summary']['sub_total'],
            'discount' => $productMix['summary']['discount'],
            'tax' => $productMix['summary']['tax'],
            'total' => $productMix['summary']['total'],
            'created_by' => $userId,
            'company_logo' => $companyLogo,
            'email' => $email,
            'address' => $address,
            'pdf_path' => null
        ]);

        // 4️⃣ Save invoice items
        $this->invoiceModel->createInvoiceItems($invoiceId, $productMix['products']);

        return [
            'invoice_id' => $invoiceId,
            'products' => $productMix['products'],
            'summary' => $productMix['summary']
        ];
    }
    // ---------------- Generate invoice number ----------------
    //random invoice number
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    // ---------------- Algorithm ----------------
function generateProductMix(array $products, float $targetAmount, bool $enableDiscount = true): array|false
{
    error_log("[generateProductMix] TIME COMPLEXITY: O(n log n) [sort] + O(n * target) [DP]");

    if (empty($products) || $targetAmount <= 0) {
        return [
            'error' => 'Invalid input: No products or target amount <= 0',
            'debug' => ['products_count' => count($products), 'target_amount' => $targetAmount]
        ];
    }

    // === 1. PREPARE ITEMS ===
    $items = [];
    foreach ($products as $p) {
        $price = (float)$p['price'];
        if ($price <= 0) continue;

        $taxRate = (float)($p['tax_rate'] ?? 0);
        $stock   = (int)($p['stock'] ?? 1);
        $type    = $p['product_type'] ?? 'physical';
        $maxQty  = $type === 'digital' ? 1 : max(0, $stock);
        if ($maxQty < 1) continue;

        $unitTotal = $price * (1 + $taxRate / 100);
        $items[] = [
            'id'         => $p['id'],
            'name'       => $p['name'],
            'price'      => $price,
            'tax_rate'   => $taxRate,
            'unit_total' => $unitTotal,
            'max_qty'    => $maxQty,
        ];
    }

    if (empty($items)) {
        return ['error' => 'No valid products after filtering', 'debug' => ['target_amount' => $targetAmount]];
    }

    // === 2. DISCOUNT DISABLED → EXACT MATCH WITH QUANTITY SUPPORT ===
    if (!$enableDiscount) {
        $targetCents = (int)round($targetAmount * 100);
        $dp = array_fill(0, $targetCents + 1, false);
        $dp[0] = true;
        $prev = []; // [cents => ['item' => $item, 'qty' => int]]

        foreach ($items as $item) {
            $itemCents = (int)round($item['unit_total'] * 100);
            $maxQty = $item['max_qty'];

            // Try adding 1 to maxQty of this item
            for ($qty = 1; $qty <= $maxQty; $qty++) {
                $totalCents = $itemCents * $qty;
                if ($totalCents > $targetCents) break;

                for ($j = $targetCents; $j >= $totalCents; $j--) {
                    if ($dp[$j - $totalCents]) {
                        $dp[$j] = true;
                        $prev[$j] = ['item' => $item, 'qty' => $qty];
                    }
                }
            }
        }

        // Reconstruct solution
        if ($dp[$targetCents]) {
            $selected = [];
            $current = $targetCents;

            while ($current > 0) {
                $entry = $prev[$current];
                $item = $entry['item'];
                $qty  = $entry['qty'];

                $subTotal = $item['price'] * $qty;
                $tax      = $subTotal * ($item['tax_rate'] / 100);
                $lineTotal = $subTotal + $tax;

                $selected[] = [
                    'id'        => $item['id'],
                    'name'      => $item['name'],
                    'qty'       => $qty,
                    'price'     => round($item['price'], 2),
                    'tax_rate'  => round($item['tax_rate'], 2),
                    'sub_total' => round($subTotal, 2),
                    'tax'       => round($tax, 2),
                    'discount'  => 0.0,
                    'total'     => round($lineTotal, 2),
                ];

                $current -= (int)round($lineTotal * 100);
            }

            $subtotal = array_sum(array_column($selected, 'sub_total'));
            $tax      = array_sum(array_column($selected, 'tax'));
            $total    = $subtotal + $tax;

            $output = [
                'products' => $selected,
                'discount_percent' => 0.0,
                'summary' => [
                    'sub_total' => round($subtotal, 2),
                    'tax'       => round($tax, 2),
                    'discount'  => 0.0,
                    'total'     => round($total, 2),
                    'target'    => round($targetAmount, 2)
                ]
            ];

            error_log("[generateProductMix] EXACT MATCH (qty support) | items: " . count($selected));
            return $output;
        } else {
            return [
                'error' => 'No combination (with quantity) matches target (discount disabled)',
                'debug' => ['target_amount' => $targetAmount, 'enable_discount' => false]
            ];
        }
    }

    // === 3. DISCOUNT ENABLED → GREEDY + 10% OVERSHOOT (unchanged) ===
    usort($items, fn($a, $b) => $b['unit_total'] <=> $a['unit_total']);

    $maxOvershoot = $targetAmount * 0.1;
    $maxAllowed   = $targetAmount + $maxOvershoot;

    $selected = [];
    $current  = 0.0;

    foreach ($items as $item) {
        if ($current >= $maxAllowed) break;

        $affordable = (int)floor(($maxAllowed - $current) / $item['unit_total']);
        $qty = min($affordable, $item['max_qty']);
        $qty = max(1, $qty);

        if ($current + $item['unit_total'] > $maxAllowed) {
            $qty = (int)floor(($maxAllowed - $current) / $item['unit_total']);
            if ($qty < 1) continue;
        }

        $lineSubtotal = $item['price'] * $qty;
        $lineTax      = $lineSubtotal * ($item['tax_rate'] / 100);
        $lineTotal    = $lineSubtotal + $lineTax;

        $selected[] = [
            'id'        => $item['id'],
            'name'      => $item['name'],
            'qty'       => $qty,
            'price'     => round($item['price'], 2),
            'tax_rate'  => round($item['tax_rate'], 2),
            'sub_total' => round($lineSubtotal, 2),
            'tax'       => round($lineTax, 2),
            'discount'  => 0.0,
            'total'     => round($lineTotal, 2),
        ];

        $current += $lineTotal;
    }

    if ($current < $targetAmount && $current < $maxAllowed) {
        foreach (array_reverse($items) as $item) {
            if ($current + $item['unit_total'] <= $maxAllowed) {
                $lineSubtotal = $item['price'];
                $lineTax      = $lineSubtotal * ($item['tax_rate'] / 100);
                $lineTotal    = $lineSubtotal + $lineTax;

                $selected[] = [
                    'id'        => $item['id'],
                    'name'      => $item['name'],
                    'qty'       => 1,
                    'price'     => round($item['price'], 2),
                    'tax_rate'  => round($item['tax_rate'], 2),
                    'sub_total' => round($lineSubtotal, 2),
                    'tax'       => round($lineTax, 2),
                    'discount'  => 0.0,
                    'total'     => round($lineTotal, 2),
                ];
                $current += $lineTotal;
                break;
            }
        }
    }

    $subtotal = array_sum(array_column($selected, 'sub_total'));
    $tax      = array_sum(array_column($selected, 'tax'));
    $total    = $subtotal + $tax;

    $discount = 0.0;
    $discountPercent = 0.0;

    if ($total > $targetAmount) {
        $discount = round($total - $targetAmount, 2);
        $discountPercent = $total > 0 ? round(($discount / $total) * 100, 4) : 0;
        $total = round($targetAmount, 2);
    }

    $output = [
        'products' => $selected,
        'discount_percent' => $discountPercent,
        'summary' => [
            'sub_total' => round($subtotal, 2),
            'tax'       => round($tax, 2),
            'discount'  => $discount,
            'total'     => $total,
            'target'    => round($targetAmount, 2)
        ]
    ];

    if (abs($total - $targetAmount) > 0.01) {
        $output['error'] = 'Failed to match target amount';
        $output['debug'] = [
            'calculated_total' => $total,
            'target_amount'    => $targetAmount,
            'difference'       => round($total - $targetAmount, 2),
            'enable_discount'  => true,
            'items_selected'   => count($selected),
            'max_allowed'      => round($maxAllowed, 2)
        ];
    }

    error_log("[generateProductMix] RETURN " . (isset($output['error']) ? 'WITH ERROR' : 'SUCCESS') . " | total: $total | target: $targetAmount");

    return $output;
}

}