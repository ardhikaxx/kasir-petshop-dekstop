<?php

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
