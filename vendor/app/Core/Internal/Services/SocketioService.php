<?php
namespace App\Core\Internal\Services;

use App\Core\Internal\Resources\SystemCompany;
use App\Core\Base\Services\HttpService;

class SocketioService {

    private $httpService; 
    protected $listApp = [];

    public function __construct(){
        $this->setListApp();
        $this->httpService = new HttpService();
        $this->httpService->baseUrl('http://128.199.237.141:3000')->json();
        // $this->httpService->baseUrl('http://localhost:3000')->json();
    }

    //TESTLOG
    //2020-06-21 LOG MASUK DI SOCKET

    public function sendNotification($param, $to = null) {
        //1. to = app_company_useroid
        //2. to di generate saat disini
        //3. bole kirim to terpisah, bole berupa gabungan
        //4. apabila kirim to terpisah, maka to akan digenerate disini
        //5. apabila kirim to gabungan, mgkn sebaiknya sdh di format pd hal sblmnya

        if (isset($to)) {
            if (gettype($to) == 'string') {
                $to = 'administrator_'.$param['Company'].'_'.$to;
            } else {                
                $arr = [];
                foreach($to as $t) $arr[] = 'administrator_'.$param['Company'].'_'.$t;
                $to = $arr;
            }
            $param['To'] = $to;
        // } else {
        //     foreach($param as $p) {
        //         dd($p);
        //         foreach($p['To'] as $t) {
        //             dd($t);
        //             if (strlen($t) <20) $t = 'administrator_'.$param['Company'].'_'.$t;
        //         }
        //     }
        }
        return $this->httpService->post('/notification',$param);
    }

    public function removeNotification($param)
    {
        $arr = [];
        if (gettype($param) == 'array') {
            foreach ($param as $p) {
                $arr[] = [
                    'Oid' => $p->Oid,
                    'Type' => $p->Type,
                    'To' => 'administrator_'.$p->Company.'_'.$p->User,
                ];
            }
        } else {
            $arr[] = [
                'Oid' => $param->Oid,
                'Type' => $param->Type,
                'To' => 'administrator_'.$param->Company.'_'.$param->User,
            ];
        }
        return $this->httpService->post('/read/notification',$arr);
    }

    public function forceLogOut($to, $device) {
        // localhost:3000/auth?To=administrator_06beb986-2ca0-11ea-94dc-1a582ceaab05_5b2f0dc2-e9ec-4efb-9876-e59329372a46&Device=web_1592032311093&Action=forcelogout
        return $this->httpService->get("/auth?To=".$to."&Device=".$device."&Action=forcelogout");

    }

    public function setListApp () {
        // library company
        $SystemCompany = new SystemCompany();
        $this->listApp = $SystemCompany->getList();
    }
}