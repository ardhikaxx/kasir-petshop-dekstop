<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Service;
use App\Services\ReceiptService;
use App\Services\StoreSettingService;
use App\Services\TransactionService;
use DomainException;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected StoreSettingService $settingService,
        protected ReceiptService $receiptService
    ) {}

    /**
     * Display POS Cashier register page.
     */
    public function index(): View
    {
        $categories = Category::all();
        $settings = $this->settingService->getAll();

        // Initial products and services loaded for immediate offline catalog use
        $products = Product::query()
            ->active()
            ->with('category')
            ->orderBy('name')
            ->get();

        $services = Service::query()
            ->active()
            ->with('category')
            ->orderBy('name')
            ->get();

        return view('pos.index', compact('categories', 'settings', 'products', 'services'));
    }

    /**
     * Search products and services for POS (supports name, SKU, and exact barcode match).
     */
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));
        $categoryId = $request->input('category_id');
        $itemType = $request->input('type', 'all'); // 'all', 'product', 'service'

        $products = collect();
        $services = collect();

        // 1. Direct barcode exact match lookup first (for barcode scanner)
        if ($request->filled('barcode')) {
            $barcode = trim($request->input('barcode'));
            $matchedProduct = Product::query()
                ->active()
                ->where('barcode', $barcode)
                ->first();

            return response()->json([
                'exact_match' => $matchedProduct ? [
                    'id' => $matchedProduct->id,
                    'type' => 'product',
                    'name' => $matchedProduct->name,
                    'sku' => $matchedProduct->sku,
                    'barcode' => $matchedProduct->barcode,
                    'unit' => $matchedProduct->unit,
                    'price' => (float) $matchedProduct->selling_price,
                    'stock' => $matchedProduct->stock,
                    'is_stock_available' => $matchedProduct->stock > 0,
                ] : null,
            ]);
        }

        // 2. Products query
        if ($itemType === 'all' || $itemType === 'product') {
            $prodQuery = Product::query()->active()->with('category');

            if ($categoryId) {
                $prodQuery->where('category_id', $categoryId);
            }

            if ($query !== '') {
                $prodQuery->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%");
                });
            }

            $products = $prodQuery->orderBy('name')->limit(50)->get()->map(function ($p) {
                return [
                    'id' => $p->id,
                    'type' => 'product',
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'barcode' => $p->barcode,
                    'unit' => $p->unit,
                    'price' => (float) $p->selling_price,
                    'stock' => $p->stock,
                    'category_name' => $p->category?->name ?? 'Umum',
                    'is_stock_available' => $p->stock > 0,
                    'stock_badge' => $p->stock_badge,
                ];
            });
        }

        // 3. Services query
        if ($itemType === 'all' || $itemType === 'service') {
            $srvQuery = Service::query()->active()->with('category');

            if ($categoryId) {
                $srvQuery->where('category_id', $categoryId);
            }

            if ($query !== '') {
                $srvQuery->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('code', 'like', "%{$query}%");
                });
            }

            $services = $srvQuery->orderBy('name')->limit(30)->get()->map(function ($s) {
                return [
                    'id' => $s->id,
                    'type' => 'service',
                    'name' => $s->name,
                    'sku' => $s->code,
                    'barcode' => null,
                    'unit' => 'layanan',
                    'price' => (float) $s->price,
                    'stock' => 999999,
                    'category_name' => $s->category?->name ?? 'Layanan',
                    'is_stock_available' => true,
                    'stock_badge' => ['label' => 'Jasa', 'class' => 'badge-info'],
                ];
            });
        }

        return response()->json([
            'products' => $products,
            'services' => $services,
        ]);
    }

    /**
     * Preview calculation for active cart items.
     */
    public function calculate(Request $request): JsonResponse
    {
        try {
            $items = $request->input('items', []);
            $discountType = $request->input('discount_type', 'none');
            $discountValue = (float) $request->input('discount_value', 0);
            $taxPercentage = $request->has('tax_percentage') ? (float) $request->input('tax_percentage') : null;

            if (empty($items)) {
                return response()->json([
                    'success' => true,
                    'subtotal' => 0,
                    'discount_amount' => 0,
                    'tax_amount' => 0,
                    'grand_total' => 0,
                ]);
            }

            $calculation = $this->transactionService->calculateCart(
                $items,
                $discountType,
                $discountValue,
                $taxPercentage
            );

            return response()->json([
                'success' => true,
                'data' => $calculation,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Process checkout transaction.
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        try {
            $transaction = $this->transactionService->createTransaction($request->validated());
            $receiptData = $this->receiptService->formatReceiptData($transaction);
            $plainTextReceipt = $this->receiptService->formatPlainText($transaction);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi kasir berhasil disimpan.',
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'grand_total' => (float) $transaction->grand_total,
                'payment_amount' => (float) $transaction->payment_amount,
                'change_amount' => (float) $transaction->change_amount,
                'payment_method' => $transaction->payment_method,
                'receipt_html' => view('receipts.thermal', compact('receiptData'))->render(),
                'receipt_text' => $plainTextReceipt,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses transaksi: '.$e->getMessage(),
            ], 500);
        }
    }
}
