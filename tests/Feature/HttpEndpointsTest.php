<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;

beforeEach(function () {
    $this->category = Category::create(['name' => 'Kucing', 'slug' => 'kucing', 'type' => 'product']);
    $this->product = Product::create([
        'name' => 'Cat Food 1kg',
        'sku' => 'PRD-TEST-FOOD',
        'type' => 'Makanan',
        'unit' => 'kg',
        'cost_price' => 20000,
        'selling_price' => 30000,
        'stock' => 10,
        'min_stock' => 5,
    ]);
    $this->service = Service::create([
        'code' => 'SRV-TEST',
        'name' => 'Test Service',
        'price' => 25000,
        'cost_price' => 5000,
    ]);
});

test('all desktop web endpoints render successfully without authentication', function (string $uri) {
    $response = $this->get($uri);

    $response->assertStatus(200);
})->with([
    '/pos',
    '/dashboard',
    '/products',
    '/products/create',
    '/categories',
    '/inventory',
    '/services',
    '/services/create',
    '/transactions',
    '/reports',
    '/reports/bestsellers',
    '/settings',
    '/backup',
]);

test('can view product edit page', function () {
    $response = $this->get("/products/{$this->product->id}/edit");
    $response->assertStatus(200);
});

test('can view service edit page', function () {
    $response = $this->get("/services/{$this->service->id}/edit");
    $response->assertStatus(200);
});

test('can view thermal receipt page', function () {
    $tx = Transaction::create([
        'transaction_number' => 'PET-20261005-9999',
        'transaction_date' => now(),
        'subtotal' => 30000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 30000,
        'total_cost' => 20000,
        'payment_method' => 'cash',
        'payment_amount' => 50000,
        'change_amount' => 20000,
        'status' => 'completed',
    ]);

    $response = $this->get("/transactions/{$tx->id}/receipt");
    $response->assertStatus(200);

    $responseShow = $this->get("/transactions/{$tx->id}");
    $responseShow->assertStatus(200);
});
