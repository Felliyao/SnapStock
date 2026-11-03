<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [ProductController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/products/ai-create', [ProductController::class, 'aiCreate'])
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

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
