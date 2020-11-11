<?php

namespace App\Core\External\Services;

use Carbon\Carbon;
use App\Core\Base\Services\HttpService;

class MidtransService
{
    /** @var HttpService $httpService */
    private $httpService; 
    private $serverKey;

    /**
     * @param HttpService $httpService
     * @return void
     */
    public function __construct(HttpService $httpService)
    {
        $this->serverKey = config('services.midtrans.server_key');
        $headers = [
            'Authorization' => 'Basic '.base64_encode($this->serverKey),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
        $this->httpService = $httpService
        ->headers($headers)
        ->json();
    }

    /**
     * Get Midtrans payment method name
     * 
     * @param string $type Payment method code
     * @return string|null
     */
    public function getPaymentMethod($type)
    {
        switch($type) {
            case 'bca_klikpay_midtrans':
                return 'bca_klikpay';
            case 'credit_card_midtrans':
                return 'credit_card';
            case 'permata_va_midtrans':
                return 'permata_va';
            case 'bca_va_midtrans':
                return 'bca_va';
            case 'mandiri_clickpay_midtrans':
                return 'mandiri_clickpay';
        }
    }

    /**
     * Get request param for requesting snap token
     * 
     * @param \App\Core\POS\Entities\PointOfSale $pos
     * @return array
     */
    public function createTokenRequest($pos)
    {
        $name = $pos->ContactName;
        $firstname = $lastname = $name;
        $type = $pos->PointOfSaleTypeObj;
        $method = $pos->PaymentMethodObj;
        if (strpos($name, ' ')) {
            $index = strpos($name, ' ');
            $firstname = substr($name, 0, $index);
            $lastname = substr($name, $index + 1);
        }
        $params = [
            'transaction_details' => [
                'order_id' => $pos->Code,
                'gross_amount' => $pos->TotalAmount
            ],
            'credit_card' => [
                'secure' => true
            ],
            'customer_details' => [
                'first_name' => $firstname,
                'last_name' => $lastname,
                'email' => $pos->ContactEmail,
                'phone' => '+'.$pos->ContactPhone
            ],
            'expiry' => [
                'start_time' => Carbon::now()->tz('Asia/Jakarta')->toDateTimeString().' +07:00',
                'unit' => 'minutes',
                'duration' => Carbon::parse($pos->DateExpiry)->diffInMinutes(Carbon::now())
            ],
            't' => $type->Code,
            'item_details'=> [],
            'enabled_payments' => [ $this->getPaymentMethod($method->Code) ]
        ];

        $ferTransaction = $pos->FerryTransactionObj;
        if ($type->IsFerry) {
            $schedule = $ferTransaction->PortScheduleObj;
            $route = $schedule->RouteObj;
            $vendor = $pos->SupplierObj;
            $portFrom = $route->PortFromObj;
            $portTo = $route->PortToObj;

            $name = "{$vendor->Name}: {$portFrom->Code} - {$portTo->Code}";

            if (!$ferTransaction->IsRoundTrip) {
                $name.=' (One way)';
            } else {
                $name.=' (Two way)';
            }

            $params['item_details'][] = [
                'name' => $name,
                'quantity' => 1,
                'price' => $pos->TotalAmount,
                'id' => $schedule->Oid
            ];
        } else if ($type->IsAttraction) {
            $attraction = $ferTransaction->ItemObj;
            $params['item_details'][] = [
                'id' => $attraction->Oid,
                'price' => $pos->TotalAmount,
                'quantity' => 1,
                'name' => strlen($attraction->Name) > 50 ? substr($attraction->Name, 0, 50) : $attraction->Name
            ];
        }

        return $params;
    }

    /**
     * Get token for snap payment
     * 
     * @param array $data
     * @return mixed
     */
    public function createToken($data)
    {
        return $this->httpService
            ->baseUrl(config('services.midtrans.snap_url'))
            ->post('/transactions', $data);
    }

    /**
     * Verify transaction signature
     * 
     * @param string $signature
     * @param string $statusCode
     * @param string $code
     * @param float|string $total
     * @return boolean
     */
    public function verifySignature($signature, $statusCode, $code, $total)
    {
        $sign = hash('sha512', $code.$statusCode.$total.'.00'.$this->serverKey);
        return $sign == $signature;
    }

    /**
     * Get status of transaction
     * 
     * @param string $id
     * @return mixed
     */
    public function status($id)
    {
        return $this->httpService->baseUrl(config('services.midtrans.url'))->get("/{$id}/status");
    }

    /**
     * Approve transaction
     * 
     * @param string $id
     * @return void
     */
    public function approve($id)
    {
        $res = $this->httpService->baseUrl(config('services.midtrans.url'))->post("/{$id}/approve");

        if ($res->status_code != 200) {
            throw new \Exception($res->status_message);
        }
    }
}
