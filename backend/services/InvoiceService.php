<?php
require_once __DIR__ . '/../models/Invoice.php';

/**
 * Service class to handle invoice generation logic
 * - Validates customer
 * - Applies product mix algorithm
 * - Saves invoice & items
 */
class InvoiceService
{
    /** @var Invoice Model instance for DB operations */
    protected $invoiceModel;

    /** Constructor - Initialize Invoice model */
    public function __construct()
    {
        $this->invoiceModel = new Invoice();
    }

    /**
     * Main method to process and create an invoice
     * @param array $payload Contains customerId, amount, date, etc.
     * @return array Invoice ID + selected products + summary
     * @throws Exception On validation or generation failure
     */
    public function processInvoice(array $payload): array
    {
        // Extract and sanitize input data
        $customerId       = $payload['customerId'];
        $date             = $payload['date'];
        $invoiceNumber    = $payload['invoiceNumber'];
        $targetAmount     = (float) $payload['amount'];           // Target total (including tax)
        $paymentMethod    = $payload['paymentMethod'] ?? 'cash';
        $userId           = $payload['created_by'] ?? null;
        $discountEnabled  = $payload['discount'] === 'false' ? false : true;
        $companyLogo      = $payload['company_logo'] ?? null;
        $email            = $payload['email'] ?? null;
        $address          = $payload['address'] ?? null;

        // === 1. Validate Customer ===
        require_once __DIR__ . '/../models/Customer.php';
        $customer = Customer::find($customerId);
        if (!$customer) {
            throw new Exception("Customer not found with ID: $customerId");
        }

        // === 2. Fetch All Available Products ===
        $products = $this->invoiceModel->getAllProducts();

        // === 3. Generate Product Mix using Algorithm ===
        $productMix = $this->generateProductMix($products, $targetAmount, $discountEnabled);

        // If algorithm failed to match target
        if (isset($productMix['error'])) {
            throw new Exception($productMix['error']);
        }

        // === 4. Save Invoice Header ===
        $invoiceId = $this->invoiceModel->createInvoice([
            'invoice_number' => $invoiceNumber,
            'customer_id'    => $customerId,
            'payment_method' => $paymentMethod,
            'date'           => $date,
            'subtotal'       => $productMix['summary']['sub_total'],
            'discount'       => $productMix['summary']['discount'],
            'tax'            => $productMix['summary']['tax'],
            'total'          => $productMix['summary']['total'],
            'created_by'     => $userId,
            'company_logo'   => $companyLogo,
            'email'          => $email,
            'address'        => $address,
            'pdf_path'       => null
        ]);

        // === 5. Save Invoice Line Items ===
        $this->invoiceModel->createInvoiceItems($invoiceId, $productMix['products']);

        // === 6. Return Success Response ===
        return [
            'invoice_id' => $invoiceId,
            'products'   => $productMix['products'],
            'summary'    => $productMix['summary']
        ];
    }

    /**
     * Generate a random 6-digit invoice number
     * @return string e.g., INV-123456
     */
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    // ===================================================================
    // ====================== MAIN ALGORITHM =============================
    // ===================================================================

    /**
     * Generate product combination to match target amount
     * @param array $products List of available products
     * @param float $targetAmount Desired invoice total
     * @param bool $enableDiscount Allow discount if overshoot
     * @return array Selected products + summary (or error)
     */
    public function generateProductMix(array $products, float $targetAmount, bool $enableDiscount = true): array
    {
        $startTime = microtime(true);

        // Log start of process
        echo "[generateProductMix] START | Target: $targetAmount | Items: " . count($products)
           . " | Discount: " . ($enableDiscount ? 'ON' : 'OFF') . "\n";
        echo "[generateProductMix] TIME COMPLEXITY: O(n log n) [sort] + O(n * target) [DP]\n";

        // === Logger Closure (for clean END logs) ===
        $logEnd = function (string $status, ?int $items = null) use ($startTime) {
            $duration = round((microtime(true) - $startTime) * 1000, 3);
            $msg = "[generateProductMix] END | Duration: {$duration}ms | Result: $status";
            if ($items !== null) {
                $msg .= " | Items: $items";
            }
            echo $msg . "\n";
        };

        // === Input Validation ===
        if (empty($products) || $targetAmount <= 0) {
            $logEnd('ERROR (Invalid input)', 0);
            return [
                'error' => 'Invalid input: No products or target amount <= 0',
                'debug' => ['products_count' => count($products), 'target_amount' => $targetAmount]
            ];
        }

        // ===================================================================
        // === 1. PREPARE ITEMS: Filter valid products & compute unit total ===
        // ===================================================================
        $items = [];
        foreach ($products as $p) {
            $price = (float)($p['price'] ?? 0);
            if ($price <= 0) continue; // Skip free/invalid

            $taxRate = (float)($p['tax_rate'] ?? 0);
            $stock   = (int)($p['stock'] ?? 1);
            $type    = $p['product_type'] ?? 'physical';

            // Digital: max 1, Physical: limited by stock
            $maxQty = $type === 'digital' ? 1 : max(0, $stock);
            if ($maxQty < 1) continue;

            // Total per unit = price + tax
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

        // No valid products left
        if (empty($items)) {
            $logEnd('ERROR (No valid products)', 0);
            return ['error' => 'No valid products after filtering', 'debug' => ['target_amount' => $targetAmount]];
        }

        // ===================================================================
        // === 2. DISCOUNT DISABLED: Exact Match using Dynamic Programming ===
        // ===================================================================
        if (!$enableDiscount) {
            $targetCents = (int)round($targetAmount * 100); // Work in cents to avoid float errors
            $dp   = array_fill(0, $targetCents + 1, false); // Can we make this amount?
            $prev = array_fill(0, $targetCents + 1, null);  // Backtracking: which item + qty?

            $dp[0] = true; // Base case: 0 is always possible

            // Process each item (like coin change - unbounded knapsack)
            foreach ($items as $item) {
                $itemCents = (int)round($item['unit_total'] * 100);
                if ($itemCents <= 0) continue;

                // Try to fill DP from current itemCents to target
                for ($j = $itemCents; $j <= $targetCents; $j++) {
                    if ($dp[$j - $itemCents]) {
                        $qty = 1;
                        $remaining = $j - $itemCents;

                        // If same item was used before, increase quantity
                        if (isset($prev[$remaining]) && $prev[$remaining]['item']['id'] === $item['id']) {
                            $qty = $prev[$remaining]['qty'] + 1;
                        }

                        // Respect stock limit
                        if ($qty <= $item['max_qty']) {
                            $dp[$j] = true;
                            $prev[$j] = ['item' => $item, 'qty' => $qty];
                        }
                    }
                }
            }

            // No exact combination found
            if (!$dp[$targetCents]) {
                $logEnd('NO_EXACT_MATCH', 0);
                return [
                    'error' => 'Cannot generate invoice: no combination reaches target exactly without discount',
                    'debug' => ['target_amount' => $targetAmount]
                ];
            }

            // === Reconstruct Solution from DP ===
            $selected = [];
            $current = $targetCents;

            while ($current > 0 && isset($prev[$current])) {
                $entry = $prev[$current];
                $item  = $entry['item'];
                $qty   = $entry['qty'];

                $subTotal  = $item['price'] * $qty;
                $tax       = $subTotal * ($item['tax_rate'] / 100);
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

            // === Final Summary (No Discount) ===
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

            $logEnd('EXACT_MATCH', count($selected));
            return $output;
        }

        // ===================================================================
        // === 3. DISCOUNT ENABLED: Greedy + 10% Overshoot Tolerance ===
        // ===================================================================
        // Sort by highest unit price first (greedy)
        usort($items, fn($a, $b) => $b['unit_total'] <=> $a['unit_total']);

        $maxOvershoot = $targetAmount * 0.1;     // Allow up to +10%
        $maxAllowed   = $targetAmount + $maxOvershoot;
        $selected     = [];
        $current      = 0.0;

        // === Greedy Selection: Take max possible qty of expensive items ===
        foreach ($items as $item) {
            if ($current >= $maxAllowed) break;

            // How many can we afford?
            $affordable = (int)floor(($maxAllowed - $current) / $item['unit_total']);
            $qty = min($affordable, $item['max_qty']);
            $qty = max(1, $qty); // At least 1

            // Safety: Recalculate if overshoot
            if ($current + $item['unit_total'] * $qty > $maxAllowed) {
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

        // === Fill Small Gap with Cheapest Item ===
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

        // === Calculate Totals ===
        $subtotal = array_sum(array_column($selected, 'sub_total'));
        $tax      = array_sum(array_column($selected, 'tax'));
        $total    = $subtotal + $tax;

        $discount = 0.0;
        $discountPercent = 0.0;

        // === Apply Discount if Overshot ===
        if ($total > $targetAmount) {
            $discount = round($total - $targetAmount, 2);
            $discountPercent = $total > 0 ? round(($discount / $total) * 100, 4) : 0;
            $total = round($targetAmount, 2); // Final billed amount
        }

        // === Final Output Structure ===
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

        // === Check if Target Was Met Exactly ===
        $difference = abs($total - $targetAmount);
        if ($difference > 0.01) {
            $output['error'] = 'Failed to match target exactly (used discount)';
            $output['debug'] = [
                'calculated_total' => round($subtotal + $tax, 2),
                'target_amount'    => $targetAmount,
                'difference'       => round($difference, 2),
                'max_allowed'      => round($maxAllowed, 2),
                'items_selected'   => count($selected)
            ];
            $logEnd('PARTIAL_MATCH', count($selected));
        } else {
            $logEnd('SUCCESS', count($selected));
        }

        return $output;
    }
}