<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionCheckoutService
{
    private const MAX_TOTAL_CENTS = 999_999_999_999;

    /**
     * @param  array<int, array{product_id: int, quantity: int}>  $items
     */
    public function complete(User $user, array $items): Transaction
    {
        return DB::transaction(function () use ($user, $items): Transaction {
            $productIds = collect($items)
                ->pluck('product_id')
                ->map(fn (int|string $productId): int => (int) $productId)
                ->sort()
                ->values();
            $products = $user->products()
                ->whereKey($productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $productIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Keranjang berisi produk yang tidak tersedia.',
                ]);
            }

            $totalCents = 0;
            $transactionItems = [];

            foreach ($items as $index => $item) {
                $product = $products->get((int) $item['product_id']);
                $quantity = (int) $item['quantity'];

                if ($quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        "items.{$index}.quantity" => "Stok {$product->name} tidak mencukupi. Sisa stok {$product->stock} {$product->unit}.",
                    ]);
                }

                $unitPriceCents = $this->toCents($product->price);

                if ($unitPriceCents > intdiv(self::MAX_TOTAL_CENTS, $quantity)) {
                    throw ValidationException::withMessages([
                        'items' => 'Total transaksi melebihi batas maksimum.',
                    ]);
                }

                $subtotalCents = $unitPriceCents * $quantity;

                if ($totalCents > self::MAX_TOTAL_CENTS - $subtotalCents) {
                    throw ValidationException::withMessages([
                        'items' => 'Total transaksi melebihi batas maksimum.',
                    ]);
                }

                $totalCents += $subtotalCents;
                $transactionItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit' => $product->unit,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $this->fromCents($subtotalCents),
                ];
            }

            $transaction = $user->transactions()->create([
                'total' => $this->fromCents($totalCents),
                'status' => 'completed',
            ]);
            $transaction->items()->createMany($transactionItems);

            foreach ($items as $item) {
                $products->get((int) $item['product_id'])->decrement('stock', (int) $item['quantity']);
            }

            return $transaction;
        });
    }

    private function toCents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '0');

        return ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
    }

    private function fromCents(int $amount): string
    {
        return intdiv($amount, 100).'.'.str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
