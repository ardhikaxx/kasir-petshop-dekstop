<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\BackupService;

beforeEach(function () {
    $this->backupService = app(BackupService::class);

    Category::create(['name' => 'Kucing', 'slug' => 'kucing', 'type' => 'product']);
    Product::create([
        'name' => 'Whiskas 1kg',
        'sku' => 'PRD-TEST-WHISKAS',
        'type' => 'Makanan',
        'unit' => 'pack',
        'cost_price' => 30000,
        'selling_price' => 45000,
        'stock' => 10,
        'min_stock' => 2,
    ]);
});

test('can export application data as valid JSON', function () {
    $json = $this->backupService->exportJson();
    $data = json_decode($json, true);

    expect($data)->toBeArray()
        ->and($data['app'])->toBe('Kasir Pet Shop Desktop')
        ->and($data['products'])->toHaveCount(1)
        ->and($data['categories'])->toHaveCount(1);
});

test('can export products and transactions as CSV', function () {
    $csvProducts = $this->backupService->exportCsv('products');
    expect($csvProducts)->toContain('Whiskas 1kg')
        ->and($csvProducts)->toContain('PRD-TEST-WHISKAS');
});

test('rejects restore if file is not a valid SQLite database', function () {
    $tempFile = tempnam(sys_get_temp_dir(), 'fake_backup');
    file_put_contents($tempFile, 'This is a corrupt text file, not SQLite.');

    try {
        $this->backupService->restoreDatabase($tempFile);
        $this->fail('Expected InvalidArgumentException was not thrown');
    } catch (InvalidArgumentException $e) {
        expect($e->getMessage())->toContain('bukan file SQLite yang valid');
    } finally {
        @unlink($tempFile);
    }
});
