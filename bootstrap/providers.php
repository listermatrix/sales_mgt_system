<?php


return [
    App\Providers\AppServiceProvider::class,
    App\Services\Customer\Providers\CustomerServiceProvider::class,
    App\Services\Product\Providers\ProductServiceProvider::class,
    App\Services\Order\Providers\OrderServiceProvider::class,
    App\Services\Payment\Providers\PaymentServiceProvider::class,
];
