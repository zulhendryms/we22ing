<?php

namespace App\AdminApi\Security\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardAdminDevController extends Controller
{
    public function index(Request $request)
    {        
        try {            
            $company = Auth::user()->CompanyObj;

            $query = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(p.TotalAmount) AS Amount
                FROM pospointofsale p
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW()
                GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
            $data2 = DB::select($query);

            $data =[
                'Company' => [
                    'Oid' => $company->Oid,
                    'Name' => $company->Name,
                    'Image' => $company->Image,
                    'FullAddress' => $company->FullAddress,
                    'PhoneNo' => $company->PhoneNo,
                    'Currency' => $company->Currency,
                    'CurrencyObj' => [
                        'Oid' => $company->CurrencyObj->Oid,
                        'Code' => $company->CurrencyObj->Code,
                        'Name' => $company->CurrencyObj->Name
                    ]
                ],
                'Sales' => [
                    'TotalAmount' => $this->sumTotal($data2),
                    'Title' => "Sales Last 7 Days",
                    'Data' => $this->arrayLast7Days($data2),
                ]
            ];

            return $data;
           
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }

    private function sumTotal($data) {
        $totalamount = 0;
        foreach($data as $row) $totalamount += $row->Amount;
        return $totalamount;
    }

    private function arrayLast7Days($data) {
        $arr = array();
        for ($x = 6; $x >= 0; $x--) {
            $found = 0;
            $date = date('Y-m-d', strtotime(now(). ' - '.$x.'days'));
            foreach($data as $row) {
                if ($row->DateOrder == $date) {
                    array_push($arr, [
                        'Date' => $row->DateDisplay,
                        'Amount' => $row->Amount,
                    ]);
                    $found = 1;
                }                
            }
            if ($found == 0) {
                $date = date('j M',strtotime(substr($x,0,4).'-'.substr($x,4,2).'-'.substr($x,6,2)));
                array_push($arr, [
                    'Date' => date('d M', strtotime(now(). ' - '.$x.'days')),
                    'Amount' => 0,
                ]);
            }   
        }
        return $arr;
    }
}
