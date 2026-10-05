<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Display sales reports with date filter and metrics.
     */
    public function index(Request $request): View
    {
        $startDate = $request->input('start_date', Carbon::today('Asia/Jakarta')->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::today('Asia/Jakarta')->format('Y-m-d'));

        $report = $this->reportService->getSalesReport($startDate, $endDate);
        $bestProducts = $this->reportService->getBestSellingProducts($startDate, $endDate, 5);
        $bestServices = $this->reportService->getBestSellingServices($startDate, $endDate, 5);

        return view('reports.index', compact('report', 'bestProducts', 'bestServices', 'startDate', 'endDate'));
    }

    /**
     * Display dedicated best sellers report (products vs services).
     */
    public function bestSellers(Request $request): View
    {
        $period = $request->input('period', 'month'); // 'today', 'week', 'month', 'custom'

        $now = Carbon::today('Asia/Jakarta');
        if ($period === 'today') {
            $start = $now->copy()->format('Y-m-d');
            $end = $now->copy()->format('Y-m-d');
        } elseif ($period === 'week') {
            $start = $now->copy()->startOfWeek()->format('Y-m-d');
            $end = $now->copy()->endOfWeek()->format('Y-m-d');
        } elseif ($period === 'month') {
            $start = $now->copy()->startOfMonth()->format('Y-m-d');
            $end = $now->copy()->endOfMonth()->format('Y-m-d');
        } else {
            $start = $request->input('start_date', $now->copy()->startOfMonth()->format('Y-m-d'));
            $end = $request->input('end_date', $now->copy()->format('Y-m-d'));
        }

        $products = $this->reportService->getBestSellingProducts($start, $end, 20);
        $services = $this->reportService->getBestSellingServices($start, $end, 20);

        return view('reports.bestsellers', compact('products', 'services', 'period', 'start', 'end'));
    }
}
