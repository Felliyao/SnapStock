<?php

namespace App\Http\Controllers;

use App\Services\TransactionCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        return view('transactions.index', [
            'transactions' => $request->user()->transactions()->with('items')->latest()->paginate(12),
            'transactionCount' => $request->user()->transactions()->count(),
            'totalRevenue' => $request->user()->transactions()->sum('total'),
        ]);
    }

    public function store(Request $request, TransactionCheckoutService $checkout): JsonResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'in:cash,qris,transfer'],
            'amount_paid' => ['required_if:payment_method,cash', 'nullable', 'integer', 'min:0', 'max:9999999999'],
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:product_id,quantity'],
            'items.*.product_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);

        $transaction = $checkout->complete(
            $request->user(),
            $validated['items'],
            $validated['payment_method'],
            isset($validated['amount_paid']) ? (int) $validated['amount_paid'] : null,
        );

        return response()->json([
            'message' => 'Transaksi berhasil disimpan.',
            'redirect' => route('transactions.index'),
            'transaction_id' => $transaction->id,
        ], 201);
    }

    public function destroy(Request $request, int $transaction): RedirectResponse
    {
        $user = $request->user();
        $transaction = $user->transactions()
            ->whereKey($transaction)
            ->with('items')
            ->firstOrFail();

        DB::transaction(function () use ($user, $transaction): void {
            $quantities = $transaction->items
                ->filter(fn ($item): bool => $item->product_id !== null)
                ->groupBy('product_id')
                ->map(fn ($items): int => (int) $items->sum('quantity'));

            if ($quantities->isNotEmpty()) {
                $products = $user->products()
                    ->whereKey($quantities->keys())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($products as $product) {
                    $quantity = $quantities->get($product->id, 0);

                    if ($quantity > 4294967295 - $product->stock) {
                        throw ValidationException::withMessages([
                            'transaction' => 'Stok produk tidak dapat dipulihkan karena melebihi kapasitas maksimum.',
                        ]);
                    }

                    $product->increment('stock', $quantity);
                }
            }

            $transaction->delete();
        });

        return redirect()->route('transactions.index')->with('status', 'Transaksi berhasil dihapus.');
    }
}
