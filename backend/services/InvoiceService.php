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

// function generateProductMix(array $products, float $targetAmount, bool $enableDiscount = true): array|false
// {
//     $max_attempts = 50;
//     $selected = [];
//     $total = 0.0;
//     $discountPercent = 0.0;
//     $appliedDiscount = 0.0;

//     $maxAllowedOvershoot = min($targetAmount * 0.1, 200.0);
//     $maxAllowedTotal = $targetAmount + $maxAllowedOvershoot;

//     if ($enableDiscount) {
//         // Mode 1: Enable discount - aim for >= target, <= maxAllowedTotal, pick minimal overshoot
//         $bestOvershoot = PHP_FLOAT_MAX;
//         $bestSelected = [];
//         $bestTotal = 0.0;

//         for ($attempts = 0; $attempts < $max_attempts; $attempts++) {
//             $tempSelected = [];
//             $tempTotal = 0.0;
//             shuffle($products);
//             foreach ($products as $product) {
//                 if ($tempTotal >= $targetAmount) break;

//                 $price = (float)$product['price'];
//                 $taxRate = (float)($product['tax_rate'] ?? 0);
//                 $type = $product['product_type'] ?? 'physical';
//                 $stock = (int)($product['stock'] ?? 1);

//                 // Physical: random quantity 1 - stock, Digital: only 1
//                 $maxQty = $type === 'physical' ? max(1, $stock) : 1;
//                 $qty = $type === 'physical' ? rand(1, $maxQty) : 1;

//                 $subTotal = $price * $qty;
//                 $tax = $subTotal * ($taxRate / 100);
//                 $totalWithTax = $subTotal + $tax;

//                 // Skip if exceeds max allowed
//                 if ($tempTotal + $totalWithTax > $maxAllowedTotal) continue;

//                 $tempSelected[] = [
//                     'id' => $product['id'],
//                     'name' => $product['name'],
//                     'type' => $type,
//                     'qty' => $qty,
//                     'price' => round($price, 2),
//                     'tax_rate' => round($taxRate, 2),
//                     'sub_total' => round($subTotal, 2),
//                     'tax' => round($tax, 2),
//                     'discount' => 0.0,
//                     'total' => round($totalWithTax, 2)
//                 ];

//                 $tempTotal += $totalWithTax;
//             }

//             $overshoot = $tempTotal - $targetAmount;
//             if ($overshoot >= 0 && $tempTotal <= $maxAllowedTotal && $overshoot < $bestOvershoot) {
//                 $bestOvershoot = $overshoot;
//                 $bestSelected = $tempSelected;
//                 $bestTotal = $tempTotal;
//             }
//         }

//         if (empty($bestSelected)) {
//             return false;
//         }

//         $selected = $bestSelected;
//         $total = $bestTotal;

//         // Apply whole order discount to match targetAmount
//         $actualTotal = $total;
//         $discountNeeded = $actualTotal - $targetAmount;

//         if ($discountNeeded > 0 && $actualTotal > 0) {
//             $appliedDiscount = $discountNeeded;
//             $discountPercent = ($appliedDiscount / $actualTotal) * 100;
//         }
//     } else {
//         // Mode 2: Disable discount - aim for > target, <= maxAllowedTotal, pick minimal overshoot
//         $bestOvershoot = PHP_FLOAT_MAX;
//         $bestSelected = [];
//         $bestTotal = 0.0;

//         for ($attempts = 0; $attempts < $max_attempts; $attempts++) {
//             $tempSelected = [];
//             $tempTotal = 0.0;
//             shuffle($products);
//             foreach ($products as $product) {
//                 if ($tempTotal >= $targetAmount) break;

//                 $price = (float)$product['price'];
//                 $taxRate = (float)($product['tax_rate'] ?? 0);
//                 $type = $product['product_type'] ?? 'physical';
//                 $stock = (int)($product['stock'] ?? 1);

//                 // Physical: random quantity 1 - stock, Digital: only 1
//                 $maxQty = $type === 'physical' ? max(1, $stock) : 1;
//                 $qty = $type === 'physical' ? rand(1, $maxQty) : 1;

//                 $subTotal = $price * $qty;
//                 $tax = $subTotal * ($taxRate / 100);
//                 $totalWithTax = $subTotal + $tax;

//                 // Skip if would exceed max allowed
//                 if ($tempTotal + $totalWithTax > $maxAllowedTotal) continue;

//                 $tempSelected[] = [
//                     'id' => $product['id'],
//                     'name' => $product['name'],
//                     'type' => $type,
//                     'qty' => $qty,
//                     'price' => round($price, 2),
//                     'tax_rate' => round($taxRate, 2),
//                     'sub_total' => round($subTotal, 2),
//                     'tax' => round($tax, 2),
//                     'discount' => 0.0,
//                     'total' => round($totalWithTax, 2)
//                 ];

//                 $tempTotal += $totalWithTax;
//             }

//             $overshoot = $tempTotal - $targetAmount;
//             if ($overshoot > 0 && $tempTotal <= $maxAllowedTotal && $overshoot < $bestOvershoot) {
//                 $bestOvershoot = $overshoot;
//                 $bestSelected = $tempSelected;
//                 $bestTotal = $tempTotal;
//             }
//         }

//         if (empty($bestSelected)) {
//             return false;
//         }

//         $selected = $bestSelected;
//         $total = $bestTotal;
//     }

//     $summarySubTotal = array_sum(array_column($selected, 'sub_total'));
//     $summaryTax = array_sum(array_column($selected, 'tax'));
//     $summaryDiscount = round($appliedDiscount, 2);
//     $summaryTotal = $summarySubTotal + $summaryTax - $summaryDiscount;

//     if ($summaryTotal == 0) {
//         return false;
//     }

//     return [
//         'products' => $selected,
//         'discount_percent' => round($discountPercent, 4),
//         'summary' => [
//             'sub_total' => round($summarySubTotal, 2),
//             'tax' => round($summaryTax, 2),
//             'discount' => $summaryDiscount,
//             'total' => round($summaryTotal, 2),
//             'target' => round($targetAmount, 2)
//         ]
//     ];
// }

function generateProductMix(array $products, float $targetAmount, bool $enableDiscount = true): array|false
{
    $max_attempts = 50; // Retained but not used in optimized version
    $selected = [];
    $total = 0.0;
    $discountPercent = 0.0;
    $appliedDiscount = 0.0;

    $maxAllowedOvershoot = min($targetAmount * 0.1, 200.0);
    $maxAllowedTotal = $targetAmount + $maxAllowedOvershoot;

    // Sort products by price descending for greedy high-to-low selection
    usort($products, function($a, $b) {
        return $b['price'] <=> $a['price'];
    });

    // Single greedy pass: select quantities deterministically to reach >= targetAmount
    $tempSelected = [];
    $tempTotal = 0.0;

    foreach ($products as $product) {
        if ($tempTotal >= $targetAmount) {
            break;
        }

        $price = (float)$product['price'];
        $taxRate = (float)($product['tax_rate'] ?? 0);
        $type = $product['product_type'] ?? 'physical';
        $stock = (int)($product['stock'] ?? 1);

        // Physical: up to stock, Digital: only 1
        $maxQty = $type === 'physical' ? max(1, $stock) : 1;

        $perUnitSubTotal = $price;
        $perUnitTax = $perUnitSubTotal * ($taxRate / 100);
        $perUnitTotal = $perUnitSubTotal + $perUnitTax;

        // Skip if even 1 unit exceeds max allowed
        if ($tempTotal + $perUnitTotal > $maxAllowedTotal) {
            continue;
        }

        $remaining = $targetAmount - $tempTotal;
        $qtyByTarget = (int) ceil($remaining / $perUnitTotal);
        $qty = min($maxQty, $qtyByTarget);

        // Cap by max allowed total
        $maxQtyByMaxTotal = (int) floor(($maxAllowedTotal - $tempTotal) / $perUnitTotal);
        $qty = min($qty, $maxQtyByMaxTotal);

        if ($qty < 1) {
            continue;
        }

        $subTotal = $price * $qty;
        $tax = $subTotal * ($taxRate / 100);
        $totalWithTax = $subTotal + $tax;

        // Double-check against max (due to float rounding)
        if ($tempTotal + $totalWithTax > $maxAllowedTotal) {
            continue;
        }

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

    // Validate based on mode
    $isValid = $enableDiscount ? ($tempTotal >= $targetAmount) : ($tempTotal > $targetAmount);
    if (empty($tempSelected) || !$isValid || $tempTotal > $maxAllowedTotal) {
        return false;
    }

    $selected = $tempSelected;
    $total = $tempTotal;

    // Apply whole order discount only if enabled
    if ($enableDiscount) {
        $actualTotal = $total;
        $discountNeeded = $actualTotal - $targetAmount;

        if ($discountNeeded > 0 && $actualTotal > 0) {
            $appliedDiscount = $discountNeeded;
            $discountPercent = ($appliedDiscount / $actualTotal) * 100;
        }
    }

    $summarySubTotal = array_sum(array_column($selected, 'sub_total'));
    $summaryTax = array_sum(array_column($selected, 'tax'));
    $summaryDiscount = round($appliedDiscount, 2);
    $summaryTotal = $summarySubTotal + $summaryTax - $summaryDiscount;

    if ($summaryTotal == 0) {
        return false;
    }

    return [
        'products' => $selected,
        'discount_percent' => round($discountPercent, 4),
        'summary' => [
            'sub_total' => round($summarySubTotal, 2),
            'tax' => round($summaryTax, 2),
            'discount' => $summaryDiscount,
            'total' => round($summaryTotal, 2),
            'target' => round($targetAmount, 2)
        ]
    ];
}
}
