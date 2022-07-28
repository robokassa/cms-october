<?php
namespace Robokassa\Robokassa\Classes\Event;

use Lovata\Toolbox\Classes\Event\AbstractBackendFieldHandler;

use Lovata\OrdersShopaholic\Models\PaymentMethod;
use Lovata\OrdersShopaholic\Controllers\PaymentMethods;
use Robokassa\Robokassa\Classes\Helper\PaymentGateway;

class ExtendPaymentMethodFieldsHandler extends AbstractBackendFieldHandler
{
    /**
     * Extend backend fields
     * @param \Backend\Widgets\Form $obWidget
     */
    protected function extendFields($obWidget)
    {
        if ($obWidget->model->gateway_id != PaymentGateway::CODE) {
            return;
        }

        $obWidget->addTabFields(
            [
                'secret_section' => [
                    'label'   => 'Персональные данные Robokassa',
                    'comment' => 'Внесите данные с личного кабинета',
                    'tab'     => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'type'    => 'section',
                    'span'    => 'full'
                ],

                'gateway_currency' => [
                    'label'    => 'lovata.ordersshopaholic::lang.field.gateway_currency',
                    'tab'      => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'span'     => 'auto',
                    'required' => 'true',
                    'type' => 'dropdown',
                    'options' => [
                        'USD' => 'USD',
                        'EUR' => 'EUR',
                        'KZT' => 'KZT',
                        'RUR' => 'RUR',
                    ]
                ],

                'gateway_property[merchant_login]' => [
                    'label' => 'MerchantLogin',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'true',
                    'span' => 'left'
                ],

                'gateway_property[country]' => [
                    'label' => 'Страна',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'true',
                    'span' => 'right',
                    'type' => 'dropdown',
                    'default' => 'RUS',
                    'options' => [
                        'RUS' => 'Россия',
                        'KAZ' => 'Казахстан'

                    ]
                ],
                'gateway_property[receipt]' => [
                    'label' => 'Фискализация',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'true',
                    'span' => 'right',
                    'default' => 'Да',
                    'type' => 'switch',
                ],

                'gateway_property[pass1]' => [
                    'label' => 'Пароль 1',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'true',
                    'span' => 'left',
                    'type' => 'password'
                ],
                'gateway_property[is_test]' => [
                    'label' => 'Тестовые заказы',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'true',
                    'span' => 'right',
                    'default' => 'Да',
                    'type' => 'switch',
                ],
                'gateway_property[pass2]' => [
                    'label' => 'Пароль 2',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'true',
                    'span' => 'left',
                    'type' => 'password'
                ],
                'gateway_property[tax]' => [
                    'label' => 'Ставка налога',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'false',
                    'span' => 'left',
                    'type' => 'dropdown',
                    'options' => [
                        'none' => 'Без НДС',
                        'vat0' => 'НДС по ставке 0%',
                        'vat10' => ' НДС чека по ставке 10%',
                        'vat110' => 'НДС чека по расчетной ставке 10/110',
                        'vat20' => 'НДС чека по ставке 20%',
                        'vat120' => 'НДС чека по расчетной ставке 20/120',
                    ]
                ],
                'gateway_property[payment_method]' => [
                    'label' => 'Признак способа расчёта',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'false',
                    'span' => 'left',
                    'type' => 'dropdown',
                    'options' => [
                        'full_prepayment' => 'Предоплата 100%',
                        'prepayment' => 'Предоплата',
                        'advance' => 'Аванс',
                        'full_payment' => 'Полный расчёт',
                        'partial_payment' => 'Частичный расчёт и кредит',
                        'credit' => 'Передача в кредит',
                        'credit_payment' => 'Оплата кредита',
                    ]
                ],
                'gateway_property[payment_object]' => [
                    'label' => 'Признак предмета расчёта',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'false',
                    'span' => 'left',
                    'type' => 'dropdown',
                    'options' => [
                        'commodity' => 'Товар',
                        'excise' => 'Подакцизный товар',
                        'job' => 'Работа',
                        'service' => 'Услуга',
                        'gambling_bet' => 'Ставка азартной игры',
                        'gambling_prize' => 'Выигрыш азартной игры',
                        'lottery' => 'Лотерейный билет',
                        'lottery_prize' => 'Выигрыш лотереи',
                        'intellectual_activity' => 'Предоставление результатов интеллектуальной деятельности',
                        'payment' => 'Платеж',
                        'agent_commission' => 'Агентское вознаграждение',
                        'composite' => 'Составной предмет расчета',
                        'resort_fee' => 'Курортный сбор',
                        'another' => 'Иной предмет расчета',
                        'property_right' => 'Имущественное право',
                        'non-operating_gain' => 'Внереализационный доход',
                        'insurance_premium' => 'Страховые взносы',
                        'sales_tax' => 'Торговый сбор',
                    ]
                ],
                'gateway_property[culture]' => [
                    'label' => 'Язык',
                    'tab' => 'lovata.ordersshopaholic::lang.tab.gateway',
                    'required' => 'true',
                    'span' => 'left',
                    'type' => 'dropdown',
                    'options' => [
                        'ru' => 'ru',
                        'en' => 'en',
                    ]
                ],

            ]
        );
    }

    /**
     * Get model class name
     * @return string
     */
    protected function getModelClass(): string
    {
        return PaymentMethod::class;
    }

    /**
     * Get controller class name
     * @return string
     */
    protected function getControllerClass(): string
    {
        return PaymentMethods::class;
    }
}
