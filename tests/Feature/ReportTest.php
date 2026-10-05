<?php

use App\Models\Product;
use App\Models\Service;
use App\Services\ReportService;
use App\Services\TransactionService;

beforeEach(function () {
    $this->txService = app(TransactionService::class);
    $this->reportService = app(ReportService::class);

    $this->product = Product::create([
        'name' => 'Whiskas Pouch Salmon 85g',
        'sku' => 'PRD-WHISK-POUCH',
        'type' => 'Makanan',
        'unit' => 'pouch',
        'cost_price' => 5000,
        'selling_price' => 8000,
        'stock' => 50,
        'min_stock' => 5,
        'is_active' => true,
    ]);

    $this->service = Service::create([
        'code' => 'SRV-GROOM-01',
        'name' => 'Mandi Bersih Kucing',
        'price' => 40000,
        'cost_price' => 5000,
        'is_active' => true,
    ]);
});

test('generates dashboard summary accurately', function () {
    // Perform a sale
    $this->txService->createTransaction([
        'items' => [
            ['id' => $this->product->id, 'type' => 'product', 'quantity' => 5], // 5 * 8000 = 40.000, cost = 25.000
            ['id' => $this->service->id, 'type' => 'service', 'quantity' => 1], // 40.000, cost = 5.000
        ],
        'payment_method' => 'cash',
        'payment_amount' => 100000,
    ]);

    $summary = $this->reportService->getDashboardSummary();

    expect($summary['today_revenue'])->toBe(80000.0)
        ->and($summary['today_transactions_count'])->toBe(1)
        ->and($summary['today_gross_profit'])->toBe(50000.0); // 80.000 - 30.000 = 50.000
});

test('calculates sales report and best selling items', function () {
    $this->txService->createTransaction([
        'items' => [
            ['id' => $this->product->id, 'type' => 'product', 'quantity' => 10],
        ],
        'payment_method' => 'qris',
        'payment_amount' => 80000,
    ]);

    $todayStr = now()->format('Y-m-d');
    $report = $this->reportService->getSalesReport($todayStr, $todayStr);

    expect($report['total_transactions'])->toBe(1)
        ->and($report['total_revenue'])->toBe(80000.0)
        ->and($report['payment_breakdown']['qris']['total'])->toBe(80000.0);

    $bestProducts = $this->reportService->getBestSellingProducts($todayStr, $todayStr);
    expect($bestProducts)->toHaveCount(1)
        ->and((int) $bestProducts->first()->total_qty)->toBe(10);
});
