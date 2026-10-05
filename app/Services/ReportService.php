<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Get summary metrics for the Dashboard.
     *
     * @return array<string, mixed>
     */
    public function getDashboardSummary(): array
    {
        $today = Carbon::today('Asia/Jakarta');

        $todayTransactions = Transaction::query()
            ->completed()
            ->whereDate('transaction_date', $today)
            ->get();

        $todayTotalRevenue = (float) $todayTransactions->sum('grand_total');
        $todayTransactionsCount = $todayTransactions->count();

        // Estimated Gross Profit: (grand_total - tax_amount) - total_cost
        $todayGrossProfit = 0.0;
        foreach ($todayTransactions as $tx) {
            $todayGrossProfit += $tx->estimated_gross_profit;
        }

        // Low stock and product counts
        $lowStockCount = Product::query()->active()->lowStock()->count();
        $outOfStockCount = Product::query()->active()->outOfStock()->count();
        $inStockCount = Product::query()->active()->inStock()->count();
        $serviceCount = Service::query()->active()->count();

        // Today's breakdown by payment method
        $paymentBreakdown = [
            'cash' => [
                'count' => $todayTransactions->where('payment_method', 'cash')->count(),
                'total' => (float) $todayTransactions->where('payment_method', 'cash')->sum('grand_total'),
            ],
            'qris' => [
                'count' => $todayTransactions->where('payment_method', 'qris')->count(),
                'total' => (float) $todayTransactions->where('payment_method', 'qris')->sum('grand_total'),
            ],
            'transfer' => [
                'count' => $todayTransactions->where('payment_method', 'transfer')->count(),
                'total' => (float) $todayTransactions->where('payment_method', 'transfer')->sum('grand_total'),
            ],
        ];

        // Recent 5 transactions
        $recentTransactions = Transaction::query()
            ->with('items')
            ->latest('transaction_date')
            ->limit(5)
            ->get();

        // Top 5 selling items this week
        $startOfWeek = Carbon::now('Asia/Jakarta')->startOfWeek();
        $topItems = TransactionItem::query()
            ->select('item_name', 'item_type', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(subtotal) as total_amount'))
            ->whereHas('transaction', function ($q) use ($startOfWeek) {
                $q->completed()->where('transaction_date', '>=', $startOfWeek);
            })
            ->groupBy('item_name', 'item_type')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'today_revenue' => $todayTotalRevenue,
            'today_transactions_count' => $todayTransactionsCount,
            'today_gross_profit' => round($todayGrossProfit, 2),
            'low_stock_count' => $lowStockCount,
            'out_of_stock_count' => $outOfStockCount,
            'in_stock_count' => $inStockCount,
            'service_count' => $serviceCount,
            'payment_breakdown' => $paymentBreakdown,
            'recent_transactions' => $recentTransactions,
            'top_items' => $topItems,
        ];
    }

    /**
     * Get Sales Report by Date Range or specific period.
     *
     * @return array<string, mixed>
     */
    public function getSalesReport(?string $startDate = null, ?string $endDate = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::today('Asia/Jakarta')->startOfMonth();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::today('Asia/Jakarta')->endOfDay();

        $transactions = Transaction::query()
            ->completed()
            ->whereBetween('transaction_date', [$start, $end])
            ->orderBy('transaction_date', 'asc')
            ->get();

        $totalTransactions = $transactions->count();
        $totalRevenue = (float) $transactions->sum('grand_total');
        $totalDiscount = (float) $transactions->sum('discount_amount');
        $totalTax = (float) $transactions->sum('tax_amount');
        $totalCost = (float) $transactions->sum('total_cost');

        $estimatedGrossProfit = 0.0;
        foreach ($transactions as $tx) {
            $estimatedGrossProfit += $tx->estimated_gross_profit;
        }

        $paymentBreakdown = [
            'cash' => [
                'count' => $transactions->where('payment_method', 'cash')->count(),
                'total' => (float) $transactions->where('payment_method', 'cash')->sum('grand_total'),
            ],
            'qris' => [
                'count' => $transactions->where('payment_method', 'qris')->count(),
                'total' => (float) $transactions->where('payment_method', 'qris')->sum('grand_total'),
            ],
            'transfer' => [
                'count' => $transactions->where('payment_method', 'transfer')->count(),
                'total' => (float) $transactions->where('payment_method', 'transfer')->sum('grand_total'),
            ],
        ];

        // Daily aggregated points for charts / tables
        $dailyPoints = [];
        $curr = $start->copy();
        while ($curr->lte($end)) {
            $dayKey = $curr->format('Y-m-d');
            $dayTxs = $transactions->filter(function ($tx) use ($dayKey) {
                return Carbon::parse($tx->transaction_date)->format('Y-m-d') === $dayKey;
            });

            $dailyPoints[] = [
                'date' => $dayKey,
                'label' => $curr->format('d M'),
                'count' => $dayTxs->count(),
                'revenue' => (float) $dayTxs->sum('grand_total'),
                'gross_profit' => round($dayTxs->sum(fn ($tx) => $tx->estimated_gross_profit), 2),
            ];

            $curr->addDay();
        }

        return [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
            'total_transactions' => $totalTransactions,
            'total_revenue' => $totalRevenue,
            'total_discount' => $totalDiscount,
            'total_tax' => $totalTax,
            'total_cost' => $totalCost,
            'estimated_gross_profit' => round($estimatedGrossProfit, 2),
            'payment_breakdown' => $paymentBreakdown,
            'daily_points' => $dailyPoints,
            'transactions' => $transactions,
        ];
    }

    /**
     * Get Best Selling Products report.
     *
     * @return Collection<int, mixed>
     */
    public function getBestSellingProducts(?string $startDate = null, ?string $endDate = null, int $limit = 20): Collection
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::today('Asia/Jakarta')->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::today('Asia/Jakarta')->endOfDay();

        return TransactionItem::query()
            ->select(
                'item_name',
                'sku',
                'product_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue'),
                DB::raw('SUM(cost_price * quantity) as total_cost')
            )
            ->where('item_type', 'product')
            ->whereHas('transaction', function ($q) use ($start, $end) {
                $q->completed()->whereBetween('transaction_date', [$start, $end]);
            })
            ->groupBy('item_name', 'sku', 'product_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $revenue = (float) $row->total_revenue;
                $cost = (float) $row->total_cost;
                $row->gross_profit = max(0, $revenue - $cost);

                return $row;
            });
    }

    /**
     * Get Best Selling Services report.
     *
     * @return Collection<int, mixed>
     */
    public function getBestSellingServices(?string $startDate = null, ?string $endDate = null, int $limit = 20): Collection
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::today('Asia/Jakarta')->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : Carbon::today('Asia/Jakarta')->endOfDay();

        return TransactionItem::query()
            ->select(
                'item_name',
                'sku',
                'service_id',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(subtotal) as total_revenue'),
                DB::raw('SUM(cost_price * quantity) as total_cost')
            )
            ->where('item_type', 'service')
            ->whereHas('transaction', function ($q) use ($start, $end) {
                $q->completed()->whereBetween('transaction_date', [$start, $end]);
            })
            ->groupBy('item_name', 'sku', 'service_id')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $revenue = (float) $row->total_revenue;
                $cost = (float) $row->total_cost;
                $row->gross_profit = max(0, $revenue - $cost);

                return $row;
            });
    }
}
