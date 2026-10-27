<?php

/*
|--------------------------------------------------------------------------
| Payment gateways
|--------------------------------------------------------------------------
|
| Paid courses are bought through VNPay or MoMo. Leave a gateway's keys
| empty to hide it from the checkout page. The default URLs are the
| sandbox (test) environments; see docs/thanh-toan.md for how to get keys.
|
*/

return [

    'vnpay' => [
        'tmn_code' => env('VNPAY_TMN_CODE'),
        'hash_secret' => env('VNPAY_HASH_SECRET'),
        'url' => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    ],

    'momo' => [
        'partner_code' => env('MOMO_PARTNER_CODE'),
        'access_key' => env('MOMO_ACCESS_KEY'),
        'secret_key' => env('MOMO_SECRET_KEY'),
        'endpoint' => env('MOMO_ENDPOINT', 'https://test-payment.momo.vn/v2/gateway/api/create'),
        // captureWallet: pay with the MoMo app; payWithATM: domestic ATM card.
        'request_type' => env('MOMO_REQUEST_TYPE', 'captureWallet'),
    ],

    // true: also offer a fake gateway that marks the order paid at once (never turn on for real sales).
    'demo' => (bool) env('PAYMENT_DEMO', false),

    // Minutes the learner has to finish paying before the gateway expires the link.
    'expire_minutes' => 15,

];
