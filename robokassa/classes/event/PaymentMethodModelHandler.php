<?php namespace Robokassa\Robokassa\Classes\Event;

use Cms\Classes\Page;
use Lang;
use Lovata\OrdersShopaholic\Models\PaymentMethod;

use Robokassa\Robokassa\Classes\Helper\PaymentGateway;

class PaymentMethodModelHandler
{
    /**
     * Add event listeners
     * @param \Illuminate\Events\Dispatcher $obEvent
     */
    public function subscribe($obEvent)
    {
        PaymentMethod::extend(function ($obElement) {
            /** @var PaymentMethod $obElement */

            $obElement->addGatewayClass(PaymentGateway::CODE, PaymentGateway::class);

            $obElement->bindEvent('model.beforeValidate', function () use ($obElement) {
                $this->addValidationRules($obElement);
            });
        });

        $obEvent->listen(PaymentMethod::EVENT_GET_GATEWAY_LIST, function () {
            $arPaymentMethodList = [
                PaymentGateway::CODE => 'Robokassa',
                //PaymentGateway::CODE => Lang::get('concordpay.concordpayshopaholic::lang.gateway.name'),
            ];

            return $arPaymentMethodList;
        });
    }

    /**
     * Add custom validartion rules and validation messages
     * @param PaymentMethod $obElement
     */
    protected function addValidationRules($obElement)
    {
        if ($obElement->gateway_id != PaymentGateway::CODE
            || $obElement->getOriginal('gateway_id') != PaymentGateway::CODE
        ) {
            return;
        }

    }
}
