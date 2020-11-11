<?php

namespace App\AdminApi\Chart\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PieChartController extends Controller
{
    public function data(Request $request) {
        return $this->list([
            'key' => $request->input('key') ?: "Sales Revenue",
            'title' => $request->input('title') ?: "Sales Revenue",
            'module' => $request->input('module') ?: "pos",
            'sum' => $request->input('sum') ?: "amount",
            'criteria' => $request->input('criteria') ?: "",
        ]);
    }

    public function list($request = [])
    {
        $field =$request['key'];
        switch ($request['sum']) {
            case "amount": { $sum = 'p.TotalAmount'; break; }
            case "quantity": { $sum = 'p.Quantity'; break; }
            default: $sum = "p.TotalAmount";
        }
        $field = 'Warehouse';
        switch ($field) {
            case "Sales": { $join = 'LEFT OUTER JOIN mstemployee a ON a.Oid = p.Employee'; break; }
            case "Warehouse": { $join = 'LEFT OUTER JOIN mstwarehouse a ON a.Oid = p.Warehouse'; break; }
            case "Customer": { $join = 'LEFT OUTER JOIN mstbusinesspartner a ON a.Oid = p.Customer'; break; }
            case "POSTable": { $join = 'LEFT OUTER JOIN postable a ON a.Oid = p.POSTable'; break; }
            case "PaymentMethod": { $join = 'LEFT OUTER JOIN mstpaymentmethod a ON a.Oid = p.PaymentMethod'; break; }
            case "Supplier": { $join = 'LEFT OUTER JOIN mstbusinesspartner a ON a.Oid = p.Supplier'; break; }
            default:
                $join = 'LEFT OUTER JOIN user a ON a.Oid = p.User';
        }

        if($field == "ItemGroup") {
            $query = "SELECT ig.Oid AS GroupID, ig.Name AS GroupName,
                DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$sum.") AS Amount
                FROM pospointofsale p
                LEFT OUTER JOIN pospointofsaledetail pd ON pd.PointOfSale = p.Oid
                LEFT OUTER JOIN mstitem i ON i.Oid = pd.Item
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND i.".$field." IS NOT NULL
                GROUP BY i.".$field." ORDER BY Date;";
        }else {
            $query = "SELECT a.Oid AS GroupID, a.Name AS GroupName,
                DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$sum.") AS Amount
                FROM pospointofsale p
                ".$join."
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND p.".$field." IS NOT NULL
                GROUP BY p.".$field." ORDER BY Date;";
        }
        $data = DB::select($query);

        $dataArray = $this->arrayLastDays($data);

        $key= array();
        foreach ($dataArray as $row) {
            $count[]=$row['Count'];
            $key[]=$row['Key'];
        }

        $data = [
            'Title' => $request['title'] ?: "Customer",
            'AnalyticsData' => $dataArray,
            'Series' => $count,
            'Labels' => $key
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    private function arrayLastDays($data) {
        $arr = array();
        $i = 0;
       
        foreach($data as $row) {
            logger($row->Amount);
            array_push($arr, [
                'Key' => $row->GroupName,
                'Count' => $row->Amount,
                'Color' => getChartColor($i),
            ]);
            $i += 1;             
        }
        return $arr;
    }
}
