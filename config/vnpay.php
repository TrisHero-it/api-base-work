<?php
return [
    'vnp_TmnCode'    => env('VNP_TMN_CODE', 'Mã_TMNCODE'),
    'vnp_HashSecret' => env('VNP_HASH_SECRET', 'Mã_HashSecret'),
    'vnp_Url'        => env('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),
    'vnp_ReturnUrl'  => env('VNP_RETURN_URL', 'http://localhost:8000/vnpay-return'),
];
