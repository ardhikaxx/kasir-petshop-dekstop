<?php

use App\Models\Product;
use App\Models\Service;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Services\TransactionService;

beforeEach(function () {
    $this->txService = app(TransactionService::class);

    $this->product = Product::create([
        'name' => 'Royal Canin Mother & Babycat 400g',
        'sku' => 'PRD-RC-BABY',
        'type' => 'Makanan',
        'unit' => 'pack',
        'cost_price' => 50000,
        'selling_price' => 68000,
        'stock' => 10,
        'min_stock' => 3,
        'is_active' => true,
    ]);

    $this->service = Service::create([
        'code' => 'SRV-TEST-01',
        'name' => 'Mandi Sehat Kucing',
        'price' => 50000,
        'cost_price' => 10000,
        'estimated_duration' => '45 Menit',
        'is_active' => true,
    ]);
});

test('generates transaction number in correct PET-YYYYMMDD-XXXX format', function () {
    $txNumber = $this->txService->generateTransactionNumber();
    $todayStr = now()->format('Ymd');

    expect($txNumber)->toMatch("/^PET-{$todayStr}-\\d{4}$/");
});

test('accurately calculates cart with percentage discount and tax', function () {
    $items = [
        ['id' => $this->product->id, 'type' => 'product', 'quantity' => 2], // 2 * 68.000 = 136.000
        ['id' => $this->service->id, 'type' => 'service', 'quantity' => 1], // 1 * 50.000 = 50.000
    ];

    // Subtotal = 186.000
    // Discount 10% = 18.600 -> After disc = 167.400
    // Tax 11% = 18.414 -> Grand total = 185.814
    $calc = $this->txService->calculateCart($items, 'percent', 10, 11);

    expect($calc['subtotal'])->toBe(186000.0)
        ->and($calc['discount_amount'])->toBe(18600.0)
        ->and($calc['tax_amount'])->toBe(18414.0)
        ->and($calc['grand_total'])->toBe(185814.0);
});

test('accurately calculates cart with nominal fixed discount', function () {
    $items = [
        ['id' => $this->product->id, 'type' => 'product', 'quantity' => 1], // 68.000
    ];

    $calc = $this->txService->calculateCart($items, 'fixed', 8000, 0);

    expect($calc['subtotal'])->toBe(68000.0)
        ->and($calc['discount_amount'])->toBe(8000.0)
        ->and($calc['grand_total'])->toBe(60000.0);
});

test('can execute mixed checkout with product and service, deducting stock atomically', function () {
    $data = [
        'items' => [
            ['id' => $this->product->id, 'type' => 'product', 'quantity' => 3],
            ['id' => $this->service->id, 'type' => 'service', 'quantity' => 1],
        ],
        'discount_type' => 'none',
        'discount_value' => 0,
        'tax_percentage' => 0,
        'payment_method' => 'cash',
        'payment_amount' => 300000,
        'customer_name' => 'Budi Santoso',
        'pet_name' => 'Kimi',
        'pet_type' => 'Kucing Anggora',
    ];

    // 3 * 68.000 + 50.000 = 254.000
    // Pay 300.000 -> Change = 46.000
    $tx = $this->txService->createTransaction($data);

    expect($tx)->toBeInstanceOf(Transaction::class)
        ->and((float) $tx->grand_total)->toBe(254000.0)
        ->and((float) $tx->change_amount)->toBe(46000.0)
        ->and($tx->items)->toHaveCount(2);

    // Verify product stock deduction: 10 - 3 = 7
    $this->product->refresh();
    expect($this->product->stock)->toBe(7);

    // Verify stock movement of type 'sale'
    $movement = StockMovement::where('product_id', $this->product->id)
        ->where('type', 'sale')
        ->first();

    expect($movement)->not->toBeNull()
        ->and($movement->quantity)->toBe(-3)
        ->and($movement->before_stock)->toBe(10)
        ->and($movement->after_stock)->toBe(7);
});

test('rejects checkout when product stock is insufficient', function () {
    $data = [
        'items' => [
            ['id' => $this->product->id, 'type' => 'product', 'quantity' => 15], // stock only 10
        ],
        'payment_method' => 'cash',
        'payment_amount' => 2000000,
    ];

    $this->txService->createTransaction($data);
})->throws(DomainException::class);

test('rejects cash checkout when payment amount is less than grand total', function () {
    $data = [
        'items' => [
            ['id' => $this->product->id, 'type' => 'product', 'quantity' => 1], // 68.000
        ],
        'payment_method' => 'cash',
        'payment_amount' => 50000, // less than 68.000
    ];

    $this->txService->createTransaction($data);
})->throws(DomainException::class);

test('records QRIS and Transfer payment methods accurately', function () {
    $dataQris = [
        'items' => [
            ['id' => $this->product->id, 'type' => 'product', 'quantity' => 1],
        ],
        'payment_method' => 'qris',
        'payment_amount' => 68000,
    ];

    $tx = $this->txService->createTransaction($dataQris);
    expect($tx->payment_method)->toBe('qris')
        ->and((float) $tx->change_amount)->toBe(0.0);

    $dataTransfer = [
        'items' => [
            ['id' => $this->service->id, 'type' => 'service', 'quantity' => 1],
        ],
        'payment_method' => 'transfer',
        'payment_amount' => 50000,
    ];

    $tx2 = $this->txService->createTransaction($dataTransfer);
    expect($tx2->payment_method)->toBe('transfer')
        ->and((float) $tx2->change_amount)->toBe(0.0);
});

test('can cancel transaction and reverses product stock with reversal movement', function () {
    $tx = $this->txService->createTransaction([
        'items' => [
            ['id' => $this->product->id, 'type' => 'product', 'quantity' => 4],
        ],
        'payment_method' => 'cash',
        'payment_amount' => 300000,
    ]);

    expect($this->product->fresh()->stock)->toBe(6);

    $cancelled = $this->txService->cancelTransaction($tx, 'Pelanggan membatalkan pesanan');

    expect($cancelled->status)->toBe('cancelled')
        ->and($this->product->fresh()->stock)->toBe(10);

    $reversalMovement = StockMovement::where('product_id', $this->product->id)
        ->where('type', 'reversal')
        ->first();

    expect($reversalMovement)->not->toBeNull()
        ->and($reversalMovement->quantity)->toBe(4)
        ->and($reversalMovement->after_stock)->toBe(10);
});
