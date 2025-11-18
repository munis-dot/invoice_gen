<?php
require_once __DIR__ . '/../models/Invoice.php';

class InvoiceService
{
    protected $invoiceModel;

    public function __construct()
    {
        $this->invoiceModel = new Invoice();
    }

    public function processInvoice(array $payload): array
    {
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

        require_once __DIR__ . '/../models/Customer.php';
        $customer = Customer::find($customerId);
        if (!$customer) {
            throw new Exception("Customer not found with ID: $customerId");
        }

        $products = $this->invoiceModel->getAllProducts();
        $productMix = $this->generateProductMix($products, $targetAmount, $discountEnabled);

        if (isset($productMix['error'])) {
            throw new Exception($productMix['error']);
        }

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

        $this->invoiceModel->createInvoiceItems($invoiceId, $productMix['products']);

        return [
            'invoice_id' => $invoiceId,
            'products'   => $productMix['products'],
            'summary'    => $productMix['summary']
        ];
    }

    private function generateInvoiceNumber(): string
    {
        return 'INV-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    // ===================================================================
    // MAIN ALGORITHM – TAX ADDED ONLY
    // ===================================================================
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

        // === 1. PREPARE ITEMS WITH TAX ===
        $items = [];
        foreach ($products as $p) {
            $price = (float)($p['price'] ?? 0);
            if ($price <= 0) continue;

            $taxRate = (float)($p['tax_rate'] ?? 0); // NEW: tax rate per product
            $type = $p['product_type'] ?? 'physical';

            $canAddQuantity = ($type === 'physical');
            $maxQty = $canAddQuantity ? PHP_INT_MAX : 1;

            // NEW: Unit total in cents = price + tax
            $priceCents = (int)round($price * 100);
            $unitTotalCents = (int)round($priceCents * (1 + $taxRate / 100));

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

        // === 2. DISCOUNT DISABLED → EXACT MATCH (BACKTRACKING) ===
        if (!$enableDiscount) {
            $targetCents = (int)round($targetAmount * 100);

            usort($items, fn($a, $b) => $b['price'] <=> $a['price']);
            $result = null;
            $maxDepth = count($items);

            $findCombination = function (int $index, int $currentCents, array $currentSelection) use (
                &$findCombination, $items, $targetCents, &$result, $maxDepth
            ): void {
                if ($result !== null) return;
                if ($currentCents === $targetCents) {
                    $result = $currentSelection;
                    return;
                }
                if ($index >= $maxDepth || $currentCents > $targetCents) return;

                $item = $items[$index];
                $maxQty = $item['can_add_quantity'] ? $item['max_qty'] : 1;
                $maxPossibleQty = $item['can_add_quantity']
                    ? min($maxQty, (int)floor(($targetCents - $currentCents) / $item['unit_total_cents']))
                    : 1;

                for ($qty = $maxPossibleQty; $qty >= 0; $qty--) {
                    if ($qty === 0) {
                        $findCombination($index + 1, $currentCents, $currentSelection);
                        continue;
                    }

                    $newCents = $currentCents + $item['unit_total_cents'] * $qty;
                    if ($newCents > $targetCents) continue;

                    $lineSubTotal = round($item['price_cents'] * $qty / 100, 2);
                    $lineTax = round($lineSubTotal * ($item['tax_rate'] / 100), 2);

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

                    $findCombination($index + 1, $newCents, $newSelection);
                    if ($result !== null) return;
                }
            };

            $findCombination(0, 0, []);

            if ($result === null) {
                $logEnd('NO_EXACT_MATCH', 0);
                return [
                    'error' => 'Cannot reach target exactly without discount',
                    'debug' => ['target_amount' => $targetAmount]
                ];
            }

            $subTotal = array_sum(array_column($result, 'sub_total'));
            $taxTotal = array_sum(array_column($result, 'tax'));

            $output = [
                'products' => $result,
                'discount_percent' => 0.0,
                'summary' => [
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

        // === 3. DISCOUNT ENABLED → GREEDY + 10% OVERSHOOT ===
        usort($items, fn($a, $b) => $b['price'] <=> $a['price']);

        $maxOvershoot = $targetAmount * 0.1;
        $maxAllowed   = $targetAmount + $maxOvershoot;
        $selected     = [];
        $current      = 0.0;

        foreach ($items as $item) {
            if ($current >= $maxAllowed) break;

            $affordable = (int)floor(($maxAllowed - $current) / ($item['unit_total_cents'] / 100));
            $qty = min($affordable, $item['max_qty']);
            $qty = max(1, $qty);
            if (!$item['can_add_quantity'] && $qty > 1) $qty = 1;

            $lineTotalCents = $item['unit_total_cents'] * $qty;
            $lineTotalFloat = $lineTotalCents / 100;

            if ($current + $lineTotalFloat > $maxAllowed) {
                $qty = (int)floor(($maxAllowed - $current) / ($item['unit_total_cents'] / 100));
                if ($qty < 1) continue;
                $lineTotalCents = $item['unit_total_cents'] * $qty;
                $lineTotalFloat = $lineTotalCents / 100;
            }

            $lineSubTotal = round($item['price_cents'] * $qty / 100, 2);
            $lineTax = round($lineSubTotal * ($item['tax_rate'] / 100), 2);

            $selected[] = [
                'id'        => $item['id'],
                'name'      => $item['name'],
                'qty'       => $qty,
                'price'     => round($item['price'], 2),
                'sub_total' => $lineSubTotal,
                'tax'       => $lineTax,
                'total'     => round($lineSubTotal + $lineTax, 2),
            ];

            $current += $lineTotalFloat;
        }

        // Fill small gap
        if ($current < $targetAmount && $current < $maxAllowed) {
            foreach (array_reverse($items) as $item) {
                $unitTotalFloat = $item['unit_total_cents'] / 100;
                if ($current + $unitTotalFloat <= $maxAllowed) {
                    $lineSubTotal = $item['price'];
                    $lineTax = round($lineSubTotal * ($item['tax_rate'] / 100), 2);
                    $selected[] = [
                        'id'        => $item['id'],
                        'name'      => $item['name'],
                        'qty'       => 1,
                        'price'     => round($item['price'], 2),
                        'sub_total' => $lineSubTotal,
                        'tax'       => $lineTax,
                        'total'     => round($lineSubTotal + $lineTax, 2),
                    ];
                    $current += $unitTotalFloat;
                    break;
                }
            }
        }

        $subTotal = array_sum(array_column($selected, 'sub_total'));
        $taxTotal = array_sum(array_column($selected, 'tax'));
        $grossTotal = $subTotal + $taxTotal;

        $discount = 0.0;
        $discountPercent = 0.0;

        if ($grossTotal > $targetAmount) {
            $discount = round($grossTotal - $targetAmount, 2);
            $discountPercent = $grossTotal > 0 ? round(($discount / $grossTotal) * 100, 4) : 0;
            $grossTotal = round($targetAmount, 2);
        }

        $output = [
            'products' => $selected,
            'discount_percent' => $discountPercent,
            'summary' => [
                'sub_total' => round($subTotal, 2),
                'discount'  => $discount,
                'tax'       => round($taxTotal, 2),
                'total'     => $grossTotal,
                'target'    => round($targetAmount, 2)
            ]
        ];

        $diff = abs($grossTotal - $targetAmount);
        if ($diff > 0.01) {
            $output['error'] = 'Used discount to match target';
            $output['debug'] = [
                'gross_total' => round($subTotal + $taxTotal, 2),
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