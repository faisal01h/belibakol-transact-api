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

class TransactionController extends Controller
{
    public function track(Request $request, $query) {

        $serialization = [
            'invoice',
            'destination',
            'user_identifier',
            'selling_price',
            'payment_method',
            'status',
            'created_at',
            'product_id'
            // '*'
        ];
        $transactions = Transaction::where('user_identifier', $query)->orWhere('invoice', $query)->orderByDesc('created_at')->get($serialization);
        foreach($transactions as $transaction) {
            $transaction->product;
        }

        return response()->json($transactions);
    }
}
