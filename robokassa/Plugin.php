<?php namespace Robokassa\Robokassa;

use Backend;
use System\Classes\PluginBase;
use Robokassa\Robokassa\Classes\Event\ExtendPaymentMethodFieldsHandler;
use Robokassa\Robokassa\Classes\Event\PaymentMethodModelHandler;

/**
 * Robokassa Plugin Information File
 */
class Plugin extends PluginBase
{

    public $require = [
        'Lovata.Toolbox',
        'Lovata.Shopaholic',
        'Lovata.OrdersShopaholic',
    ];
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Robokassa',
            'description' => 'No description provided yet...',
            'author'      => 'Robokassa',
            'icon'        => 'icon-credit-card',
            'homepage'    => 'https://robokassa.ru/'
        ];
    }


    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        $this->addListeners();
    }

    private function addListeners()
    {
        \Event::subscribe(ExtendPaymentMethodFieldsHandler::class);
        \Event::subscribe(PaymentMethodModelHandler::class);
    }

}
