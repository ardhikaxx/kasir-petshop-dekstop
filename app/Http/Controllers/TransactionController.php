<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\ReceiptService;
use App\Services\TransactionService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected ReceiptService $receiptService
    ) {}

    /**
     * Display transaction history list with filters.
     */
    public function index(Request $request): View
    {
        $query = Transaction::query()->with('items');

        // Search by transaction number or customer/pet name
        if ($request->filled('q')) {
            $search = trim($request->input('q'));
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('pet_name', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->input('end_date'));
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $transactions = $query->latest('transaction_date')->paginate(15)->withQueryString();

        return view('transactions.index', compact('transactions'));
    }

    /**
     * Show transaction details.
     */
    public function show(Transaction $transaction): View
    {
        $transaction->load(['items.product', 'items.service']);
        $receiptData = $this->receiptService->formatReceiptData($transaction);
        $receiptText = $this->receiptService->formatPlainText($transaction);

        return view('transactions.show', compact('transaction', 'receiptData', 'receiptText'));
    }

    /**
     * Render receipt printable view (for thermal printer dialog).
     */
    public function receipt(Transaction $transaction): View
    {
        $receiptData = $this->receiptService->formatReceiptData($transaction);

        return view('receipts.thermal', compact('receiptData'));
    }

    /**
     * Get plain text receipt (for clipboard copy).
     */
    public function rawText(Transaction $transaction): JsonResponse
    {
        $text = $this->receiptService->formatPlainText($transaction);

        return response()->json([
            'success' => true,
            'text' => $text,
            'transaction_number' => $transaction->transaction_number,
        ]);
    }

    /**
     * Cancel transaction and reverse stock.
     */
    public function cancel(Request $request, Transaction $transaction): RedirectResponse
    {
        try {
            $reason = $request->input('cancel_reason', 'Pembatalan kasir');
            $this->transactionService->cancelTransaction($transaction, $reason);

            return back()->with('success', "Transaksi #{$transaction->transaction_number} berhasil dibatalkan dan stok dikembalikan.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
