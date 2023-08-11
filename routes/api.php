<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    Route::post('refresh', 'refresh');
});
Route::middleware(['auth:api'])->group(function () {
    Route::resource('products', ProductController::class)->only(['store', 'update']);
    Route::get('products/check-expiration/{unique_code}', [ProductController::class, 'checkExpiration']);
    Route::get('products/check-product/{unique_code}', [ProductController::class, 'checkProduct']);
});
