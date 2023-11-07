<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Traits\Digiflazz;
use App\Traits\Xendit;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use Digiflazz, Xendit;

    public function priceList(Request $request) {
        $request->validate([
            'sku' => 'string',
        ]);
        // DO NOT FETCH DIRECTLY FROM DIGI FOR CLIENT INTERFACING PART
        // FETCH FROM DATABASE INSTEAD
        $products = Product::where('enabled', true)->whereRaw('max_price >= base_price')->whereRaw('discounted_price > base_price');
        if($request->sku) $products->where('sku', $request->sku);
        $products = $products->get();

        foreach($products as $product) {
            $product->category;
        }

        return response()->json([
            "products" => $products,
            "query" => [
                "sku" => $request->sku
            ]
        ]);
    }

    public function productDetails(Request $request, $sku) {
        $product = Product::where('sku', $sku)->first();
        if(!$product) {
            abort(404);
        }
        return response()->json([
            "data" => $product
        ]);
    }

    public function checkPln(Request $request) {
        $request->validate([
            "destination" => "required|string"
        ], [
            "destination" => "Nomor meteran harus diisi"
        ]);

        $pln = $this->digiflazzPlnInquiry($request->destination);
        return response()->json([
            "data" => $pln,
            "query" => [
                "destination" => $request->destination
            ]
        ], isset($pln['error']) ? $pln['error'] : 200);
    }

    public function productCategories(Request $request) {
        $request->validate([
            'name' => 'string'
        ]);
        $categories = Category::query();
        if($request->name) {
            $categories->where('name', 'LIKE', '%'.$request->name.'%');
        }
        $categories = $categories->get();

        return response()->json([
            "data" => $categories,
            "query" => [
                "name" => $request->name
            ]
        ]);
    }

    public function productByCategorySlug(Request $request, $slug) {
        $category = Category::where('slug', $slug)->first();
        if(!$category) {
            abort(404, "Category not found!");
        }
        $products = Product::where('category_id', $category->id)->orderBy('selling_price')->get();
        // if(count($products) === 0) {
        //     abort(404, "No product found under category ".$category->name);
        // }
        return response()->json([
            "data" => $products,
            "category" => $category
        ]);
    }

    public function createInvoice(Request $request) {
        // create transaction entry

        $request->validate([
            'sku' => 'required|string|exists:products,sku',
            'phone' => 'required|string',
            'destination' => 'required|string'
        ]);

        $product = Product::where('sku', $request->sku)->first();

        $transRecord = Transaction::create([
            'product_id' => $product->id,
            'user_identifier' => $request->phone,
            'destination' => $request->destination,
            'created_by' => 1,
            'invoice' => "BKL_".Str::ulid(),
            'ref_id' => Str::uuid(),
            'payment_method' => '',
            'source' => 'digiflazz',
            'base_price' => $product->base_price,
            'payment_method_fee' => 0,
            'selling_price' => $product->discounted_price,
            'status' => 'UNPAID',
            'remarks' => '-',
            'coupon_id' => null,
            'raw_json' => json_encode([
                "log" => [
                    [
                        "name" => "create-transaction",
                        "time" => now("+07:00")
                    ]
                ]
            ])
        ]);

        return response()->json([
            "data" => [
                "transaction" => $transRecord
            ]
        ]);
    }

    public function createPayment(Request $request) {
        $request->validate([
            'invoice' => 'required|string|exists:transactions,invoice',
            'payment_method' => 'required|string|in:xendit.qris,xendit.ewallet.dana'
        ]);

        $transRecord = Transaction::where('invoice', $request->invoice)->first();

        $payment = $this->xenditPayQris($transRecord->selling_price, $transRecord->ref_id);

        $logEntry = json_decode($transRecord->raw_json, true);
        array_push($logEntry['log'], [
            'name' => 'payment-'.$request->payment_method,
            'time' => now("+07:00"),
            'data' => $payment
        ]);
        $transRecord->raw_json = $logEntry;
        $transRecord->payment_method = $request->payment_method;
        $transRecord->save();

        return response()->json([
            "data" => $transRecord,
            "payment" => $payment
        ]);
    }

    public function checkPayment(Request $request) {
        $request->validate([
            'invoice' => 'required|exists:transactions,invoice'
        ]);

        $transRecord = Transaction::where('invoice', $request->invoice)->first();
        $transRecord->product;

        if(str_contains($transRecord->payment_method, 'xendit.qris')) {
            $logEntry = json_decode($transRecord->raw_json, true);
            foreach($logEntry['log'] as $log) {
                if($log['name'] === 'payment-'.$transRecord->payment_method) {
                    $response = $this->xenditCheckQris($log['data']['id']);
                    if($response['status'] === "SUCCEEDED" && $transRecord->status === "UNPAID") {
                        array_push($logEntry['log'], [
                            'name' => 'payment-success-'.$transRecord->payment_method,
                            'time' => now("+07:00"),
                            'data' => $response
                        ]);
                        $transRecord->status = "PENDING";
                        $transRecord->raw_json = $logEntry;
                        $transRecord->save();
                        $this->purchasePrepaid($transRecord->ref_id);
                    }
                    return response()->json([
                        "data" => [
                            "payment" => $response,
                            "transaction" => $transRecord
                        ]
                    ]);
                }
            }

        } else if(str_contains($transRecord->payment_method, 'xendit.ewallet')) {

        } else {
            return response()->json([
                "data" => [
                    "transaction" => $transRecord
                ]
            ]);
        }
    }

    private function purchasePrepaid($ref_id) {
        //execute on payment success
        $development = env('APP_ENV') === 'production' ? false : true;

        $transRecord = Transaction::where('ref_id', $ref_id)->first();
        if(!$transRecord) {
            abort(404, "Transaction Reference ID not found!");
        }

        // execute on payment success
        $transaction = $this->digiflazzPurchase($transRecord->sku, $transRecord->destination, $transRecord->ref_id, $development);
        $logEntry = json_decode($transRecord->raw_json, true);
        array_push($logEntry['log'], [
            'name' => 'digiflazz-inquiry',
            'time' => now("+07:00"),
            'data' => $transaction
        ]);
        $transRecord->raw_json = $logEntry;
        $transRecord->save();
        return;
        // return response()->json([
        //     "data" => $transRecord,
        //     // Carbon::parse(json_decode($transRecord->raw_json, true)['log'][0]['time'], "+07:00")->toDayDateTimeString()
        // ], isset($transaction['error']) ? $transaction['error'] : 201);
    }

}
