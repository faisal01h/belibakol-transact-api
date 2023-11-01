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
    $payload = [
        "message" => env('APP_NAME'),
        "version" => env('APP_VER'),
    ];
    if($request->author) {
        $payload['author'] = [
            "name" => env('AUTHOR_NAME'),
            "email" => env('AUTHOR_EMAIL')
        ];
    }
    return response()->json($payload, 200);
})->name('home');

Route::group([], function() {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register'])->name('register');
});

Route::get('/products', [ProductController::class, 'priceList'])->name('products');
Route::get('/products/categories', [ProductController::class, 'productCategories'])->name('products.categories');
Route::get('/products/category/{slug}', [ProductController::class, 'productByCategorySlug'])->name('products.category');
Route::get('/products/pln', [ProductController::class, 'checkPln'])->name('products.pln.check');

Route::post('/checkout', [ProductController::class, 'createInvoice'])->name('transaction.buy');
Route::post('/transaction/pay', [ProductController::class, 'createPayment'])->name('transaction.pay');
Route::get('/transaction/check', [ProductController::class, 'checkPayment'])->name('transaction.check');

Route::group(["middleware" => "auth:sanctum"], function() {

});
