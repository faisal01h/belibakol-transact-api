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
        return $http;
    }

    public function xenditCheckQris($id) {

    }

    public function xenditCheckEwallet($charge_id) {

    }
}
