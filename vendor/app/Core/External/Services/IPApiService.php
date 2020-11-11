<?php

namespace App\Core\External\Services;

use App\Core\Base\Services\HttpService;

class IPApiService {

    /** @var HttpService $httpService */
    private $httpService; 

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService
        ->baseUrl('http://ip-api.com');
    }

    public function query($ip)
    {
        return $this->httpService
        ->json()
        ->get('/json/'.$ip);
    }
}

?>
