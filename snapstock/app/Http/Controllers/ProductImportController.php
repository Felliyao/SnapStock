<?php

namespace App\Http\Controllers;

use App\Services\ProductCsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportController extends Controller
{
    public function create(): RedirectResponse
    {
        return redirect()->route('products.create');
    }

    public function template(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, ['name', 'category', 'description', 'price', 'stock', 'unit'], ',', '"', '');
            fclose($stream);
        }, 'products-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function preview(Request $request, ProductCsvImportService $importer): View
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'extensions:csv', 'max:2048'],
        ]);
        $rows = $importer->preview($validated['file'], $request->user());

        return view('products.create', [
            'rows' => $rows,
            'invalidCount' => collect($rows)->filter(fn (array $row): bool => $row['errors'] !== [])->count(),
        ]);
    }

    public function store(Request $request, ProductCsvImportService $importer): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'products' => ['required', 'array', 'min:1', 'max:500'],
            ]);
            $counts = $importer->import($request->user(), $validated['products']);
        } catch (ValidationException $exception) {
            return redirect()->route('products.create')->withErrors($exception->errors());
        }

        return redirect()->route('dashboard')->with(
            'status',
            "Impor selesai: {$counts['created']} produk ditambahkan, {$counts['updated']} diperbarui, {$counts['unchanged']} tidak berubah."
        );
    }
}
