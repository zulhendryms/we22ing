<?php

namespace App\Core\Base\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;

class HttpService 
{
    /** @var Client */
    private $client;
    protected $baseUrl = '';
    protected $headers = [
        'http_errors' => false,
    ];
    protected $contentType = 'application/json';
    protected $accept = 'application/json';
    protected $timeout = 0;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function baseUrl($baseUrl) 
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function headers($headers) 
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function setHeader($key, $value) 
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function unsetHeader($key) 
    {
        unset($this->headers[$key]);
        return $this;
    }

    public function clearHeaders() 
    {
        $this->headers = [
            'http_errors' => false
        ];
        return $this;
    }

    public function auth($token) 
    {
        $this->headers['Authorization'] = $token;
        return $this;
    }

    public function json() 
    {
        $this->headers['Content-Type'] = 'application/json';
        $this->headers['Accept'] = 'application/json';
        return $this;
    }

    public function formParams() 
    {
        $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $this->headers['Accept'] = 'application/json';
        return $this;
    }

    public function timeout($value) 
    {
        $this->timeout = $value;
        return $this;
    }

    /**
     * Do a Http Request
     * 
     * @param string $url
     * @param string $method
     * @param array $requestOptions
     * 
     * @return mixed
     */
    public function request($url, $method, $requestOptions) 
    {
        try {
            $options = [
                'headers' => $this->headers,
                'timeout' => $this->timeout
                // 'verify' => false
            ];
            if (strtolower($method) != 'get' && isset($requestOptions['body'])) {
                if ($this->headers['Content-Type'] == 'application/json') {
                    $options['json'] = $requestOptions['body'];
                } else if ($this->headers['Content-Type'] == 'application/x-www-form-urlencoded') {
                    $options['form_params'] = $requestOptions['body'];
                }
            }

            $url = $this->baseUrl.$url;

            $logger = logger();
            // $logger->info("[HTTP Request]".PHP_EOL, array_merge(['url' => $url], $options));

            $result = $this->client->request($method, $url, $options);
            $contentType = $result->getHeader('Content-Type')[0];

            $response = $result->getBody();
            if (strpos($contentType, 'json')) {
                $response = json_decode($result->getBody());
            }

            // $logger->info("[HTTP Response]".PHP_EOL, [
            //     'url' => $url,
            //     'response' => is_string($response) ? $response : json_decode(json_encode($response), true)
            // ]);

            return $response;
        } catch (RequestException $ex) {
            logger()->error("[HTTP REQUEST ERROR]", [ 'message' => $ex->getResponse()->getBody() ]);
            throw $ex;
        }
    }

    /**
     * Get http request
     * 
     * @param string $url
     * @param array $query
     * @return mixed
     */
    public function get($url, $query = []) 
    {
        return $this->request($url, 'GET', ['query' => $query]);
    }

    /**
     * Post http request
     * 
     * @param string $url
     * @param array $data
     * @return mixed
     */
    public function post($url, $data = []) 
    {
        return $this->request($url, 'POST', ['body' => $data]);
    }

     /**
     * PUT http request
     * 
     * @param string $url
     * @param array $data
     * @return mixed
     */
    public function put($url, $data = []) 
    {
        return $this->request($url, 'PUT', ['body' => $data]);
    }

     /**
     * Delete http request
     * 
     * @param string $url
     * @return mixed
     * 
     */
    public function delete($url) 
    {
        return $this->request($url, 'DELETE', null);
    }

    /**
     * Download a file
     * 
     * @param string $url
     * @return mixed
     */
    public function download($url, $path)
    {
        $this->client->request('GET', $url, [
            'sink' => $path
        ]);
    }
}