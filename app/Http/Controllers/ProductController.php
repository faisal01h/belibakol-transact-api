<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Traits\Digiflazz;
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
    use Digiflazz;

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

    public function purchasePrepaid(Request $request) {
        $request->validate([
            'sku' => 'required|exists:products,sku',
            'destination' => 'required|string',
            'phone' => 'required|string'
        ]);
        $development = env('APP_ENV') === 'production' ? false : true;
        $product = Product::where('sku', $request->sku)->first();
        $transRecord = Transaction::create([
            'product_id' => $product->id,
            'user_identifier' => $request->phone,
            'destination' => $request->destination,
            'created_by' => 1,
            'invoice' => "SP_".Str::ulid(),
            'ref_id' => Str::uuid(),
            'payment_method' => 'xendit.qris',
            'source' => 'digiflazz',
            'base_price' => $product->base_price,
            'payment_method_fee' => 0,
            'selling_price' => $product->discounted_price,
            'status' => 'PENDING',
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
        $transaction = $this->digiflazzPurchase($request->sku, $request->destination, $transRecord->ref_id, $development);
        return response()->json([
            "data" => $transaction,
            // Carbon::parse(json_decode($transRecord->raw_json, true)['log'][0]['time'], "+07:00")->toDayDateTimeString()
        ], isset($transaction['error']) ? $transaction['error'] : 201);
    }

}
