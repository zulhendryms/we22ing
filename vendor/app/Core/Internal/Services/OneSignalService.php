<?php
namespace App\Core\Internal\Services;

use GuzzleHttp\Client;
use App\Core\Internal\Resources\SystemCompany;
use App\Core\Base\Services\HttpService;

class OneSignalService {
    private $listApp = [];
    private $baseurl;
    private $client;
    private $httpService; 

    public function __construct(Client $client)
    {
        $this->httpService = new HttpService();
        $this->httpService = $this->httpService
        ->baseUrl('https://onesignal.com/api/v1')
        ->json();
        $this->client = $client;
        $this->baseurl = 'https://onesignal.com/api/v1/notifications';
        $this->setListApp();
    }

    public function sendNotification($title, $message, $to, $app = 'administrator', $return = false){
        //1. pake tag tdk pake player tdk pake tag login = login
        //2. to = userid tanpa app / company
        //3. credential onesignal ada token dan appid
        //4. test dari laravel {{url}}/admin/api/v1/development/test/onesignal?to=vivi&return=1
        //5. test dari onesignal https://onesignal.com/api/v1/notifications

        foreach ($this->listApp as $key => $item) {
            if ($app == 'all' || $item->name == $app || (is_array($app) && in_array($item->name, $app))) {

                $header = [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Basic ' . $item->token
                ];
                $data = [];
                $data['headers'] = $header;
                $title = array(
                    "en" => isset($title) ? $title : 'test'
                );
                $content = array(
                    "en" => isset($message) ? $message : 'test'
                );

                if ($to == 'all') {
                    $send = array(
                        'app_id' => $item->app_id,
                        'contents' => $content,
                        'included_segments' => array(
                            'Subscribed Users'
                        )
                    );
                } else if (isset($to) && is_string($to)) {
                    $send = array(
                        'app_id' => $item->app_id,
                        'contents' => $content,
                        'filters' => array(array("field" => "tag", "key" => "id", "relation" => "=", "value" => $to)),
                    );
                } else if (isset($to) && is_array($to)) {
                    $filters = null;
                    foreach ($to as $itemTo) {
                        if ($filters) $filters[] = array("operator" => "OR");
                        $filters[] = array("field" => "tag", "key" => "id", "relation" => "=", "value" => $itemTo);
                    }

                    $send = array(
                        'app_id' => $item->app_id,
                        'headings' => $title,
                        'contents' => $content,
                        'filters' => $filters
                    );
                } else {
                    if (isset($to[0])) if (strlen($to[0]) > 10) $to = $to[0];
                    $send = array(
                        'app_id' => $item->app_id,
                        'headings' => $title,
                        'contents' => $content,
                        'filters' => array(array("field" => "tag", "key" => "id", "relation" => "=", "value" => $to)),
                    );
                }

                $data['json'] = $send; // send adalah data dari isi pesan sampai rule / login penerima
                // dd(json_encode($data));
                try {
                    $res = $this->client->request(
                        'POST',
                        $this->baseurl,
                        $data
                    );
                } catch (\Exception $e) {
                    // if ($return) err_return($e);
                }                  
            }
        }
        if ($return) return $data;
        else return $res;
    }
    public function sendNotification2($to, $return = true){
        $json = [

            'app_id' => 'e368be6d-1d88-4ef7-8bd7-8c5d503e7804',
            'headings' => [
                'en' => 'THIS IS TESTING MESSAGE'
            ],
            'contents' => [
                'en' => 'This is testing message body'
            ],
            'include_player_ids' => [
                '096695df-8423-41c3-90a9-3f7194463e82'
            ]
            // 'filters' => [
            //     [
            //         "field"=> "tag",
            //         "key"=> "login",
            //         "relation"=> "=",
            //         "value"=> "login"
            //     ],
            //     [
            //         "operator"=> "AND"
            //     ],
            //     [
            //         "field"=> "tag",
            //         "key"=> "id",
            //         "relation"=> "=",
            //         "value"=> "5b2f0dc2-e9ec-4efb-9876-e59329372a46"
            //     ]
            // ]
        ];
        $this->httpService->setHeader("Authorization", "Bearer NzUzMTZlNmQtYWU0NS00MzA4LWFkZWItYWRiYmI2ODZjMmZl");
        
        try {
            return $this->httpService->post('/notifications', $json);
        } catch (\Exception $e) {
            if ($return) err_return($e);
        }
    }
    public function test($return = true){        
        try {
            $this->httpService->setHeader("Authorization", "Bearer NzUzMTZlNmQtYWU0NS00MzA4LWFkZWItYWRiYmI2ODZjMmZl");
            return $this->httpService->get('/apps/e368be6d-1d88-4ef7-8bd7-8c5d503e7804');
        } catch (\Exception $e) {
            if ($return) throw $e;
        }  
    }

    public function setListApp () {
        $SystemCompany = new SystemCompany();
        $this->listApp = $SystemCompany->getList();
    }    

    public function test2($type, $return){
        $data = [];
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => ["Basic NzUzMTZlNmQtYWU0NS00MzA4LWFkZWItYWRiYmI2ODZjMmZl"]
        ];
        $data['headers'] = $header;
        if ($type == 1) {
            $data['json'] = [
                    "app_id"=> "e368be6d-1d88-4ef7-8bd7-8c5d503e7804",
                    "headings"=> [
                        "en"=> "Testing Heading"
                    ],
                    "contents"=> [
                        "en"=> "Testing Message"
                    ],
                    "include_player_ids"=> [
                        "096695df-8423-41c3-90a9-3f7194463e82"
                    ]
            ];
        } elseif ($type == 2) {
            $data['json'] = [
                    "app_id"=> "e368be6d-1d88-4ef7-8bd7-8c5d503e7804",
                    "headings"=> [
                        "en"=> "Testing Heading"
                    ],
                    "contents"=> [
                        "en"=> "Testing Message"
                    ],
                    "filters"=> [
                        [
                            "field"=> "tag",
                            "key"=> "login",
                            "relation"=> "=",
                            "value"=> "login"
                        ],
                        [
                            "operator"=> "AND"
                        ],[
                            "field"=> "tag",
                            "key"=> "id",
                            "relation"=> "=",
                            "value"=> "5b2f0dc2-e9ec-4efb-9876-e59329372a46"
                        ]
                    ]
            ];
        } elseif ($type == 3) {   
            // dd($data);
            // try {
            //     return $this->client->request(
            //         'GET',
            //         'https://onesignal.com/api/v1/apps/e368be6d-1d88-4ef7-8bd7-8c5d503e7804',
            //         $header
            //     );
            // } catch (\Exception $e) {
            //     if ($return) err_return($e);
            // }  
            return $this->client->get('https://onesignal.com/api/v1/apps/e368be6d-1d88-4ef7-8bd7-8c5d503e7804', [
                'Authorization' => ['Basic NzUzMTZlNmQtYWU0NS00MzA4LWFkZWItYWRiYmI2ODZjMmZl']
            ]);
        }
        try {
            return $this->client->request(
                'POST',
                'https://onesignal.com/api/v1/notifications',
                $data
            );
        } catch (\Exception $e) {
            if ($return) err_return($e);
        }  
    }
}

// {
//     "app_id": "e368be6d-1d88-4ef7-8bd7-8c5d503e7804",
//     "contents": {
//         "en": "test message2020-06-20 12:00:19"
//     },
//     "filters": [
//         {
//             "field": "tag",
//             "key": "id",
//             "relation": "=",
//             "value": "fb4105e3-a272-4ac8-b8b7-3d051a1ce70c"
//         }
//     ]
// }