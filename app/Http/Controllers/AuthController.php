<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    public function login(Request $request) {
        $credentials = $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string'
        ]);

        if($request->user('sanctum')) {
            return response()->json([
                "message" => "You are already logged in. Use the refresh token endpoint if you want to refresh your token."
            ], 200);
        }

        if(Auth::attempt($credentials)) {
            $token = $request->user()->createToken('auth');

            return response()->json([
                "user" => $request->user(),
                "token" => $token->plainTextToken
            ]);
        }

        return response()->json([
            "message" => "Authorization error"
        ], 401);
    }

    public function register(Request $request) {
        $request->validate([
            'name' => 'required',
            'phone' => 'required|string|unique:'.Customer::class,
            'password' => ['required', 'string', Rules\Password::defaults()]
        ]);

        if(Customer::where('phone', $request->phone)->count() > 0) {
            return response()->json([
                "message" => "Account already exists!"
            ], 400);
        }

        $customer = Customer::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password)
        ]);

        if(Auth::login($customer)) {
            $token = $request->user()->createToken('auth');

            return response()->json([
                "user" => $customer,
                "token" => $token->plainTextToken
            ]);
        }
        return response()->json([
            "message" => "Cannot login"
        ], 500);
    }

    public function logout(Request $request) {

    }
}
