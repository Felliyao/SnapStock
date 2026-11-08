<?php

namespace App\Http\Controllers;

use App\Services\TransactionCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*' => ['required', 'array:product_id,quantity'],
            'items.*.product_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);

        $transaction = $checkout->complete($request->user(), $validated['items']);

        return response()->json([
            'message' => 'Transaksi berhasil disimpan.',
            'redirect' => route('transactions.index'),
            'transaction_id' => $transaction->id,
        ], 201);
    }
}
