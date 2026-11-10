<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', [ProductController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/products/create', [ProductController::class, 'create'])
    ->middleware(['auth', 'verified'])
    ->name('products.create');

Route::get('/products/ai-create', [ProductController::class, 'create'])
    ->middleware(['auth', 'verified'])
    ->name('products.ai-create');

Route::post('/products/ai-create/generate', [ProductController::class, 'generateAi'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])
    ->name('products.ai-generate');

Route::post('/products/ai-create/store', [ProductController::class, 'storeAiGenerated'])
    ->middleware(['auth', 'verified'])
    ->name('products.ai-store');

Route::post('/products', [ProductController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('products.store');

Route::get('/products/{product}/edit', [ProductController::class, 'edit'])
    ->middleware(['auth', 'verified'])
    ->name('products.edit');

Route::put('/products/{product}', [ProductController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('products.update');

Route::post('/products/stock', [ProductController::class, 'restock'])
    ->middleware(['auth', 'verified', 'throttle:30,1'])
    ->name('products.stock.store');

Route::post('/materials/stock', [RawMaterialController::class, 'restock'])
    ->middleware(['auth', 'verified', 'throttle:30,1'])
    ->name('materials.stock.store');

Route::post('/materials/restock-batch', [RawMaterialController::class, 'restockBatch'])
    ->middleware(['auth', 'verified', 'throttle:30,1'])
    ->name('materials.restock-batch');

Route::post('/materials/scan-receipt', [RawMaterialController::class, 'scanReceipt'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])
    ->name('materials.scan-receipt');

Route::post('/materials', [RawMaterialController::class, 'store'])
    ->middleware(['auth', 'verified'])
    ->name('materials.store');

Route::put('/materials/{rawMaterial}', [RawMaterialController::class, 'update'])
    ->middleware(['auth', 'verified'])
    ->name('materials.update');

Route::delete('/materials/{rawMaterial}', [RawMaterialController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('materials.destroy');

Route::middleware(['auth', 'verified'])->prefix('/products/import')->name('products.import.')->group(function () {
    Route::get('/', [ProductImportController::class, 'create'])->name('create');
    Route::get('/template', [ProductImportController::class, 'template'])->name('template');
    Route::post('/preview', [ProductImportController::class, 'preview'])->middleware('throttle:10,1')->name('preview');
    Route::post('/', [ProductImportController::class, 'store'])->middleware('throttle:10,1')->name('store');
});

Route::get('/pos', [ProductController::class, 'pos'])
    ->middleware(['auth', 'verified'])
    ->name('pos.index');

Route::get('/transactions', [TransactionController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('transactions.index');

Route::post('/transactions', [TransactionController::class, 'store'])
    ->middleware(['auth', 'verified', 'throttle:30,1'])
    ->name('transactions.store');

Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])
    ->middleware(['auth', 'verified'])
    ->name('transactions.destroy');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/catalogue', [ProductController::class, 'catalogue'])
    ->middleware(['auth', 'verified'])
    ->name('catalogue.index');

Route::get('/inventory', [RawMaterialController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('inventory.index');

require __DIR__.'/auth.php';
