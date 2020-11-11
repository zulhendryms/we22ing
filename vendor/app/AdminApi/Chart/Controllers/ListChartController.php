<?php

namespace App\AdminApi\Chart\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ListChartController extends Controller
{
    public function data(Request $request)
    {
        $criteria ='';
        if ($request->has('criteria')) $criteria = " AND ".$request->input('criteria');
        $query = "SELECT DATE_FORMAT(p.Date, '%Y-%m-%d') AS DateStart, DATE_FORMAT(p.Ended, '%Y-%m-%d') AS DateEnded, SUM(p.Amount) AS Amount, s.Name AS StatusName
            FROM possession p
            LEFT OUTER JOIN sysstatus s ON s.Oid = p.Status
            WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND p.Ended IS NOT NULL ".$criteria."
            GROUP BY DATE_FORMAT(p.Date, '%Y%m%d') ORDER BY p.Date";
        $data = DB::select($query);

        $query2 = "SELECT u.Name, u.Image
            FROM possession p
            LEFT OUTER JOIN user u ON u.Oid = p.User
            WHERE p.GCRecord IS NULL AND p.Date >= DATE_ADD(NOW(), INTERVAL -7 DAY) AND p.Date <= NOW() AND p.Ended IS NOT NULL ".$criteria."
            GROUP BY p.User ORDER BY p.Date";
        $data2 = DB::select($query2);
        
        foreach ($data as $row) {
            logger($row->StatusName);
            if ($row->StatusName == "ENTRY") $row->StatusColor = "success";
            if ($row->StatusName == "PAID") $row->StatusColor = "danger";
            if ($row->StatusName == "CANCELLED") $row->StatusColor = "warning";
            $row->UsersLiked = $data2;
        }

        $data = array_merge(['Title' => $request->input('title')], [
            'Details' => $data
        ]);

        return response()->json(
            $data,
            Response::HTTP_OK
        );
    }
}
