<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockInRequest;
use App\Models\Product;
use App\Services\InventoryService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    /**
     * Display stock movements history with filters.
     */
    public function index(Request $request): View
    {
        $filters = [
            'product_id' => $request->input('product_id'),
            'type' => $request->input('type'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];

        $movements = $this->inventoryService->getStockMovements($filters, 20);
        $products = Product::orderBy('name')->get();
        $alertProducts = $this->inventoryService->getAlertProducts(10);

        return view('inventory.index', compact('movements', 'products', 'alertProducts', 'filters'));
    }

    /**
     * Record stock in (Restock / Penerimaan Barang).
     */
    public function stockIn(StockInRequest $request): RedirectResponse
    {
        try {
            $product = Product::findOrFail($request->validated('product_id'));
            $quantity = (int) $request->validated('quantity');
            $costPrice = $request->filled('cost_price') ? (float) $request->validated('cost_price') : null;
            $refNumber = $request->validated('reference_number');
            $notes = $request->validated('notes');

            $movement = $this->inventoryService->recordStockIn(
                $product,
                $quantity,
                $costPrice,
                $refNumber,
                $notes
            );

            return redirect()->route('inventory.index')
                ->with('success', "Stok masuk sebanyak {$quantity} {$product->unit} untuk [{$product->name}] berhasil dicatat.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Record physical stock adjustment / correction.
     */
    public function adjust(StockAdjustmentRequest $request): RedirectResponse
    {
        try {
            $product = Product::findOrFail($request->validated('product_id'));
            $actualStock = (int) $request->validated('actual_stock');
            $reason = $request->validated('reason');
            $notes = $request->validated('notes');

            $movement = $this->inventoryService->recordAdjustment(
                $product,
                $actualStock,
                $reason,
                $notes
            );

            return redirect()->route('inventory.index')
                ->with('success', "Penyesuaian stok fisik [{$product->name}] berhasil diperbarui menjadi {$actualStock} {$product->unit}.");
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
