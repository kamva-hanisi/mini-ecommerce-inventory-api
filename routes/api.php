<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:auth');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::apiResource('products', ProductController::class);
    Route::post('/products/{product}/stock-in', [ProductController::class, 'stockIn'])
        ->name('products.stock-in');
    Route::post('/products/{product}/stock-out', [ProductController::class, 'stockOut'])
        ->name('products.stock-out');

    Route::apiResource('categories', CategoryController::class)
        ->only(['index', 'store']);
    Route::apiResource('suppliers', SupplierController::class)
        ->only(['index', 'store']);
});
