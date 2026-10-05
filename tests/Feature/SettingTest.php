<?php

use App\Models\Transaction;
use App\Services\StoreSettingService;

test('can retrieve default settings and update store settings', function () {
    $service = app(StoreSettingService::class);

    $settings = $service->getAll();
    expect($settings)->toBeArray()
        ->and($settings['store_name'])->toBe('Pet Care & Shop');

    $service->updateMany([
        'store_name' => 'Meow & Woof Pet Clinic & Shop',
        'receipt_paper_size' => '80mm',
        'tax_enabled' => '1',
        'tax_percentage' => '11',
    ]);

    expect($service->get('store_name'))->toBe('Meow & Woof Pet Clinic & Shop')
        ->and($service->get('receipt_paper_size'))->toBe('80mm')
        ->and($service->get('tax_percentage'))->toBe('11');
});

test('can clear all transactions via artisan command and web route', function () {
    Transaction::create([
        'transaction_number' => 'PET-20261005-0001',
        'transaction_date' => now(),
        'subtotal' => 100000,
        'grand_total' => 100000,
        'payment_method' => 'cash',
        'payment_amount' => 100000,
        'status' => 'completed',
    ]);

    expect(Transaction::count())->toBe(1);

    $response = $this->post(route('settings.clear-transactions'));
    $response->assertRedirect(route('settings.index'));
    $response->assertSessionHas('success');

    expect(Transaction::count())->toBe(0);
});
