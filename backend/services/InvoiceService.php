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
        $maxAllowedOvershoot = min($targetAmount * 0.1, 200.0);
        $maxAllowedTotal = $targetAmount + $maxAllowedOvershoot;

        // ✅ Fix target condition to allow exact match
        $target_cond = $targetAmount;

        // Sort products by price descending
        $sorted_products = $products;
        usort($sorted_products, function ($a, $b) {
            return $b['price'] <=> $a['price'];
        });

        $n = count($sorted_products);
        $unit_totals = [];
        $max_qtys = [];

        foreach ($sorted_products as $product) {
            $price = (float) $product['price'];
            $taxRate = (float) ($product['tax_rate'] ?? 0);
            $type = $product['product_type'] ?? 'physical';
            $stock = (int) ($product['stock'] ?? 1);

            $per_unit_total = $price * (1 + $taxRate / 100);
            $unit_totals[] = $per_unit_total;

            // Digital = allow only 1 qty
            $max_qtys[] = ($type === 'digital') ? 1 : max(0, $stock);
        }

        // Precompute max_add for pruning
        $max_add = array_fill(0, $n + 1, 0.0);
        for ($i = $n - 1; $i >= 0; $i--) {
            $max_add[$i] = $max_add[$i + 1] + $max_qtys[$i] * $unit_totals[$i];
        }

        $best_total = PHP_FLOAT_MAX;
        $best_qtys = null;
        $qtys = array_fill(0, $n, 0);

        $recurse = function (int $idx, float $curr_total, array &$qtys) use (&$best_total, &$best_qtys, $n, $unit_totals, $max_qtys, $max_add, $target_cond, $maxAllowedTotal, &$recurse): void {

            if ($idx == $n) {
                if ($curr_total >= $target_cond && $curr_total <= $maxAllowedTotal && $curr_total < $best_total) {
                    $best_total = $curr_total;
                    $best_qtys = $qtys;
                }
                return;
            }

            if ($curr_total >= $best_total)
                return;

            // ✅ Allow tolerance so valid combos aren't skipped
            if ($curr_total + $max_add[$idx] < $target_cond - 0.01)
                return;

            $per_unit = $unit_totals[$idx];

            // ✅ Skip zero-value products to prevent division by zero
            if ($per_unit <= 0) {
                $qtys[$idx] = 0;
                $recurse($idx + 1, $curr_total, $qtys);
                return;
            }

            $max_qty_here = min($max_qtys[$idx], floor(($maxAllowedTotal - $curr_total) / $per_unit));
            for ($qty = 0; $qty <= $max_qty_here; $qty++) {
                $new_total = $curr_total + $qty * $per_unit;
                if ($new_total > $maxAllowedTotal)
                    break;
                $qtys[$idx] = $qty;
                $recurse($idx + 1, $new_total, $qtys);
            }
        };

        $recurse(0, 0.0, $qtys);

        if ($best_qtys === null)
            return false;

        $selected = [];
        $tempTotal = 0.0;

        foreach ($best_qtys as $i => $qty) {
            if ($qty == 0)
                continue;

            $product = $sorted_products[$i];
            $price = (float) $product['price'];
            $taxRate = (float) ($product['tax_rate'] ?? 0);

            $subTotal = $price * $qty;
            $tax = $subTotal * ($taxRate / 100);
            $totalWithTax = $subTotal + $tax;

            $selected[] = [
                'id' => $product['id'],
                'name' => $product['name'],
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

        $discountPercent = 0.0;
        $appliedDiscount = 0.0;

        if ($enableDiscount && $tempTotal > $targetAmount) {
            $appliedDiscount = $tempTotal - $targetAmount;
            $discountPercent = ($appliedDiscount / $tempTotal) * 100;
            $tempTotal = $targetAmount;
        }

        return [
            'products' => $selected,
            'discount_percent' => round($discountPercent, 4),
            'summary' => [
                'sub_total' => round(array_sum(array_column($selected, 'sub_total')), 2),
                'tax' => round(array_sum(array_column($selected, 'tax')), 2),
                'discount' => round($appliedDiscount, 2),
                'total' => round($tempTotal, 2),
                'target' => round($targetAmount, 2)
            ]
        ];
    }
}
