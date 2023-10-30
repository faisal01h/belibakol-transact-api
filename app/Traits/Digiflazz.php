<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait Digiflazz
{
    public function digiflazzPriceList($mode, $code = null) {
        $payload = [
            "cmd" => $mode,
            "username" => env('DIGIFLAZZ_API_USERNAME'),
            "sign" => md5(env("DIGIFLAZZ_API_USERNAME").env("DIGIFLAZZ_API_KEY")."pricelist")
        ];
        if($code) {
            $payload["code"] = $code;
        }
        $http = Http::post('https://api.digiflazz.com/v1/price-list', $payload);
        return $http['data'];
    }

    public function digiflazzPurchase($sku, $destination, $referenceId, $test = true) {
        $payload = [
            "username" => env("DIGIFLAZZ_API_USERNAME"),
            "buyer_sku_code" => $sku,
            "customer_no" => $destination,
            "ref_id" => $referenceId,
            "sign" => md5(env("DIGIFLAZZ_API_USERNAME").env("DIGIFLAZZ_API_KEY").$referenceId),
            "testing" => $test,

        ];
        $http = Http::post('https://api.digiflazz.com/v1/transaction', $payload);
        if(isset($http['data']['rc'])) {
            return [
                "error" => 400,
                "dfe" => $http['data']['rc']
            ];
        }
        return $http['data'];
    }

    public function digiflazzPlnInquiry($destination) {
        $payload = [
            "commands" => "pln-subscribe",
            "customer_no" => $destination
        ];
        $http = Http::post('https://api.digiflazz.com/v1/transaction', $payload);
        if(isset($http['data']['rc'])) {
            return [
                "error" => 400,
                "dfe" => $http['data']['rc']
            ];
        }
        return $http['data'];
    }
}
