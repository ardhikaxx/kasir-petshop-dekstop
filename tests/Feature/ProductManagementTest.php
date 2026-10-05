<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ProductService;

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'Makanan Kucing',
        'slug' => 'makanan-kucing',
        'type' => 'product',
    ]);
});

test('can create a product with initial stock and records initial movement', function () {
    $service = app(ProductService::class);

    $product = $service->create([
        'category_id' => $this->category->id,
        'name' => 'Whiskas Tuna 1kg',
        'sku' => 'PRD-WHISK-01',
        'barcode' => '8991234567890',
        'type' => 'Makanan',
        'unit' => 'pack',
        'cost_price' => 35000,
        'selling_price' => 45000,
        'stock' => 15,
        'min_stock' => 5,
        'is_active' => true,
    ]);

    expect($product)->toBeInstanceOf(Product::class)
        ->and($product->name)->toBe('Whiskas Tuna 1kg')
        ->and($product->stock)->toBe(15);

    $movement = StockMovement::where('product_id', $product->id)->first();
    expect($movement)->not->toBeNull()
        ->and($movement->type)->toBe('initial')
        ->and($movement->quantity)->toBe(15)
        ->and($movement->after_stock)->toBe(15);
});

test('can update product attributes and records adjustment if stock changed', function () {
    $service = app(ProductService::class);

    $product = $service->create([
        'category_id' => $this->category->id,
        'name' => 'Me-O Cat 1kg',
        'type' => 'Makanan',
        'unit' => 'pack',
        'cost_price' => 30000,
        'selling_price' => 40000,
        'stock' => 10,
        'min_stock' => 5,
    ]);

    $updated = $service->update($product, [
        'name' => 'Me-O Salmon 1kg',
        'stock' => 14,
    ]);

    expect($updated->name)->toBe('Me-O Salmon 1kg')
        ->and($updated->stock)->toBe(14);

    $adjMovement = StockMovement::where('product_id', $product->id)
        ->where('type', 'adjustment')
        ->first();

    expect($adjMovement)->not->toBeNull()
        ->and($adjMovement->quantity)->toBe(4)
        ->and($adjMovement->after_stock)->toBe(14);
});

test('detects low stock and out of stock conditions', function () {
    $service = app(ProductService::class);

    $pInStock = $service->create([
        'name' => 'Item In Stock',
        'type' => 'Makanan',
        'unit' => 'pcs',
        'cost_price' => 10000,
        'selling_price' => 15000,
        'stock' => 20,
        'min_stock' => 5,
    ]);

    $pLowStock = $service->create([
        'name' => 'Item Low Stock',
        'type' => 'Makanan',
        'unit' => 'pcs',
        'cost_price' => 10000,
        'selling_price' => 15000,
        'stock' => 3,
        'min_stock' => 5,
    ]);

    $pOutOfStock = $service->create([
        'name' => 'Item Out of Stock',
        'type' => 'Makanan',
        'unit' => 'pcs',
        'cost_price' => 10000,
        'selling_price' => 15000,
        'stock' => 0,
        'min_stock' => 5,
    ]);

    expect($pInStock->isLowStock())->toBeFalse()
        ->and($pInStock->isOutOfStock())->toBeFalse()
        ->and($pLowStock->isLowStock())->toBeTrue()
        ->and($pLowStock->isOutOfStock())->toBeFalse()
        ->and($pOutOfStock->isOutOfStock())->toBeTrue();
});

test('can toggle product active state', function () {
    $service = app(ProductService::class);

    $product = $service->create([
        'name' => 'Mainan Bola Kucing',
        'type' => 'Mainan',
        'unit' => 'pcs',
        'cost_price' => 5000,
        'selling_price' => 12000,
        'stock' => 10,
        'min_stock' => 2,
        'is_active' => true,
    ]);

    $service->toggleActive($product);
    expect($product->fresh()->is_active)->toBeFalse();

    $service->toggleActive($product);
    expect($product->fresh()->is_active)->toBeTrue();
});
