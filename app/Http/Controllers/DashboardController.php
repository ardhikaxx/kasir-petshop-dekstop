<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Services\ReportService;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display offline pet shop dashboard.
     */
    public function index(): View
    {
        $summary = $this->reportService->getDashboardSummary();
        $alertProducts = $this->inventoryService->getAlertProducts(8);

        return view('dashboard.index', compact('summary', 'alertProducts'));
    }
}
