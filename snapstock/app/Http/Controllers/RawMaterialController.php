<?php

namespace App\Http\Controllers;

use App\Models\RawMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RawMaterialController extends Controller
{
    public function index(Request $request): View
    {
        $rawMaterials = $request->user()->rawMaterials()->orderBy('name')->get([
            'id',
            'name',
            'category',
            'stock',
            'unit',
            'location',
            'expiration_date',
            'created_at',
        ]);

        return view('inventory.index', [
            'rawMaterials' => $rawMaterials,
            'categories' => $rawMaterials
                ->map(fn (RawMaterial $rawMaterial): string => $rawMaterial->category ?: 'Lainnya')
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'unit' => ['required', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'expiration_date' => ['nullable', 'date'],
        ]);

        $request->user()->rawMaterials()->create($validated);

        return redirect()->route('inventory.index')->with('status', 'Stok bahan baku berhasil ditambahkan.');
    }

    public function update(Request $request, int $rawMaterial): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'unit' => ['required', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date'],
        ]);

        $request->user()->rawMaterials()->whereKey($rawMaterial)->firstOrFail()->update($validated);

        return redirect()->route('inventory.index')->with('status', 'Data bahan baku berhasil diperbarui.');
    }

    public function destroy(Request $request, int $rawMaterial): RedirectResponse
    {
        $request->user()->rawMaterials()->whereKey($rawMaterial)->firstOrFail()->delete();

        return redirect()->route('inventory.index')->with('status', 'Bahan baku berhasil dihapus.');
    }

    public function restock(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'raw_material_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'stock_action' => ['required', 'in:restock'],
            'expiration_date' => ['nullable', 'date'],
        ]);

        $material = DB::transaction(function () use ($request, $validated): RawMaterial {
            $material = $request->user()->rawMaterials()
                ->whereKey($validated['raw_material_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $quantity = (int) $validated['quantity'];

            if ($quantity > 4294967295 - $material->stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah restock melebihi kapasitas stok maksimum.',
                ]);
            }

            $material->increment('stock', $quantity);

            if (isset($validated['expiration_date'])) {
                $material->update(['expiration_date' => $validated['expiration_date']]);
            }

            return $material;
        });

        return redirect()->route('inventory.index')->with(
            'status',
            "Stok {$material->name} berhasil ditambah {$validated['quantity']} {$material->unit}. Stok sekarang {$material->stock} {$material->unit}."
        );
    }
}
