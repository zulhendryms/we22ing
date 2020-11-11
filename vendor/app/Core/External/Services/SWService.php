<?php

namespace App\Core\External\Services;

use App\Core\Base\Services\HttpService;

class SWService {

    /** @var HttpService $httpService */
    private $httpService; 

    private $token;
    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService
        ->baseUrl(config('services.swonline.url'))
        ->json();
    }

    private function getToken()
    {
        $res = $this->httpService->post('/clients', [
            'consumer_id' => config('services.swonline.consumer_id'),
            'consumer_secret' => config('services.swonline.consumer_key')
        ]);

        $this->token = $res->access_token;
        
        return $this->token;
    }

    public function getAttractions($sku = null)
    {
        if (!isset($this->token)) $this->getToken();

        $query = [
            'page' => 1
        ];

        if (!empty($sku)) $query['SKU'] = $sku;

        return $this->httpService->headers([
            'Authorization' => "Bearer {$this->token}"
        ])->get('/pricings? '.http_build_query($query));
    }

    public function createOrder($options)
    {

        $orders = new \stdClass;
        foreach ($options['items'] as $key => $item) {
            $product = $this->getAttractions($item['sku']);
            throw_if(count($product->Data) < 1, \Exception::class, "SKU: {$item['sku']} not found");
            $data = $product->Data[0];
            $priceDetail = $data->PriceDetails[0];
            $orderData = [
                'Quantity' => $item['qty'],
                'ProductId' => $data->ProductId,
                'PricingId' => $data->PricingId,
            ];
            if (isset($priceDetail->PromotionPriceId)) $orderData['PromotionPriceId'] = $priceDetail->PromotionPriceId;
            else if (isset($priceDetail->SpecialPriceId)) $orderData['SpecialPriceId'] = $priceDetail->SpecialPriceId;
            else if (isset($priceDetail->PriceTierId)) $orderData['PriceTierId'] = $priceDetail->PriceTierId;

            $orders->{$key} = $orderData;
        }

        $body = [
            'IsCombined' => true,
            'ReferenceNo' => $options['code'],
            'EmailTo' => company_email(),
            'attr' => $orders
        ];

        $res = $this->httpService->post('/orders', $body);

        $response = new \stdClass;
        $result = $res->result;
        $response->tickets = $result->tickets;
        return $response;
    }
}

?>
