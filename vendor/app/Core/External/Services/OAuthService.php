<?php

namespace App\Core\External\Services;

use App\Core\Base\Services\HttpService;

class OAuthService 
{
    /** @var HttpService */
    protected $httpService;

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService->baseUrl(config('services.oauth.url'))->json();
    }

    public function passwordGrant($username, $password, $company = null) 
    {
        return $this->httpService
        ->post('/token', [
            'grant_type' => 'password',
            'client_id' => config('services.oauth.client_id'),
            'client_secret' => config('services.oauth.client_secret'),
            'username' => $username,
            'password' => $password,
            'company' => $company ?? config('app.company_id')
        ]);
    }
}