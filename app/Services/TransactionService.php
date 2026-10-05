<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    /**
     * Generate unique transaction number: PET-YYYYMMDD-XXXX.
     */
    public function generateTransactionNumber(): string
    {
        $todayStr = Carbon::now('Asia/Jakarta')->format('Ymd');
        $prefix = "PET-{$todayStr}-";

        // Find last transaction for today
        $lastTransaction = Transaction::where('transaction_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastTransaction) {
            $parts = explode('-', $lastTransaction->transaction_number);
            $lastSeq = end($parts);
            if (is_numeric($lastSeq)) {
                $nextNumber = (int) $lastSeq + 1;
            }
        }

        $formatted = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$formatted}";
    }

    /**
     * Calculate cart breakdown from raw cart items safely using backend database prices.
     *
     * @param  array<int, array{id: int, type: string, quantity: int}>  $cartItems
     * @param  string  $discountType  'none'|'percent'|'fixed'
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     subtotal: float,
     *     discount_type: string,
     *     discount_value: float,
     *     discount_amount: float,
     *     tax_percentage: float,
     *     tax_amount: float,
     *     grand_total: float,
     *     total_cost: float
     * }
     */
    public function calculateCart(
        array $cartItems,
        string $discountType = 'none',
        float $discountValue = 0,
        ?float $taxPercentage = null
    ): array {
        if (empty($cartItems)) {
            throw new InvalidArgumentException('Keranjang belanja kasir masih kosong.');
        }

        $processedItems = [];
        $subtotal = 0.0;
        $totalCost = 0.0;

        foreach ($cartItems as $item) {
            $type = $item['type'] ?? 'product';
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $itemId = (int) ($item['id'] ?? 0);

            if ($type === 'product') {
                $product = Product::findOrFail($itemId);
                $unitPrice = (float) $product->selling_price;
                $costPrice = (float) $product->cost_price;
                $lineSubtotal = round($unitPrice * $qty, 2);
                $lineCost = round($costPrice * $qty, 2);

                $processedItems[] = [
                    'item_type' => 'product',
                    'product_id' => $product->id,
                    'service_id' => null,
                    'item_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'quantity' => $qty,
                    'discount' => 0.0,
                    'subtotal' => $lineSubtotal,
                    'available_stock' => $product->stock,
                ];

                $subtotal += $lineSubtotal;
                $totalCost += $lineCost;
            } elseif ($type === 'service') {
                $service = Service::findOrFail($itemId);
                $unitPrice = (float) $service->price;
                $costPrice = (float) $service->cost_price;
                $lineSubtotal = round($unitPrice * $qty, 2);
                $lineCost = round($costPrice * $qty, 2);

                $processedItems[] = [
                    'item_type' => 'service',
                    'product_id' => null,
                    'service_id' => $service->id,
                    'item_name' => $service->name,
                    'sku' => $service->code,
                    'unit_price' => $unitPrice,
                    'cost_price' => $costPrice,
                    'quantity' => $qty,
                    'discount' => 0.0,
                    'subtotal' => $lineSubtotal,
                    'available_stock' => null,
                ];

                $subtotal += $lineSubtotal;
                $totalCost += $lineCost;
            } else {
                throw new InvalidArgumentException("Tipe item [{$type}] tidak valid.");
            }
        }

        // Calculate discount
        $discountAmount = 0.0;
        $discountValue = max(0, $discountValue);

        if ($discountType === 'percent') {
            $clampedPercent = min(100, $discountValue);
            $discountAmount = round(($subtotal * $clampedPercent) / 100, 2);
        } elseif ($discountType === 'fixed') {
            $discountAmount = min($discountValue, $subtotal);
        } else {
            $discountType = 'none';
            $discountValue = 0.0;
        }

        $amountAfterDiscount = max(0, $subtotal - $discountAmount);

        // Tax calculation
        if ($taxPercentage === null) {
            $taxEnabled = Setting::get('tax_enabled', '0') === '1';
            $taxPercentage = $taxEnabled ? (float) Setting::get('tax_percentage', 0) : 0.0;
        }
        $taxPercentage = max(0, $taxPercentage);
        $taxAmount = round(($amountAfterDiscount * $taxPercentage) / 100, 2);

        $grandTotal = round($amountAfterDiscount + $taxAmount, 2);

        return [
            'items' => $processedItems,
            'subtotal' => round($subtotal, 2),
            'discount_type' => $discountType,
            'discount_value' => round($discountValue, 2),
            'discount_amount' => round($discountAmount, 2),
            'tax_percentage' => round($taxPercentage, 2),
            'tax_amount' => round($taxAmount, 2),
            'grand_total' => $grandTotal,
            'total_cost' => round($totalCost, 2),
        ];
    }

    /**
     * Process checkout transaction atomically.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $cartItems = $data['items'] ?? [];
            $discountType = $data['discount_type'] ?? 'none';
            $discountValue = (float) ($data['discount_value'] ?? 0);
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $paymentAmount = (float) ($data['payment_amount'] ?? 0);

            // Re-calculate cart from DB records
            $calculation = $this->calculateCart(
                $cartItems,
                $discountType,
                $discountValue,
                isset($data['tax_percentage']) ? (float) $data['tax_percentage'] : null
            );

            $grandTotal = $calculation['grand_total'];

            // Payment verification
            if ($paymentMethod === 'cash') {
                if ($paymentAmount < $grandTotal) {
                    $deficiency = $grandTotal - $paymentAmount;
                    throw new DomainException(
                        'Uang pembayaran tunai kurang Rp '.number_format($deficiency, 0, ',', '.').'.'
                    );
                }
                $changeAmount = round($paymentAmount - $grandTotal, 2);
            } else {
                // QRIS / Transfer: exact amount recorded
                $paymentAmount = $grandTotal;
                $changeAmount = 0.0;
            }

            // Create Transaction Record
            $transactionNumber = $this->generateTransactionNumber();
            $transactionDate = Carbon::now('Asia/Jakarta');

            $transaction = Transaction::create([
                'transaction_number' => $transactionNumber,
                'transaction_date' => $transactionDate,
                'customer_name' => $data['customer_name'] ?? null,
                'pet_name' => $data['pet_name'] ?? null,
                'pet_type' => $data['pet_type'] ?? null,
                'subtotal' => $calculation['subtotal'],
                'discount_type' => $calculation['discount_type'],
                'discount_value' => $calculation['discount_value'],
                'discount_amount' => $calculation['discount_amount'],
                'tax_percentage' => $calculation['tax_percentage'],
                'tax_amount' => $calculation['tax_amount'],
                'grand_total' => $grandTotal,
                'total_cost' => $calculation['total_cost'],
                'payment_method' => $paymentMethod,
                'payment_amount' => $paymentAmount,
                'change_amount' => $changeAmount,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            // Save items & deduct stock atomically with lock
            foreach ($calculation['items'] as $item) {
                if ($item['item_type'] === 'product') {
                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    $qtyToDeduct = $item['quantity'];

                    if ($product->stock < $qtyToDeduct) {
                        throw new DomainException(
                            "Stok tidak mencukupi untuk [{$product->name}]. Stok tersedia: {$product->stock}, diminta: {$qtyToDeduct}."
                        );
                    }

                    $beforeStock = $product->stock;
                    $afterStock = $beforeStock - $qtyToDeduct;
                    $product->stock = $afterStock;
                    $product->save();

                    // Record Stock Movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'sale',
                        'quantity' => -$qtyToDeduct,
                        'before_stock' => $beforeStock,
                        'after_stock' => $afterStock,
                        'cost_price' => $product->cost_price,
                        'reference_number' => $transaction->transaction_number,
                        'reference_type' => 'transaction',
                        'reference_id' => $transaction->id,
                        'notes' => "Penjualan kasir #{$transaction->transaction_number}",
                    ]);
                }

                TransactionItem::create([
                    'transaction_id' => $transaction->id,
                    'item_type' => $item['item_type'],
                    'product_id' => $item['product_id'],
                    'service_id' => $item['service_id'],
                    'item_name' => $item['item_name'],
                    'sku' => $item['sku'],
                    'cost_price' => $item['cost_price'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'discount' => $item['discount'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            return $transaction->load('items');
        });
    }

    /**
     * Cancel transaction and reverse stock.
     */
    public function cancelTransaction(Transaction $transaction, ?string $reason = null): Transaction
    {
        if ($transaction->status === 'cancelled') {
            throw new DomainException('Transaksi ini sudah dibatalkan sebelumnya.');
        }

        return DB::transaction(function () use ($transaction, $reason) {
            $transaction->load('items.product');

            foreach ($transaction->items as $item) {
                if ($item->item_type === 'product' && $item->product_id) {
                    $product = Product::lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $beforeStock = $product->stock;
                        $afterStock = $beforeStock + $item->quantity;
                        $product->stock = $afterStock;
                        $product->save();

                        StockMovement::create([
                            'product_id' => $product->id,
                            'type' => 'reversal',
                            'quantity' => $item->quantity,
                            'before_stock' => $beforeStock,
                            'after_stock' => $afterStock,
                            'cost_price' => $item->cost_price,
                            'reference_number' => $transaction->transaction_number,
                            'reference_type' => 'cancellation',
                            'reference_id' => $transaction->id,
                            'notes' => "Pengembalian stok pembatalan transaksi #{$transaction->transaction_number}".($reason ? " ({$reason})" : ''),
                        ]);
                    }
                }
            }

            $transaction->update([
                'status' => 'cancelled',
                'notes' => trim(($transaction->notes ?? '').' [Dibatalkan: '.($reason ?? 'Tidak ada alasan khusus').']'),
            ]);

            return $transaction->fresh('items');
        });
    }
}
