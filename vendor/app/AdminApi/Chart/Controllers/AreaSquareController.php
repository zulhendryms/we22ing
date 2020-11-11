<?php

namespace App\AdminApi\Chart\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AreaSquareController extends Controller
{

    public function data(Request $request) {
        return $this->list([
            'title' => $request->input('title') ?: "Sales Revenue",
            'subtitle' => $request->input('subtitle') ?: "Sales",
            'module' => $request->input('module') ?: "pos",
            'sum' => $request->input('sum') ?: "amount",
            'criteria' => $request->input('criteria') ?: "",
        ]);
    }

    public function list($request = [])
    {
        $criteria ='';
        switch ($request['sum']) {
            case "amount": { $field = 'p.TotalAmount'; break; }
            case "quantity": { $field = 'p.Quantity'; break; }
        }
        if ($request['criteria']) $criteria = " AND ".$request['criteria'];
        $query = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
                FROM pospointofsale p
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() ".$criteria."
                GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        $data = DB::select($query);

        $data = [
            'Statistic' => $this->sumTotal($data),
            'StatisticTitle' => $request['title'] ?: "Sales Revenue",
            'Series' => [
                [
                    'Name' => $request['subtitle'] ?: "Sales",
                    'Data' => $this->arrayLast7Days($data)
                ],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    private function sumTotal($data) {
        $totalamount = 0;
        foreach($data as $row) $totalamount += $row->Amount;
        return $totalamount;
    }

    private function arrayLast7Days($data) {
        $arr = array();
        for ($x = 0; $x <= 6; $x++) {
            $found = 0;
            $date = date('Y-m-d', strtotime(now(). ' -'.$x.' day'));
            foreach($data as $row) {
                if ($row->DateOrder == $date) {
                    array_push($arr, [
                        'x' => $row->DateDisplay,
                        'y' => $row->Amount,
                    ]);
                    $found = 1;
                }                
            }
            if ($found == 0) {
                $date = date('j M',strtotime(substr($x,0,4).'-'.substr($x,4,2).'-'.substr($x,6,2)));
                array_push($arr, [
                    'x' => date('d M', strtotime(now(). ' - '.$x.'days')),
                    'y' => 0,
                ]);
            }          
        }
        return $arr;
    }

    public function posamount(Request $request)
    {
        $query = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(p.TotalAmount) AS Amount
                FROM pospointofsale p
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW()
                GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        $data = DB::select($query);

        $data = [
            'Statistic' => $this->sumTotal($data),
            'StatisticTitle' => 'Revenue Generated',
            'Series' => [
                [
                    'Name' => 'Revenue',
                    'Data' => $this->arrayLast7Days($data)
                ],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function posquantity(Request $request)
    {
        $query = "SELECT DATE_FORMAT(Date, '%Y%m%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(IFNULL(p.Quantity, 0)) AS Amount
            FROM pospointofsale p
            WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW()
            GROUP BY DATE_FORMAT(Date, '%Y%m%d');";
        $data = DB::select($query);

        $data = [
            'Statistic' => $this->sumTotal($data),
            'StatisticTitle' => 'Quantity Sales',
            'Series' => [
                [
                    'Name' => 'Quantity',
                    'Data' => $this->arrayLast7Days($data)
                ],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function posgroupbya(Request $request)
    {
        $query = "SELECT DATE_FORMAT(Date, '%Y%m%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(pd.Quantity * pd.Amount) AS Amount
            FROM pospointofsale p
            LEFT OUTER JOIN pospointofsaledetail pd ON p.Oid = pd.PointOfSale
            LEFT OUTER JOIN mstitem i ON i.Oid = pd.Item
            LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW()
            AND ig.Oid='2df1debf-5857-4ff4-aaeb-6e316f898f20'
        GROUP BY DATE_FORMAT(Date, '%Y%m%d');";
        $data = DB::select($query);

        $data = [
            'Statistic' => $this->sumTotal($data),
            'StatisticTitle' => 'Sell Group A',
            'Series' => [
                [
                    'Name' => 'Sell Group A',
                    'Data' => $this->arrayLast7Days($data)
                ],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function posgroupbyb(Request $request)
    {
        $query = "SELECT DATE_FORMAT(Date, '%Y%m%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(pd.Quantity * pd.Amount) AS Amount
            FROM pospointofsale p
            LEFT OUTER JOIN pospointofsaledetail pd ON p.Oid = pd.PointOfSale
            LEFT OUTER JOIN mstitem i ON i.Oid = pd.Item
            LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW()
            AND ig.Oid='2df1debf-5857-4ff4-aaeb-6e316f898f20'
        GROUP BY DATE_FORMAT(Date, '%Y%m%d');";
        $data = DB::select($query);

        $data = [
            'Statistic' => $this->sumTotal($data),
            'StatisticTitle' => 'Sell Group B',
            'Series' => [
                [
                    'Name' => 'Sell Group B',
                    'Data' => $this->arrayLast7Days($data)
                ],
            ]
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }
    
    public function posamountoneweek(Request $request)
    {
        $query1 = "SELECT DATE_FORMAT(Date, '%Y%m%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(p.TotalAmount) AS Amount
                FROM pospointofsale p
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -2 WEEK) AND p.Date <= NOW()
                GROUP BY DATE_FORMAT(Date, '%Y%m%d');";
        $data1 = DB::select($query1);

        $dateend1 = date_format(NOW(),"Ymd");
        $datestart1 = date('Ymd', strtotime(now(). ' -2 week'));
        
        $arr1 = array();
        $totalamount1 = 0;
        for ($x = $datestart1; $x <= $dateend1; $x++) {
            $found1 = 0;
            foreach($data1 as $row) {
                if ($row->DateOrder == $x) {
                    // array_push($arr, $row->DateDisplay.': '.$row->Amount);
                    array_push($arr1, $row->Amount);
                    $totalamount1 += $row->Amount;
                    $found1 = 1;
                }                
            }
            if ($found1 == 0) {
                $date1 = date('j M',strtotime(substr($x,0,4).'-'.substr($x,4,2).'-'.substr($x,6,2)));
                // array_push($arr, $date.': 0');
                array_push($arr1, 0);
            }   
        } 

       $query = "SELECT DATE_FORMAT(Date, '%Y%m%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(p.TotalAmount) AS Amount
                FROM pospointofsale p
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -1 WEEK) AND p.Date <= NOW()
                GROUP BY DATE_FORMAT(Date, '%Y%m%d');";
        $data = DB::select($query);

        $dateend = date_format(NOW(),"Ymd");
        $datestart = date('Ymd', strtotime(now(). ' -1 week'));
        
        $arr = array();
        $totalamount = 0;
        for ($x = $datestart; $x <= $dateend; $x++) {
            $found = 0;
            foreach($data as $row) {
                if ($row->DateOrder == $x) {
                    // array_push($arr, $row->DateDisplay.': '.$row->Amount);
                    array_push($arr, $row->Amount);
                    $totalamount += $row->Amount;
                    $found = 1;
                }                
            }
            if ($found == 0) {
                $date = date('j M',strtotime(substr($x,0,4).'-'.substr($x,4,2).'-'.substr($x,6,2)));
                // array_push($arr, $date.': 0');
                array_push($arr, 0);
            }   
        } 
        $data = DB::select($query);

        $dateend = date_format(NOW(),"Ymd");
        $datestart = date('Ymd', strtotime(now(). ' -1 week'));
        
        $arr = array();
        $totalamount = 0;
        for ($x = $datestart; $x <= $dateend; $x++) {
            $found = 0;
            foreach($data as $row) {
                if ($row->DateOrder == $x) {
                    // array_push($arr, $row->DateDisplay.': '.$row->Amount);
                    array_push($arr, $row->Amount);
                    $totalamount += $row->Amount;
                    $found = 1;
                }                
            }
            if ($found == 0) {
                $date = date('j M',strtotime(substr($x,0,4).'-'.substr($x,4,2).'-'.substr($x,6,2)));
                // array_push($arr, $date.': 0');
                array_push($arr, 0);
            }
        } 

        $data = [
            'Statistic' => $totalamount,
            'StatisticTitle' => 'Revenue Generated',
            'Series' => [
                [
                    'Name' => 'This Week',
                    'Data' => $arr,
                    'Total' => array_sum($arr),
                ],
                [
                    'Name' => 'Last Two Week',
                    'Data' => $arr1,
                    'Total' => array_sum($arr1),
                ],
            ],
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function posgroupbycustomer(Request $request)
    {
        $query = "SELECT DATE_FORMAT(Date, '%Y%m%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay,(IFNULL(bp.Name,'kosong')) AS Name, SUM(p.TotalAmount) AS Amount
                    FROM pospointofsale p
                    LEFT OUTER JOIN mstbusinesspartner bp ON P.Customer = bp.Oid
                    WHERE p.GCRecord IS NULL 
                    GROUP BY DATE_FORMAT(Date, '%Y%m%d'), bp.Name;";
        $data = DB::select($query);
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    public function posgroupbyuser(Request $request)
    {
        $query = "SELECT DATE_FORMAT(Date, '%Y%m%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay,u.UserName, SUM(p.TotalAmount) AS Amount
                FROM pospointofsale p
                LEFT OUTER JOIN user u ON p.User = u.Oid
                WHERE p.GCRecord IS NULL 
                GROUP BY DATE_FORMAT(Date, '%Y%m%d'), u.Name;";
        $data = DB::select($query);
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }
}
