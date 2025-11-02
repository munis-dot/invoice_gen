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
        $date = $payload['date'];
        $invoiceNumber = $payload['invoiceNumber'];
        $targetAmount = (float) $payload['amount'];
        $paymentMethod = $payload['paymetMethod'] ?? 'cash';
        $userId = $payload['created_by'] ?? null;
        $discountEnabled = $payload['discount'] === 'false' ? false : true;

        // 1️⃣ Fetch all available products
        $products = $this->invoiceModel->getAllProducts();
        // echo $discountEnabled;
        // 2️⃣ Apply algorithm to select products & adjust discounts
        $productMix = $this->generateProductMix($products, $targetAmount, $discountEnabled);
        // 3️⃣ Save invoice
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
    function generateProductMix(array $products, float $targetAmount, bool $enableDiscount = true): array
{
    $max_attempts = 50;
    $selected = [];
    $total = 0.0;
    $discountPercent = 0.0;

    if ($enableDiscount) {
        // Mode 1: Enable discount - aim for >= target, prefer <=1.25x for <=20% discount
        $success = false;
        $maxAllowedTotal = $targetAmount + 200.0;

        // Retry loop to find selection where target <= total <= target*1.25
        $attempts = 0;
        while ($attempts < $max_attempts) {
            $selected = [];
            $total = 0.0;
            shuffle($products);
            foreach ($products as $product) {
                if ($total >= $targetAmount) break;

                $price = (float)$product['price'];
                $taxRate = (float)($product['tax_rate'] ?? 0);
                $type = $product['product_type'] ?? 'physical';
                $stock = (int)($product['stock'] ?? 1);

                // Physical: random quantity 1 - stock, Digital: only 1
                $maxQty = $type === 'physical' ? max(1, $stock) : 1;
                $qty = $type === 'physical' ? rand(1, $maxQty) : 1;

                $subTotal = $price * $qty;
                $tax = $subTotal * ($taxRate / 100);
                $totalWithTax = $subTotal + $tax;

                // Skip if exceeds max allowed for 20% discount
                if ($total + $totalWithTax > $maxAllowedTotal) continue;

                $selected[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'type' => $type,
                    'qty' => $qty,
                    'price' => round($price, 2),
                    'tax_rate' => round($taxRate, 2),
                    'sub_total' => round($subTotal, 2),
                    'tax' => round($tax, 2),
                    'discount' => 0.0,
                    'total' => round($totalWithTax, 2)
                ];

                $total += $totalWithTax;
                if ($total >= $targetAmount) break;
            }

            // Check if we hit the sweet spot: >= target and <= maxAllowed
            if ($total >= $targetAmount && $total <= $maxAllowedTotal) {
                $success = true;
                break;
            }
            $attempts++;
        }

        // Fallback if no sweet spot found: Relax upper limit, allow higher total
        if (!$success) {
            $selected = [];
            $total = 0.0;
            $fallbackMax = $targetAmount + 300.0; // Allow up to 2x for fallback
            shuffle($products);
            foreach ($products as $product) {
                if ($total >= $targetAmount) break;

                $price = (float)$product['price'];
                $taxRate = (float)($product['tax_rate'] ?? 0);
                $type = $product['product_type'] ?? 'physical';
                $stock = (int)($product['stock'] ?? 1);

                $maxQty = $type === 'physical' ? max(1, $stock) : 1;
                $qty = $type === 'physical' ? rand(1, min(3, $maxQty)) : 1; // Smaller qty in fallback

                $subTotal = $price * $qty;
                $tax = $subTotal * ($taxRate / 100);
                $totalWithTax = $subTotal + $tax;

                // In fallback, skip only if way over
                if ($total + $totalWithTax > $fallbackMax) continue;

                $selected[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'type' => $type,
                    'qty' => $qty,
                    'price' => round($price, 2),
                    'tax_rate' => round($taxRate, 2),
                    'sub_total' => round($subTotal, 2),
                    'tax' => round($tax, 2),
                    'discount' => 0.0,
                    'total' => round($totalWithTax, 2)
                ];

                $total += $totalWithTax;
            }

            // If still under, add more if possible
            if ($total < $targetAmount) {
                // Simple: add smallest possible items until over
                foreach ($products as $product) {
                    if ($total >= $targetAmount) break;
                    $price = (float)$product['price'];
                    $taxRate = (float)($product['tax_rate'] ?? 0);
                    $type = $product['product_type'] ?? 'physical';
                    $stock = (int)($product['stock'] ?? 1);
                    $qty = 1; // Minimal
                    $subTotal = $price * $qty;
                    $tax = $subTotal * ($taxRate / 100);
                    $totalWithTax = $subTotal + $tax;
                    if ($total + $totalWithTax > $fallbackMax * 1.5) continue;

                    $selected[] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'type' => $type,
                        'qty' => $qty,
                        'price' => round($price, 2),
                        'tax_rate' => round($taxRate, 2),
                        'sub_total' => round($subTotal, 2),
                        'tax' => round($tax, 2),
                        'discount' => 0.0,
                        'total' => round($totalWithTax, 2)
                    ];
                    $total += $totalWithTax;
                }
            }
        }

        // Apply proportional discount to match targetAmount (allow >20% if needed)
        $actualTotal = $total;
        $discountNeeded = $actualTotal - $targetAmount;

        if ($discountNeeded > 0 && $actualTotal > 0) {
            $discountPercent = ($discountNeeded / $actualTotal) * 100;
            foreach ($selected as &$item) {
                $itemDiscount = ($item['total'] * $discountPercent) / 100;
                $item['discount'] = round($itemDiscount, 2);
                $item['total'] = round($item['total'] - $itemDiscount, 2);
            }
            unset($item);

            // Adjust last item if rounding causes discrepancy (ensure exact match)
            $current_sum = array_sum(array_column($selected, 'total'));
            $diff = round($targetAmount - $current_sum, 2);
            if (abs($diff) > 0.01 && !empty($selected)) {
                $last_item =& $selected[count($selected) - 1];
                $last_item['discount'] += $diff;
                $last_item['total'] -= $diff;
            }
        }
    } else {
        // Mode 2: Disable discount - aim for closest total <= targetAmount
        $bestSelected = [];
        $bestTotal = 0.0;
        $bestDiff = PHP_FLOAT_MAX;

        for ($attempts = 0; $attempts < $max_attempts; $attempts++) {
            $tempSelected = [];
            $tempTotal = 0.0;
            shuffle($products);
            foreach ($products as $product) {
                $price = (float)$product['price'];
                $taxRate = (float)($product['tax_rate'] ?? 0);
                $type = $product['product_type'] ?? 'physical';
                $stock = (int)($product['stock'] ?? 1);

                // Physical: random quantity 1 - stock, Digital: only 1
                $maxQty = $type === 'physical' ? max(1, $stock) : 1;
                $qty = $type === 'physical' ? rand(1, $maxQty) : 1;

                $subTotal = $price * $qty;
                $tax = $subTotal * ($taxRate / 100);
                $totalWithTax = $subTotal + $tax;

                // Skip if would exceed target (stay under or equal)
                if ((($tempTotal + $totalWithTax) > ($targetAmount + 150)) || (($tempTotal + $totalWithTax) < $targetAmount)) continue;

                $tempSelected[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'type' => $type,
                    'qty' => $qty,
                    'price' => round($price, 2),
                    'tax_rate' => round($taxRate, 2),
                    'sub_total' => round($subTotal, 2),
                    'tax' => round($tax, 2),
                    'discount' => 0.0,
                    'total' => round($totalWithTax, 2)
                ];

                $tempTotal += $totalWithTax;
            }

            // Track the closest under target
            $tempDiff = $targetAmount - $tempTotal;
            if ($tempDiff < $bestDiff && $tempTotal > 0) {
                $bestDiff = $tempDiff;
                $bestSelected = $tempSelected;
                $bestTotal = $tempTotal;
            }
        }

        $selected = $bestSelected;
        $total = $bestTotal;

        // If nothing found, fallback to smallest possible selection
        if (empty($selected)) {
            // Sort products by price ascending, add cheapest until close
            usort($products, function($a, $b) {
                return (float)$a['price'] <=> (float)$b['price'];
            });
            foreach ($products as $product) {
                if ($total >= $targetAmount) break;
                $price = (float)$product['price'];
                $taxRate = (float)($product['tax_rate'] ?? 0);
                $type = $product['product_type'] ?? 'physical';
                $stock = (int)($product['stock'] ?? 1);
                $qty = 1; // Minimal qty
                $subTotal = $price * $qty;
                $tax = $subTotal * ($taxRate / 100);
                $totalWithTax = $subTotal + $tax;
                if ($total + $totalWithTax > $targetAmount) continue;

                $selected[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'type' => $type,
                    'qty' => $qty,
                    'price' => round($price, 2),
                    'tax_rate' => round($taxRate, 2),
                    'sub_total' => round($subTotal, 2),
                    'tax' => round($tax, 2),
                    'discount' => 0.0,
                    'total' => round($totalWithTax, 2)
                ];
                $total += $totalWithTax;
            }
        }
    }

    $summarySubTotal = array_sum(array_column($selected, 'sub_total'));
    $summaryTax = array_sum(array_column($selected, 'tax'));
    $summaryDiscount = array_sum(array_column($selected, 'discount'));
    $summaryTotal = array_sum(array_column($selected, 'total'));

    return [
        'products' => $selected,
        'discount_percent' => round($discountPercent, 4),
        'summary' => [
            'sub_total' => round($summarySubTotal, 2),
            'tax' => round($summaryTax, 2),
            'discount' => round($summaryDiscount, 2),
            'total' => round($summaryTotal, 2),
            'target' => round($targetAmount, 2)
        ]
    ];
}
}
