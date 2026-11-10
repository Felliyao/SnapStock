<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductProductionService
{
    public function produce(Product $product, int $quantity): void
    {
        $rawMaterials = $product->rawMaterials()
            ->orderBy('raw_materials.id')
            ->lockForUpdate()
            ->get();

        foreach ($rawMaterials as $rawMaterial) {
            $quantityPerProduct = $this->toMilliUnits((string) $rawMaterial->pivot->quantity_per_product);
            $availableStock = $this->toMilliUnits((string) $rawMaterial->stock);

            if ($quantityPerProduct === 0 || $quantity > intdiv($availableStock, $quantityPerProduct)) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$rawMaterial->name} tidak cukup untuk membuat {$quantity} {$product->unit}.",
                ]);
            }

            $requiredStock = $quantityPerProduct * $quantity;
            $rawMaterial->decrement('stock', $this->fromMilliUnits($requiredStock));
        }
    }

    private function toMilliUnits(string $quantity): int
    {
        [$whole, $fraction] = array_pad(explode('.', $quantity, 2), 2, '0');

        return ((int) $whole * 1000) + (int) str_pad(substr($fraction, 0, 3), 3, '0');
    }

    private function fromMilliUnits(int $quantity): string
    {
        return intdiv($quantity, 1000).'.'.str_pad((string) ($quantity % 1000), 3, '0', STR_PAD_LEFT);
    }
}
