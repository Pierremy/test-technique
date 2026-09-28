<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {
    return response()->json(['status' => 'ok']);
});

Route::get('/categories', [CategoryController::class, 'index']);

Route::apiResource('products', ProductController::class);
