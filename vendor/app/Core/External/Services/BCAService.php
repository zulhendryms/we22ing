<?php

namespace App\Core\External\Services;

use Carbon\Carbon;
use App\Core\Base\Services\HttpService;
use Illuminate\Support\Facades\Auth;

class BCAService {

    /** @var HttpService $httpService */
    private $httpService; 
    private $token;

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService
        ->baseUrl(config('services.bca.url'))
        ->headers([ 'X-Forwareded-For' => '114.125.59.242' ])
        ->formParams();

        $this->token = $this->getToken();
        $this->httpService->headers([
            'Authorization' => 'Bearer '.$this->token,
            'X-BCA-Key' => config('services.bca.api_key'),
        ])->json();
    }

    protected function getToken()
    {
        $token = base64_encode(config('services.bca.client_id').':'.config('services.bca.client_secret'));
        $response = $this->httpService->headers([
            'Authorization' => 'Basic '.$token
        ])->post('/api/oauth/token', [
            'grant_type' => 'client_credentials'
        ]);
        return $response->access_token;
    }

    protected function createSignature($method, $url, $body = '')
    {
        if (strpos($url, '?') !== false) {
            $qs = explode('&', substr($url, strpos($url, '?') + 1));
            sort($qs);
            $url = substr($url, 0, strpos($url, '?') + 1).implode('&', $qs);
        }
        $timestamp = $this->createTimestamp();
        $this->httpService->headers(['X-BCA-Timestamp' => $timestamp]);
        $string = $method.':'.$url.':'.$this->token.':'.strtolower(hash('sha256', $body, false)).':'.$timestamp;
        return hash_hmac('sha256', $string, config('services.bca.api_secret'));
    }

    protected function createTimestamp()
    {
        $time = microtime(true);
        $microSeconds = sprintf("%06d", ($time - floor($time)) * 1000000);
        $date = Carbon::now('Asia/Bangkok')->toIso8601String();
        $iso8601Date = sprintf(
            "%s%03d%s",
            substr($date, 0, strlen($date) - 7).'.',
            floor($microSeconds/1000),
            '+07:00'
        );

        return $iso8601Date;
    }

    protected function signRequest($method, $url, $body = '')
    {
        $this->httpService->headers(['X-BCA-Signature' => $this->createSignature($method, $url, $body)]);
    }

    public function getStatement($startDate, $endDate)
    {
        if (config('services.bca.is_sandbox')) {
            $startDate = '2016-08-29';
            $endDate = '2016-09-01';
        }
        $url = '/banking/v3/corporates/'.config('services.bca.corp_id').'/accounts/'.config('services.bca.acc_no').'/statements?StartDate='.$startDate.'&EndDate='.$endDate;
        $this->signRequest('GET', $url);

       $response = $this->httpService->get($url);

        return $response;
    }

    public function findStatementByAmount($amount, $startDate = null)
    {
        if (is_null($startDate)) {
            $startDate = Carbon::now()->subWeek(1)->toDateString();
        }
        $statements = $this->getStatement($startDate, Carbon::now()->toDateString());
        $result = null;

        if (isset($statements->Data) && is_array($statements->Data)) {
            foreach ($statements->Data as $statement) {
                if ($statement->TransactionType != 'C') continue;
                if ($statement->TransactionAmount == $amount) {
                    $result = $statement;
                    break;
                }
            }
        }

        return $result;
    }

}