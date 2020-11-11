<?php

namespace App\Core\Base\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use App\Core\Base\Services\HttpService;

class CoreService extends HttpService
{
    private $data;
    private $token;
    private $httpService;

    public function __construct(HttpService $httpService)
    {
        // parent::__construct();
        // $this->setBaseUrl(env('INTERSERVICES_SERVICENAME_BASEURL'));
        // $this->setToken(null);
        // $this->setHeader("Authorization", "Bearer ".$this->token);

        $this->httpService = $httpService
        ->baseUrl('http://api1.ezbooking.co:1000')
        // ->baseUrl('http://localhost/ezb-admin-api/public')
        ->json();
    }
    
    public function setToken(string $token = null)
    {
        // from .env()
        // $token_ = env('INTERSERVICES_SERVICENAME_TOKEN');
        $token_ = "eyJhbGciOiJIUzI1NiJ9.eyJwcmluY2lwYWwiOm51bGwsInN1YiI6InJlc2VsbGVyQGdsb2JhbHRpeC5jb20iLCJleHAiOjE1NjQ0OTEzNTUsImlhdCI6MTU2NDQwNDk1NSwicm9sZXMiOlsiUkVTRUxMRVJfQURNSU4iLCJSRVNFTExFUl9GSU5BTkNFIiwiUkVTRUxMRVJfT1BFUkFUSU9OUyJdfQ.ndWuj0UZdLfIPzSeYShWVQefQy59aHTvEpVSVv43qfs";
        $this->token = $token ?? $token_;
        return $this;
    }

    public function postJson($url, $data = []) 
    {
        return $this->request($url, 'POST', ['json' => $data]);
    }

    public function postapi($url, $token, $data)
    {
        $this->httpService->setHeader("Authorization", "Bearer ".$token);
        return $this->httpService->post($url,$data);
    }

    // toString, toArray, toObject(class)
    // (new AuthInterServices)->api()->setParams('data')->toArray();
    // (new AuthInterServices)->api('api')->setParams('data')->post()->getData();
    // (new AuthInterServices)->api('api')->setParams('data')->get()->mapTo();
}