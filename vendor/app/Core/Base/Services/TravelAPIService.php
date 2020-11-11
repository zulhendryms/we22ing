<?php

namespace App\Core\Base\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use App\Core\Base\Services\HttpService;

class TravelAPIService extends HttpService
{
    private $data;
    private $token;
    private $httpService;

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService
        ->baseUrl('http://api1.ezbooking.co:8080')
        // ->baseUrl('http://localhost/ezb-travel-api/public')
        ->json();
    }
    
    public function setToken(string $token = null)
    {
        $token_ = "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6IjQ3NzIwZjlhZjVkMDNkMGFmOTViOGViNmM5ZWU1NTkwYTI1YWJiZDg5M2FhMmIxOWEyMDcyYmVjZDEwMGZhMWI3MGY0YWJlNjI5Y2FjOGNjIn0.eyJhdWQiOiI0IiwianRpIjoiNDc3MjBmOWFmNWQwM2QwYWY5NWI4ZWI2YzllZTU1OTBhMjVhYmJkODkzYWEyYjE5YTIwNzJiZWNkMTAwZmExYjcwZjRhYmU2MjljYWM4Y2MiLCJpYXQiOjE1Njk1NjY0MjksIm5iZiI6MTU2OTU2NjQyOSwiZXhwIjoxNzI3NDE5MjI5LCJzdWIiOiI1YjJmMGRjMi1lOWVjLTRlZmItOTg3Ni1lNTkzMjkzNzJhNDYiLCJzY29wZXMiOltdLCJjb21wIjoiNDJhZTdiZTYtMDFiNi00YWE1LTgxZTgtZDdhZDUyNmI3ZDA4In0.wGXKJ6zkqbo7_lWjW7Y6diQZa9ODdBrnxCsizObpL6VurTNClSdCYlmW0eGfDvmcqD3YS49dl-iTU4f_1K_JvvOKZioGDoFwS1nRc0cBFBF-a9LcmKV-h5UcXAbL1QLTDSZlDD2UPMYKWSRWoflBohlthtcK9L8Qz310kfRvuuLAn-z7FoWc5ao9E08ZTo5aLp9Kw9cyKzDvOj5oCYq_PP4gOrke5XKwqi1PSLT3_zs3DC7cqeVBYpaljCRFLa0WHyZEWEFtkpSRARp4ZxxyVIV0t-2VotCGXtkutdaOC9_2HrAXje3Bbo7FDIfsJ2-TJOVNYAx5mmvYJqIrwUNGOVVK1gg18WXvIS6ZdpG5o6els3sGeH_F9DCQjm-7tUY35_9gIFsdLoFLKhopf2i7lBRb_CBPOOtAGyKn0ZQm7c-jO1mrsfFbYzJxR0hkzkb_eXWWMBO1AxJx3eIYfTNduIcGAK6ncxO0BVUX1UKTJw0mLYpu22y1_m1tpVZkAwYkCg4_KefgqqqdxOI0dJ_Vsew3yQto5iwya1w-PEzKTMTCwcxi4IfKvxXKAfIIn0wE6uRTn8MaqH4s4YFyL7Eb38skc2biRaHXyG0qaB8G4HFgtceqslGYoRM9WeupFOEzLk1BxtUCU6vr9KBt3tUwA-1A39bWz0IuzgBhlMAwo9w";
        $this->token = $token ?? $token_;
        return $this;
    }

    public function postJson($url, $data = []) 
    {
        return $this->request($url, 'POST', ['json' => $data]);
    }

    public function postapi($url, $data = [])
    {
        $this->httpService->setHeader("Authorization", "Bearer ".$this->token);
        return $this->httpService->post($url,$data);
    }

    public function getapi($url, $data = null)
    {
        $this->httpService->setHeader("Authorization", "Bearer ".$this->token);
        return $this->httpService->get($url, $data);
    }

    public function deleteapi($url)
    {
        $this->httpService->setHeader("Authorization", "Bearer ".$this->token);
        return $this->httpService->delete($url);
    }
}