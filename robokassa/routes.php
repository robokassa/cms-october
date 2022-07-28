<?php

use Robokassa\Robokassa\Classes\Helper\PaymentGateway;

Route::post(PaymentGateway::NOTIFICATION_URL, function () {
    $obPaymentGateway = new PaymentGateway();
    return $obPaymentGateway->processAnswerRequest();
});

Route::get(PaymentGateway::SUCCESS_URL, function () {
    $obPaymentGateway = new PaymentGateway();
    return $obPaymentGateway->processSuccessURL();
});

Route::get(PaymentGateway::FAIL_URL, function () {
    $obPaymentGateway = new PaymentGateway();
    return $obPaymentGateway->processFailURL();
});

