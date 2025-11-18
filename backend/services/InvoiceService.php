<?php
require_once __DIR__ . '/../models/Invoice.php';

class InvoiceService
{
    protected $invoiceModel;

    public function __construct()
    {
        // Initialize Invoice model for database operations
        $this->invoiceModel = new Invoice();
    }

    /**
     * ===============================================================
     * MAIN FUNCTION – PROCESS INVOICE
     * ===============================================================
     * Creates an invoice by:
     * 1. Validating customer data
     * 2. Generating product mix for target amount
     * 3. Inserting invoice + invoice items
     */
    public function processInvoice(array $payload): array
    {
        // Extract payload details
        $customerId       = $payload['customerId'];
        $date             = $payload['date'];
        $invoiceNumber    = $payload['invoiceNumber'];
        $targetAmount     = (float) $payload['amount'];
        $paymentMethod    = $payload['paymentMethod'] ?? 'cash';
        $userId           = $payload['created_by'] ?? null;
        $discountEnabled  = $payload['discount'] === 'false' ? false : true;
        $companyLogo      = $payload['company_logo'] ?? null;
        $email            = $payload['email'] ?? null;
        $address          = $payload['address'] ?? null;

        // --- Validate customer existence ---
        require_once __DIR__ . '/../models/Customer.php';
        $customer = Customer::find($customerId);
        if (!$customer) {
            throw new Exception("Customer not found with ID: $customerId");
        }

        // --- Fetch all products from DB ---
        $products = $this->invoiceModel->getAllProducts();

        // --- Generate best product mix for target amount ---
        $productMix = $this->generateProductMix($products, $targetAmount, $discountEnabled);

        // --- If algorithm fails, throw error ---
        if (isset($productMix['error'])) {
            throw new Exception($productMix['error']);
        }

        // --- Create main invoice entry in DB ---
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

        // --- Insert each product line into invoice_items table ---
        $this->invoiceModel->createInvoiceItems($invoiceId, $productMix['products']);

        // --- Return summary to caller ---
        return [
            'invoice_id' => $invoiceId,
            'products'   => $productMix['products'],
            'summary'    => $productMix['summary']
        ];
    }

    // Generate random invoice number (fallback)
    private function generateInvoiceNumber(): string
    {
        return 'INV-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * ===============================================================
     * MAIN ALGORITHM – GENERATE PRODUCT MIX
     * ===============================================================
     * Handles two modes:
     * (A) Discount Disabled → Exact match using backtracking
     * (B) Discount Enabled → Greedy algorithm + auto discount
     */
    public function generateProductMix(array $products, float $targetAmount, bool $enableDiscount = true): array
    {
        $startTime = microtime(true);

        echo "[generateProductMix] START | Target: $targetAmount | Items: " . count($products)
           . " | Discount: " . ($enableDiscount ? 'ON' : 'OFF') . "\n";

        // Closure to log end summary with time taken
        $logEnd = function (string $status, ?int $items = null) use ($startTime) {
            $duration = round((microtime(true) - $startTime) * 1000, 3);
            $msg = "[generateProductMix] END | Duration: {$duration}ms | Result: $status";
            if ($items !== null) $msg .= " | Items: $items";
            echo $msg . "\n";
        };

        // --- Step 1: Validate input ---
        if (empty($products) || $targetAmount <= 0) {
            $logEnd('ERROR (Invalid input)', 0);
            return [
                'error' => 'Invalid input: No products or target amount <= 0',
                'debug' => ['products_count' => count($products), 'target_amount' => $targetAmount]
            ];
        }

        // --- Step 2: Prepare items with tax computation ---
        $items = [];
        foreach ($products as $p) {
            $price = (float)($p['price'] ?? 0);
            if ($price <= 0) continue; // skip invalid products

            $taxRate = (float)($p['tax_rate'] ?? 0);
            $type = $p['product_type'] ?? 'physical';

            // Only physical products can have multiple quantities
            $canAddQuantity = ($type === 'physical');
            $maxQty = $canAddQuantity ? PHP_INT_MAX : 1;

            // Convert to cents to avoid float precision errors
            $priceCents = (int)round($price * 100);
            $unitTotalCents = (int)round($priceCents * (1 + $taxRate / 100));

            // Store formatted item data
            $items[] = [
                'id'               => $p['id'],
                'name'             => $p['name'],
                'price'            => $price,
                'price_cents'      => $priceCents,
                'tax_rate'         => $taxRate,
                'unit_total_cents' => $unitTotalCents,
                'max_qty'          => $maxQty,
                'can_add_quantity' => $canAddQuantity,
            ];
        }

        if (empty($items)) {
            $logEnd('ERROR (No valid products)', 0);
            return ['error' => 'No valid products', 'debug' => ['target_amount' => $targetAmount]];
        }

        // =====================================================================
        // MODE A: DISCOUNT DISABLED → NEED EXACT MATCH (Backtracking Search)
        // =====================================================================
        if (!$enableDiscount) {
    // Convert target amount to cents (paise) to avoid floating-point errors
    $targetCents = (int)round($targetAmount * 100);  // ₹1000 → 100000 cents

    // Sort products by using Spaceship Operator( HIGHEST to LOWEST price) → helps find solution faster 
    usort($items, fn($a, $b) => $b['price'] <=> $a['price']);

    $result   = null;                    // Will store the winning combination when found
    $maxDepth = count($items);           // Total number of products (e.g., 27)

    // RECURSIVE FUNCTION: Tries to build an invoice that exactly matches target
    $findCombination = function (
        int   $index,                    // Which product are we deciding now? (0 = first)
        int   $currentCents,             // How much total we have collected so far
        array $currentSelection          // Current list of selected items (the cart)
    ) use (
        &$findCombination,$items, $targetCents, &$result, $maxDepth
    ): void {

        // STOP EARLY: We already found a solution in another branch
        if ($result !== null) return;

        // JACKPOT! We hit exactly ₹1000.00 → save result and stop
        if ($currentCents === $targetCents) {
            $result = $currentSelection;
            return;
        }

        // DEAD END: No more products left OR we already overshot target
        if ($index >= $maxDepth || $currentCents > $targetCents) return;

        $item = $items[$index];  // Current product we're deciding about

        // HELPER FUNCTION: Adds given quantity and moves to next product
        $addItemAndContinue = function(int $qty) use (
            $findCombination, $index, $currentCents, $currentSelection,
            $item, $targetCents, &$result
        ) {
        

            // Calculate new total after adding this quantity
            $newCents = $currentCents + $item['unit_total_cents'] * $qty;

            // If adding this makes total > target → impossible → skip
            if ($newCents > $targetCents) return;

            // Calculate line item amounts (subtotal, tax, total)
            $lineSubTotal = round($item['price_cents'] * $qty / 100, 2);
            $lineTax      = round($lineSubTotal * ($item['tax_rate'] / 100), 2);

            // Create a new cart by adding this item
            $newSelection = $currentSelection;
            $newSelection[] = [
                'id'        => $item['id'],
                'name'      => $item['name'],
                'qty'       => $qty,
                'price'     => round($item['price'], 2),
                'sub_total' => $lineSubTotal,
                'tax'       => $lineTax,
                'total'     => round($lineSubTotal + $lineTax, 2),
            ];

            // RECURSIVE CALL: Now decide for the NEXT product
            $findCombination($index + 1, $newCents, $newSelection);

            // If solution found in deeper call → stop everything
            if ($result !== null) return;
        };

        // ——————————— MAIN LOGIC: Decide based on product type ———————————

        if ($item['can_add_quantity']) {
            // PHYSICAL PRODUCT: Can sell multiple quantities (like Pen, Mouse, etc.)
            $maxQty       = $item['max_qty'];  // Usually unlimited
            $maxPossible  = min($maxQty, (int)floor(($targetCents - $currentCents) / $item['unit_total_cents']));

            // Try from highest possible qty down to 0 → faster solution discovery
            for ($qty = $maxPossible; $qty >= 1; $qty--) {
                $addItemAndContinue($qty);
                if ($result !== null) return;  // Stop early if found
            }
        } else {
            // DIGITAL PRODUCT: Can only sell 0 or 1 (like license, course)
            $addItemAndContinue(1);  // Try including it
            $findCombination($index + 1, $currentCents, $currentSelection);
            if ($result !== null) return;  // Always try skipping it too
        }
    };

    // START THE SEARCH: Begin with first product, ₹0 total, empty cart
    $findCombination(0, 0, []);

    // If no exact combination found → return error
    if ($result === null) {
        $logEnd('NO_EXACT_MATCH', 0);
        return [
            'error' => 'Cannot reach target exactly without discount',
            'debug' => ['target_amount' => $targetAmount]
        ];
    }

    // SUCCESS: Calculate final totals from the winning combination
    $subTotal = array_sum(array_column($result, 'sub_total'));
    $taxTotal = array_sum(array_column($result, 'tax'));

    $output = [
        'products'         => $result,
        'discount_percent' => 0.0,
        'summary'          => [
            'sub_total' => round($subTotal, 2),
            'discount'  => 0.0,
            'tax'       => round($taxTotal, 2),
            'total'     => round($subTotal + $taxTotal, 2),
            'target'    => round($targetAmount, 2)
        ]
    ];

    $logEnd('EXACT_MATCH', count($result));
    return $output;
}
// =====================================================================
// MODE B: DISCOUNT ENABLED → Smart & Safe Product Selection
// =====================================================================
// =====================================================================
// MODE B: DISCOUNT ENABLED → MUST OVERSHOOT TARGET TO APPLY DISCOUNT
// =====================================================================

usort($items, fn($a, $b) => $b['price'] <=> $a['price']);

$maxAllowedItemPrice = $targetAmount * 3;
$buffer              = $targetAmount * 0.10;           // 10% buffer (was 5%)
$targetWithBuffer    = $targetAmount + $buffer;        // e.g., 150 → 165

$selected    = [];
$remaining   = $targetWithBuffer;
$priceGroups = [];

// Group items by price
foreach ($items as $item) {
    if ($item['price'] > $maxAllowedItemPrice) continue;

    $priceKey = number_format($item['price'], 2, '.', '');
    if (!isset($priceGroups[$priceKey])) $priceGroups[$priceKey] = [];
    $priceGroups[$priceKey][] = $item;
}

$availablePrices = array_keys($priceGroups);
sort($availablePrices, SORT_NUMERIC);

while ($remaining > 0.01 && !empty($availablePrices)) {
    $chosenPrice = null;

    // 1. Try largest item that fits
    foreach (array_reverse($availablePrices) as $p) {
        if ($p <= $remaining) {
            $chosenPrice = $p;
            break;
        }
    }

    // 2. If nothing fits — pick the SMALLEST item to keep adding
    if ($chosenPrice === null && !empty($availablePrices)) {
        $chosenPrice = $availablePrices[0]; // ← FORCE CONTINUE
    }

    if ($chosenPrice === null) break;

    $priceKey = number_format((float)$chosenPrice, 2, '.', '');
    $group    = $priceGroups[$priceKey];
    $chosenItem = $group[array_rand($group)];

    $qty = 1;
    if ($chosenItem['can_add_quantity']) {
        $maxQty = min($chosenItem['max_qty'], (int)floor($remaining / $chosenItem['price']));
        $qty = max(1, $maxQty);
    }

    $lineTotal = $chosenItem['price'] * $qty;

    $selected[] = [
        'id'        => $chosenItem['id'],
        'name'      => $chosenItem['name'],
        'qty'       => $qty,
        'price'     => round($chosenItem['price'], 2),
        'sub_total' => round($lineTotal, 2),
        'total'     => round($lineTotal, 2),
    ];

    $remaining -= $lineTotal;

    unset($priceGroups[$priceKey][array_key_first(array_slice($priceGroups[$priceKey], 0, 1))]);
    if (empty($priceGroups[$priceKey])) {
        unset($priceGroups[$priceKey]);
        $availablePrices = array_keys($priceGroups);
        sort($availablePrices, SORT_NUMERIC);
    }
}

// FINAL: Force overshoot — if still under target, add smallest item until we exceed
$grossTotal = array_sum(array_column($selected, 'sub_total'));

while ($grossTotal < $targetAmount && !empty($availablePrices)) {
    $smallestPrice = $availablePrices[0];
    $priceKey = number_format($smallestPrice, 2, '.', '');
    $group = $priceGroups[$priceKey] ?? [];
    if (empty($group)) break;

    $item = $group[array_rand($group)];

    $selected[] = [
        'id'        => $item['id'],
        'name'      => $item['name'],
        'qty'       => 1,
        'price'     => round($item['price'], 2),
        'sub_total' => round($item['price'], 2),
        'total'     => round($item['price'], 2),
    ];

    $grossTotal += $item['price'];
    unset($priceGroups[$priceKey][array_key_first($priceGroups[$priceKey])]);
    if (empty($priceGroups[$priceKey])) {
        unset($priceGroups[$priceKey]);
        $availablePrices = array_keys($priceGroups);
        sort($availablePrices, SORT_NUMERIC);
    }
}

// FINAL CALCULATION — NOW WE GUARANTEE DISCOUNT
$grossTotal = array_sum(array_column($selected, 'sub_total'));
$discount   = round($grossTotal - $targetAmount, 2);
$discount   = max(0, $discount);  // Always ≥ 0

$discountPercent = $grossTotal > 0 ? round(($discount / $grossTotal) * 100, 2) : 0;

$output = [
    'products' => $selected,
    'summary' => [
        'sub_total' => round($grossTotal, 2),
        'discount'  => round($discount, 2),
        'tax'       => 0.0,
        'total'     => round($targetAmount, 2),
        'target'    => round($targetAmount, 2)
    ]
];

$logEnd('SUCCESS', count($selected));
return $output;
    }
}