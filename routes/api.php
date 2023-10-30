<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::any('/', function(Request $request) {
    // sleep(5);
    return response()->json([
        "message" => "SwiftPay Transact API",
        "version" => env('APP_VER'),

    ], 200);
})->name('home');

Route::group([], function() {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::get('/products', [ProductController::class, 'priceList'])->name('products');
Route::group(["middleware" => "auth:sanctum"], function() {
    Route::get('/products/pln', [ProductController::class, 'checkPln'])->name('products.pln.check');
    Route::post('/products/buy', [ProductController::class, 'purchasePrepaid'])->name('products.buy');
});
