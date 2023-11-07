<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait Xendit
{
    public function xenditPayQris($amountIdr, $refId) {
        $url = "https://api.xendit.co/qr_codes";
        $auth = base64_encode(env("XENDIT_SECRET").":");
        $body = [
            "reference_id" => $refId,
            "currency" => "IDR",
            "amount" => $amountIdr,
            "type" => "DYNAMIC"
        ];
        $header = [
            "Authorization" => "Basic $auth",
            "api-version" => "2022-07-31"
        ];

        $http = Http::withHeaders($header)->post($url, $body);
        if($http->failed()) {
            abort(500, "Error creating payment. XAPI-25.".$http->status());
        }
        return [
            "reference_id" => $http['reference_id'],
            'type' => $http['type'],
            'currency' => $http['currency'],
            'amount' => $http['amount'],
            'expires_at' => $http['expires_at'],
            'metadata' => $http['metadata'],
            'channel_code' => $http['channel_code'],
            'business_id' => $http['business_id'],
            'id' => $http['id'],
            'created' => $http['created'],
            'updated' => $http['updated'],
            'qr_string' => $http['qr_string'],
            'status' => $http['status']
        ];
    }

    public function xenditCheckQris($id) {
        $url = "https://api.xendit.co/qr_codes/".$id;

        return [
            "status" => false
        ];
    }

    public function xenditCheckEwallet($charge_id) {

    }
}
