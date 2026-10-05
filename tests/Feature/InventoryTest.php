<?php

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;

beforeEach(function () {
    $this->product = Product::create([
        'name' => 'Pasir Gumpal Wangi 5L',
        'sku' => 'PRD-PASIR-01',
        'type' => 'Pasir',
        'unit' => 'karung',
        'cost_price' => 25000,
        'selling_price' => 38000,
        'stock' => 10,
        'min_stock' => 5,
        'is_active' => true,
    ]);
});

test('can record stock in and updates product stock with movement history', function () {
    $service = app(InventoryService::class);

    $movement = $service->recordStockIn(
        $this->product,
        15,
        24000,
        'INV-PO-202610-001',
        'Penerimaan stok dari supplier pasir'
    );

    expect($movement)->toBeInstanceOf(StockMovement::class)
        ->and($movement->type)->toBe('in')
        ->and($movement->quantity)->toBe(15)
        ->and($movement->before_stock)->toBe(10)
        ->and($movement->after_stock)->toBe(25);

    $this->product->refresh();
    expect($this->product->stock)->toBe(25)
        ->and((float) $this->product->cost_price)->toBe(24000.0);
});

test('rejects stock in with non-positive quantity', function () {
    $service = app(InventoryService::class);

    $service->recordStockIn($this->product, 0);
})->throws(InvalidArgumentException::class);

test('can record stock adjustment and logs physical opname history', function () {
    $service = app(InventoryService::class);

    $movement = $service->recordAdjustment(
        $this->product,
        8,
        'Barang Rusak / Bocor',
        '2 karung rusak saat bongkar muat'
    );

    expect($movement)->toBeInstanceOf(StockMovement::class)
        ->and($movement->type)->toBe('adjustment')
        ->and($movement->quantity)->toBe(-2)
        ->and($movement->before_stock)->toBe(10)
        ->and($movement->after_stock)->toBe(8);

    $this->product->refresh();
    expect($this->product->stock)->toBe(8);
});
