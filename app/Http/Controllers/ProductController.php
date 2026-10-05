<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Display product catalog and inventory list.
     */
    public function index(Request $request): View
    {
        $query = Product::query()->with('category');

        // Search by keyword (name, SKU, barcode)
        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // Filter by stock status
        $status = $request->input('status', 'all');
        if ($status === 'low_stock') {
            $query->lowStock();
        } elseif ($status === 'out_of_stock') {
            $query->outOfStock();
        } elseif ($status === 'in_stock') {
            $query->inStock();
        }

        $products = $query->latest()->paginate(15)->withQueryString();
        $categories = Category::all();

        return view('products.index', compact('products', 'categories', 'status'));
    }

    /**
     * Show create product form.
     */
    public function create(): View
    {
        $categories = Category::all();
        $suggestedSku = $this->productService->generateSku();

        return view('products.create', compact('categories', 'suggestedSku'));
    }

    /**
     * Store newly created product.
     */
    public function store(ProductRequest $request): RedirectResponse
    {
        $product = $this->productService->create($request->validated());

        return redirect()->route('products.index')
            ->with('success', "Produk [{$product->name}] berhasil ditambahkan ke inventaris.");
    }

    /**
     * Show edit product form.
     */
    public function edit(Product $product): View
    {
        $categories = Category::all();

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update product.
     */
    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->productService->update($product, $request->validated());

        return redirect()->route('products.index')
            ->with('success', "Produk [{$product->name}] berhasil diperbarui.");
    }

    /**
     * Delete product safely.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $productName = $product->name;
        $this->productService->delete($product);

        return redirect()->route('products.index')
            ->with('success', "Produk [{$productName}] berhasil dihapus.");
    }

    /**
     * Toggle product active state.
     */
    public function toggle(Product $product): RedirectResponse
    {
        $updated = $this->productService->toggleActive($product);
        $stateLabel = $updated->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Produk [{$product->name}] berhasil {$stateLabel}.");
    }
}
