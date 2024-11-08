<?php
namespace Robokassa\Robokassa\Classes\Helper;

use Event;
use Lovata\OrdersShopaholic\Models\Order;
use Lovata\Shopaholic\Models\Offer;
use Response;
use Lang;
use Lovata\OrdersShopaholic\Classes\Helper\AbstractPaymentGateway;
use October\Rain\Support\Facades\Input;

class PaymentGateway extends AbstractPaymentGateway
{
    const CODE = 'rob';
    /** @var array - response from payment gateway */
    protected $arResponse = [];
    /** @var array - request data for payment gateway */
    protected $arRequestData = [];
    protected $sRedirectURL = '';
    protected $sMessage = '';
    protected $sSignature = '';
    protected $obResponse;

    protected $Description = '';
    protected $signatureValue = '';

    const NOTIFICATION_URL = 'robokassa/result';
    const SUCCESS_URL = 'robokassa/success';
    const FAIL_URL = 'robokassa/fail';

    const POST_URL_KZ = 'https://auth.robokassa.kz/Merchant/Index.aspx?';
    const POST_URL_RU = 'https://auth.robokassa.ru/Merchant/Index.aspx?';


    const EVENT_FAIL_RETURN_URL = 'shopaholic.payment.robokassa.fail.redirect_url';
    const EVENT_SUCCESS_RETURN_URL = 'shopaholic.payment.robokassa.success.redirect_url';


    /**
     * Get response array
     * @return string
     */
    public function getResponse(): array
    {
        return (array)$this->arResponse;
    }

    /**
     * Get redirect URL
     * @return string
     */
    public function getRedirectURL(): string
    {
        return $this->sRedirectURL;
    }

    /**
     * Get response message
     * @return string
     */
    public function getMessage(): string
    {
        return $this->sMessage;
    }

    /**
     * Prepare purchase data array for request
     * @return void
     */
    protected function preparePurchaseData()
    {
        $this->arRequestData = [
            'MrchLogin' => $this->getGatewayProperty('merchant_login'),
            'Pass1' => $this->getGatewayProperty('pass1'),
            'OutSum' => $this->numberSum(),
            'InvId' => $this->obOrder->id,
            'Desc' => $this->obOrder->order_number,
            'currency' => $this->obPaymentMethod->gateway_currency,
            'culture' => $this->getGatewayProperty('culture'),
            'isTest' => $this->getGatewayProperty('is_test')
        ];

        $this->arRequestData['shp_label'] = 'official_october';

        $login = $this->arRequestData['MrchLogin'];
        $pass1 = $this->arRequestData['Pass1'];
        $outsumm = $this->arRequestData['OutSum'];
        $invid = $this->arRequestData['InvId'];

        if($this->getGatewayProperty('receipt'))
        {
            $this->arRequestData['Receipt'] = $this->getReceipt();
        }

        $hash = "$login:$outsumm:$invid";

        if(
            ($this->getGatewayProperty('country') == 'RUS' && $this->obPaymentMethod->gateway_currency != 'RUR') ||
            ($this->getGatewayProperty('country') == 'KAZ' && $this->obPaymentMethod->gateway_currency != 'KZT')
        )
        {
            $OutSumCurrency = $this->arRequestData['currency'];
            $hash .= ":$OutSumCurrency";
        }

        if(isset($this->arRequestData['Receipt']))
        {
            $receipt = json_encode($this->arRequestData['Receipt']);
            $hash .= ":$receipt";
        }



        $hash .= ":$pass1";

        $hash .= ":shp_label=official_october"; // новый параметр

        $this->signatureValue = md5($hash);
    }


    /**
     * Validate purchase data array for request
     * @return bool
     */
    protected function validatePurchaseData()
    {
        return true;
    }

    /**
     * Send purchase data array for request
     * @return void
     */
    protected function sendPurchaseData()
    {
        $this->preparePurchaseData();
    }

    /**
     * Process purchase response
     * @return void
     */
    protected function processPurchaseResponse()
    {
        $url = $this->getGatewayProperty('country') == 'RUS' ? self::POST_URL_RU : self::POST_URL_KZ;
        $this->sRedirectURL = $url .
            "MrchLogin=" . $this->arRequestData['MrchLogin'] .
            "&OutSum=" . $this->arRequestData['OutSum'] .
            "&InvId=" . $this->arRequestData['InvId'] .
            "&Desc=" . $this->arRequestData['Desc'] .
            '&Culture=' . $this->arRequestData['culture'] .
            "&SignatureValue=" . $this->signatureValue .
            "&shp_label=official_october";

        if(isset($this->arRequestData['Receipt']))
        {
            $this->sRedirectURL .= "&receipt=".json_encode($this->arRequestData['Receipt']);
        }

        if(
            ($this->getGatewayProperty('country') == 'RUS' && $this->obPaymentMethod->gateway_currency != 'RUR') ||
            ($this->getGatewayProperty('country') == 'KAZ' && $this->obPaymentMethod->gateway_currency != 'KZT')
        )
        {
            $this->sRedirectURL .= "&OutSumCurrency=" . $this->arRequestData['currency'];
        }

        if($this->arRequestData['isTest']){
            $this->sRedirectURL .= "&isTest=".$this->arRequestData['isTest'];
        }
        $this->bIsRedirect = true;

        $this->arRequestData['payment_url'] = $this->sRedirectURL;

        // Capture request and reponse data to order.
        $this->obOrder->payment_data = $this->arRequestData;
        $this->obOrder->payment_token = $this->signatureValue;
        $this->obOrder->save();
    }
    public function processSuccessURL()
    {
        $arData = Input::all();
        if (empty($arData) || !$this->prepareOrder($arData)) {
            $this->denied();
        }

        $this->setWaitPaymentStatus();

        $this->obOrder->payment_response = $arData;
        $this->obOrder->save();

        return $this->returnRedirectResponse(self::EVENT_SUCCESS_RETURN_URL);
    }

    public function processFailURL()
    {
        $arData = Input::all();
        if (empty($arData) || !$this->prepareOrder($arData)) {
            $this->denied();
        }

        $this->setCancelStatus();

        $this->obOrder->payment_response = $arData;
        $this->obOrder->save();

        return $this->returnRedirectResponse(self::EVENT_FAIL_RETURN_URL);
    }

    public function processAnswerRequest()
    {
        $arData = Input::all();
        if (empty($arData) || !$this->prepareOrder($arData)) {
            $this->denied();
        }

        $this->setSuccessStatus();

        return "OK".$this->obOrder->id."\n";
    }

    protected function prepareOrder($arData)
    {
        $iOrderId = array_get($arData, 'InvId');
        $this->obOrder = Order::find(e($iOrderId));
        if (empty($this->obOrder)) {
            return false;
        }

        $this->obPaymentMethod = $this->obOrder->payment_method;
        $this->preparePurchaseData();

        if ($this->signatureValue !== e(array_get($arData, 'crc'))) {
            return false;
        }

        return true;
    }

    protected function getReceipt()
    {
        $out = [
            'items' => []
        ];

        if(!$this->obOrder->order_position)
        {
            return $out;
        }



        switch ($this->getGatewayProperty('country'))
        {
            case 'RUS':
                foreach ($this->obOrder->order_position as $position) {
                    $offer = Offer::where(['id' => $position->item_id])->first();
                    $product = $offer->product;
                    $out['items'][] = [
                        'name' => "$product->name ($offer->name)",
                        'quantity' => $position['quantity'],
                        'payment_method' => $this->getGatewayProperty('payment_method'),
                        'payment_object' => $this->getGatewayProperty('payment_object'),
                        'sum' => $position['price']*$position['quantity'],
                        'tax' => $this->getGatewayProperty('tax'),

                    ];
                }
                break;

            case 'KAZ':
                $out = [
                    'items' => []
                ];

                if(!$this->obOrder->order_position)
                {
                    return $out;
                }
                foreach ($this->obOrder->order_position as $position) {
                    $offer = Offer::where(['id' => $position->item_id])->first();
                    $product = $offer->product;
                    $out['items'][] = [
                        'name' => "$product->name ($offer->name)",
                        'quantity' => $position['quantity'],
                        'sum' => $position['price']*$position['quantity'],
                        'tax' => $this->getGatewayProperty('tax'),

                    ];
                }
                break;
        }

        if($this->obOrder->shipping_price > 0){
            $out['items'][] = [
                'name' => $this->obOrder->shipping_type->name,
                'quantity' => 1,
                'sum' => $this->obOrder->shipping_price,
                'tax' => $this->getGatewayProperty('tax')
            ];
        }

        return $out;

    }

    protected function numberSum()
    {
        return number_format($this->obOrder->total_price_value, 2, '.', '');
    }

    protected function denied()
    {
        return Response::make("Ошибка целосности\n", 403);
    }
}

