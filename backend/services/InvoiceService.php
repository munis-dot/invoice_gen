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

    echo "[generateProductMix] START | Target: $targetAmount | Items: " . count($products)
       . " | Discount: " . ($enableDiscount ? 'ON' : 'OFF') . "\n";

    $logEnd = function (string $status, ?int $items = null) use ($startTime) {
        $duration = round((microtime(true) - $startTime) * 1000, 3);
        $msg = "[generateProductMix] END | Duration: {$duration}ms | Result: $status";
        if ($items !== null) $msg .= " | Items: $items";
        echo $msg . "\n";
    };

    if (empty($products) || $targetAmount <= 0) {
        $logEnd('ERROR (Invalid input)', 0);
        return [
            'error' => 'Invalid input: No products or target amount <= 0',
            'debug' => ['products_count' => count($products), 'target_amount' => $targetAmount]
        ];
    }

    // ===================================================================
    // 1. PREPARE ITEMS: Only price + type → canAddQuantity
    // ===================================================================
    $items = [];
    foreach ($products as $p) {
        $price = (float)($p['price'] ?? 0);
        if ($price <= 0) continue;

        $type = $p['product_type'] ?? 'physical';

        // BOOLEAN: Can we add quantity?
        $canAddQuantity = ($type === 'physical'); // true = physical, false = digital

        // Max qty: digital = 1, physical = unlimited
        $maxQty = $canAddQuantity ? PHP_INT_MAX : 1;

        $items[] = [
            'id'              => $p['id'],
            'name'            => $p['name'],
            'price'           => $price,
            'max_qty'         => $maxQty,
            'can_add_quantity'=> $canAddQuantity,
        ];
    }

    if (empty($items)) {
        $logEnd('ERROR (No valid products)', 0);
        return ['error' => 'No valid products', 'debug' => ['target_amount' => $targetAmount]];
    }

 // === 2. DISCOUNT DISABLED → FAST EXACT MATCH (BACKTRACKING) ===
if (!$enableDiscount) {
    $targetCents = (int)round($targetAmount * 100);

    // Try large prices first (greedy-like) → fewer combinations to explore, faster convergence.
    usort($items, fn($a, $b) => $b['price'] <=> $a['price']);
    //$result holds the found combination (or null if none). $maxDepth stops recursion when no more items.
    $result = null;
    $maxDepth = count($items);

    /*$findCombination is the backtracking core that is closure (anonymous function) that calls itself.
$index: Current product index (0 to $maxDepth-1)
$currentCents: Current sum (starts at 0)
$currentSelection: List of selected products so far (starts empty)
'use': Passes $items, $targetCents, etc., to recursion.
*/
    $findCombination = function (int $index, int $currentCents, array $currentSelection) use (
        &$findCombination, $items, $targetCents, &$result, $maxDepth
    ): void {
        /*If we already found a match (elsewhere in recursion), stop.
If current sum == target → save selection and stop.*/
        if ($result !== null) return; // Already found

        if ($currentCents === $targetCents) {
            $result = $currentSelection;
            return;
        }
/*$index >= $maxDepth: No more products to try.
$currentCents > $targetCents: Overshot → invalid path.*/

        if ($index >= $maxDepth || $currentCents > $targetCents) {
            return;
        }
//Prepare item data for this recursion level
        $item = $items[$index];
        $itemCents = (int)round($item['price'] * 100);
        $maxQty = $item['can_add_quantity'] ? $item['max_qty'] : 1;

        // Don't try qty that would overshoot remaining amount.
        $maxPossibleQty = $item['can_add_quantity']
            ? min($maxQty, (int)floor(($targetCents - $currentCents) / $itemCents))
            : 1;
//Try high qty first (finds solution quicker).
        for ($qty = $maxPossibleQty; $qty >= 0; $qty--) {
            if ($qty === 0) {
                $findCombination($index + 1, $currentCents, $currentSelection);
                continue;
            }

            $newCents = $currentCents + $itemCents * $qty;
            if ($newCents > $targetCents) continue;

            $newSelection = $currentSelection;
            $lineTotal = $item['price'] * $qty;
            $newSelection[] = [
                'id'        => $item['id'],
                'name'      => $item['name'],
                'qty'       => $qty,
                'price'     => round($item['price'], 2),
                'sub_total' => round($lineTotal, 2),
                'total'     => round($lineTotal, 2),
            ];

            $findCombination($index + 1, $newCents, $newSelection);
            if ($result !== null) return;
        }
    };

//Begin search with first item, sum=0, empty list.it trigeeres the recursive tree
    $findCombination(0, 0, []);

/*If no match → error.
Else → build structured output.*/
    if ($result === null) {
        $logEnd('NO_EXACT_MATCH', 0);
        return [
            'error' => 'Cannot reach target exactly without discount',
            'debug' => ['target_amount' => $targetAmount]
        ];
    }

    $total = array_sum(array_column($result, 'sub_total'));

    $output = [
        'products' => $result,
        'discount_percent' => 0.0,
        'summary' => [
            'sub_total' => round($total, 2),
            'discount'  => 0.0,
            'total'     => round($total, 2),
            'target'    => round($targetAmount, 2)
        ]
    ];

    $logEnd('EXACT_MATCH', count($result));
    return $output;
}

    // ===================================================================
    // 3. DISCOUNT ENABLED → GREEDY + 10% OVERSHOOT
    // ===================================================================
    usort($items, fn($a, $b) => $b['price'] <=> $a['price']);

    $maxOvershoot = $targetAmount * 0.1;
    $maxAllowed   = $targetAmount + $maxOvershoot;
    $selected     = [];
    $current      = 0.0;

    foreach ($items as $item) {
        if ($current >= $maxAllowed) break;

        $affordable = (int)floor(($maxAllowed - $current) / $item['price']);
        $qty = min($affordable, $item['max_qty']);
        $qty = max(1, $qty);

        // Enforce digital: qty = 1 max
        if (!$item['can_add_quantity'] && $qty > 1) {
            $qty = 1;
        }

        $lineTotal = $item['price'] * $qty;

        if ($current + $lineTotal > $maxAllowed) {
            $qty = (int)floor(($maxAllowed - $current) / $item['price']);
            if ($qty < 1) continue;
            $lineTotal = $item['price'] * $qty;
        }

        $selected[] = [
            'id'        => $item['id'],
            'name'      => $item['name'],
            'qty'       => $qty,
            'price'     => round($item['price'], 2),
            'sub_total' => round($lineTotal, 2),
            'discount'  => 0.0,
            'total'     => round($lineTotal, 2),
        ];

        $current += $lineTotal;
    }

    // Fill small gap
    if ($current < $targetAmount && $current < $maxAllowed) {
        foreach (array_reverse($items) as $item) {
            if ($current + $item['price'] <= $maxAllowed) {
                $selected[] = [
                    'id'        => $item['id'],
                    'name'      => $item['name'],
                    'qty'       => 1,
                    'price'     => round($item['price'], 2),
                    'sub_total' => round($item['price'], 2),
                    'discount'  => 0.0,
                    'total'     => round($item['price'], 2),
                ];
                $current += $item['price'];
                break;
            }
        }
    }

    $total = array_sum(array_column($selected, 'sub_total'));

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
            'sub_total' => round($total + $discount, 2),
            'discount'  => $discount,
            'total'     => $total,
            'target'    => round($targetAmount, 2)
        ]
    ];

    $diff = abs($total - $targetAmount);
    if ($diff > 0.01) {
        $output['error'] = 'Used discount to match target';
        $output['debug'] = [
            'gross_total' => round($total + $discount, 2),
            'target'      => $targetAmount,
            'difference'  => round($diff, 2),
            'items'       => count($selected)
        ];
        $logEnd('PARTIAL_MATCH', count($selected));
    } else {
        $logEnd('SUCCESS', count($selected));
    }

    return $output;
}
}