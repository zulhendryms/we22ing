<?php

namespace App\AdminApi\Chart\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BarChartController extends Controller
{
    public function data(Request $request)
    {
        $key =$request->input('key');
        switch ($request->input('sum')) {
            case "amount": { $field = 'p.TotalAmount'; break; }
            case "quantity": { $field = 'p.Quantity'; break; }
        }

        switch ($request->input('key')) {
            case "Warehouse": { $join = 'LEFT OUTER JOIN mstwarehouse a ON a.Oid = p.Warehouse'; break; }
            case "Customer": { $join = 'LEFT OUTER JOIN mstbusinesspartner a ON a.Oid = p.Customer'; break; }
            case "User": { $join = 'LEFT OUTER JOIN user a ON a.Oid = p.User'; break; }
            case "POSTable": { $join = 'LEFT OUTER JOIN postable a ON a.Oid = p.POSTable'; break; }
            case "PaymentMethod": { $join = 'LEFT OUTER JOIN mstpaymentmethod a ON a.Oid = p.PaymentMethod'; break; }
            case "Supplier": { $join = 'LEFT OUTER JOIN mstbusinesspartner a ON a.Oid = p.Supplier'; break; }
        }

       if($key == "ItemGroup") {
            $query = "SELECT ig.Oid AS GroupID, ig.Name AS GroupName,
                DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
                FROM pospointofsale p
                LEFT OUTER JOIN pospointofsaledetail pd ON pd.PointOfSale = p.Oid
                LEFT OUTER JOIN mstitem i ON i.Oid = pd.Item
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND i.".$key." IS NOT NULL
                GROUP BY i.".$key." ORDER BY Date;";
        }else {
            $query = "SELECT a.Oid AS GroupID, a.Name AS GroupName,
                DATE_FORMAT(Date, '%Y-%m-%d') AS DateOrder, DATE_FORMAT(Date, '%e %b') AS DateDisplay, SUM(".$field.") AS Amount
                FROM pospointofsale p
                ".$join."
                WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND p.".$key." IS NOT NULL
                GROUP BY p.".$key." ORDER BY Date;";
        }
        $data = DB::select($query);

        $dataArray = $this->arrayLastDays($data,$key);

        $key= array();
        foreach ($dataArray as $row) {
            $count[]=$row['Count'];
            $key[]=$row['Key'];
            $color[]=$row['Color'];
        }

        $data = [
            'Title' => $request->input('title') ?: "Bar Horizontal",
            'Label' => $request->input('subtitle') ?: "Price",
            'Color' => $color,
            'Data' => $count,
            'Labels' => $key
        ];
        
        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }

    private function arrayLastDays($data, $key) {
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
