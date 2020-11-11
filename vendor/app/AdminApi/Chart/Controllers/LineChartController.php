<?php

namespace App\AdminApi\Chart\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LineChartController extends Controller
{
    public function data(Request $request) {
        return $this->list([
            'title' => $request->input('title') ?: "val",
            'subtitle1' => $request->input('subtitle1') ?: "This Week",
            'subtitle2' => $request->input('subtitle2') ?: "Previous Week",
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
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -2 WEEK) AND p.Date <= NOW() ".$criteria."
                GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        $data = DB::select($query);

        // $query = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
        //         FROM pospointofsale p
        //         WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -1 MONTH) AND p.Date <= NOW() ".$criteria."
        //         GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        // $data = DB::select($query);

        $query1 = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
                FROM pospointofsale p
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -1 WEEK) AND p.Date <= NOW() ".$criteria."
                GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        $data1 = DB::select($query1);

        // $query1 = "SELECT DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
        //         FROM pospointofsale p
        //         WHERE p.GCRecord IS NULL AND p.Date >= date_format(NOW(), '%Y-%m-01') AND p.Date <= NOW() ".$criteria."
        //         GROUP BY DATE_FORMAT(Date, '%Y%m%d') ORDER BY Date";
        // $data1 = DB::select($query1);

        // $test = array_sum($this->arrayLast2Weeks($data));
        // dd($test);
        
        $data = [
            'Statistic' => $this->sumTotal($data),
            'StatisticTitle' => $request['title'] ?: "Revenue",
            'Series' => [
                [
                    'Name' => $request['subtitle1'] ?: "This Week",
                    'Data' => $this->arrayLastThisWeeks($data1),
                    'Total' => array_sum($this->arrayLastThisWeeks($data1)),
                ],
                [
                    'Name' => $request['subtitle2'] ?: "Last Two Week",
                    'Data' => $this->arrayLast2Weeks($data),
                    'Total' => array_sum($this->arrayLast2Weeks($data)),
                ],
            ],
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

    private function arrayLast2Weeks($data) {
        $arr = array();
        $totalamount = 0;

        for ($x = 0; $x <= 13; $x++) {
            $found = 0;
            $date = date('Y-m-d', strtotime(now(). ' -'.$x.' day'));
            logger($date);
            foreach($data as $row) {
                if ($row->DateOrder == $date) {
                    array_push($arr, $row->Amount);
                    $totalamount += $row->Amount;
                    $found = 1;
                }                
            }
            if ($found == 0) array_push($arr, 0);            
        }
        return $arr;
    }

    private function arrayLastThisWeeks($data) {
        $arr = array();
        $totalamount = 0;
        $dateend1 = date_format(NOW(),"Y-m-d");
        $datestart1 = date('Y-m-d', strtotime(now(). ' -1 week'));

        for ($x = 0; $x <= 6; $x++) {
            $found = 0;
            $date = date('Y-m-d', strtotime(now(). ' -'.$x.' day'));
            logger($date);
            foreach($data as $row) {
                if ($row->DateOrder == $date) {
                    array_push($arr, $row->Amount);
                    $totalamount += $row->Amount;
                    $found = 1;
                }                
            }
            if ($found == 0) array_push($arr, 0);            
        }
        return $arr;
    }


}
