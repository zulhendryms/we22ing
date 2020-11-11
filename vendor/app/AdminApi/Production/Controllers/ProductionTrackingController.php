<?php

namespace App\AdminApi\Production\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Services\RoleModuleService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class ProductionTrackingController extends Controller
{
    protected $roleService;
    
    public function __construct(RoleModuleService $roleService){        
        $this->roleService = $roleService;
    }

    public function trackingsummary(Request $request)
    {        
        try {
            $query = "SELECT po.Oid, CONCAT(po.Code, ' - ', bp.Name) AS Code, DATE_FORMAT(po.Date, '%m/%d') AS Date,
                DATE_FORMAT(po.DeliveryDate, '%m/%d') AS DeliveryDate, 
                bp.Name AS BusinessPartner, SUM(IFNULL(poid.Quantity,0)) AS Qty,
                DATE_FORMAT(po.DeliveryDate, '%Y-%m-%d') AS CompleteDeliveryDate, DATE_FORMAT(po.Date, '%Y-%m-%d') AS CompleteOrderDate
                FROM prdorder po
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN mstitemgroup igg ON ig.ItemGroup = igg.Oid
                LEFT OUTER JOIN prditemglass pig ON pig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prdthickness th ON th.Oid = pig.ProductionThickness
                LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN sysstatus s ON s.Oid = po.Status
                WHERE po.GCRecord IS NULL AND s.Code = 'posted'";
            if ($request->has('businesspartner'))  $query.= " AND po.Customer = '{$request->input('businesspartner')}'";
            if ($request->has('itemglass'))  $query.= " AND ig.Oid = '{$request->input('itemglass')}'";
            if ($request->has('itemproduction'))  $query.= " AND ip.Oid = '{$request->input('itemproduction')}'";
            if ($request->has('orderdatefrom'))  $query.= " AND po.Date >= '{$request->input('orderdatefrom')}'";
            if ($request->has('orderdateto'))  $query.= " AND po.Date <= '{$request->input('orderdateto')}'";
            if ($request->has('deldatefrom'))  $query.= " AND po.DeliveryDate >= '{$request->input('deldatefrom')}'";
            if ($request->has('deldateto'))  $query.= " AND po.DeliveryDate <= '{$request->input('deldateto')}'";
            if ($request->has('widthfrom'))  $query.= " AND poid.Width >= '{$request->input('widthfrom')}'";
            if ($request->has('widthto'))  $query.= " AND poid.Width <= '{$request->input('widthto')}'";
            if ($request->has('heightfrom'))  $query.= " AND poid.Height >= '{$request->input('heightfrom')}'";
            if ($request->has('heightto'))  $query.= " AND poid.Height <= '{$request->input('heightto')}'";
            if ($request->has('thicknessfrom'))  $query.= " AND th.Sequence >= '{$request->input('thicknessfrom')}'";
            if ($request->has('thicknessto'))  $query.= " AND th.Sequence <= '{$request->input('thicknessto')}'";

            $query.= " GROUP BY po.Oid, po.Code, po.Date, po.DeliveryDate, bp.Name, bp.Code ORDER BY po.Date DESC LIMIT 100;";
            $data = DB::select($query);

            $role = $this->roleService->list('ProductionTracking');
            foreach ($data as $row) {
                $query = "SELECT po.Oid, p.Code AS ProcessCode, CONCAT(p.Name,' - ',p.Code) AS Process, SUM(IFNULL(prd.QuantityProduction,0)-IFNULL(prd.QuantityReject,0)) AS Qty, poip.Valid
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                    LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN prdprocess p ON p.Oid = poip.ProductionProcess
                    LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND poip.ProductionProcess = prd.ProductionProcess                    
                    WHERE prd.GCRecord IS NULL AND po.Oid = '".$row->Oid."'
                    GROUP BY p.Sequence, po.Oid, p.Name, p.Code, poip.Valid;";   
                $detail = DB::select($query);
                foreach ($detail as $rowdetail) {
                    $row->{$rowdetail->ProcessCode} = ($rowdetail->Valid ? $rowdetail->Qty : null);
                    $row->Role = $this->GenerateRole($role);
                }
                $date1 = Carbon::parse($row->CompleteDeliveryDate);
                $date2 = Carbon::now();
                $diff = $date1->diffInDays($date2);
                if ($row->Qty <= $row->Shp) $row->Color = 'FFFFFF';
                elseif ($diff > 1) $row->Color = 'FFFFFF';
                elseif ($diff > 0 && $diff <= 1) $row->Color = 'F6FF47';
                else $row->Color = 'FF6C6C'; // F6FF47
                unset($row->CompleteDeliveryDate);
                unset($row->CompleteOrderDate);
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    } 
    
    public function trackingdetail(Request $request)
    {        
        try {   
            $query = "SELECT po.Oid,po.Customer,bp.Name AS CustomerName, DATE_FORMAT(po.Date, '%d %M %Y') AS Date,po.Code,DATE_FORMAT(po.DeliveryDate, '%d %M %Y') AS DeliveryDate,po.Code 
                FROM prdorder po 
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                WHERE po.Oid='{$request->input('order')}'";
            $data = DB::select($query);

            $query2 = "SELECT poid.Oid,ip.Name AS ItemProduction,
                ig.Name AS ItemGlass, th.Name AS ProductionThickness,
                CONCAT(CASE WHEN poid.Code IS NULL THEN '' ELSE CONCAT(poid.Code, ') ') END,ip.Name, ' - ', ig.Name) AS Item,
                CONCAT(poid.Width, ' x ', poid.Height) AS ItemSpec,
                poid.Width,poid.Height, SUM(IFNULL(poid.Quantity,0)) AS Qty
                FROM prdorder po
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prditemglass pig ON pig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prdthickness th ON th.Oid = pig.ProductionThickness
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                WHERE po.GCRecord IS NULL AND po.Oid = '{$request->input('order')}'
                GROUP BY poi.CreatedAt, poid.Sequence, poid.Oid,ip.Name, ig.Name, th.Name, poid.Width,poid.Height";
            $data2 = DB::select($query2);

            foreach ($data2 as $row) {
                $query = " SELECT poid.Oid, p.Code AS ProcessCode, CONCAT(p.Name,' - ',p.Code) AS Process, SUM(IFNULL(prd.QuantityProduction,0)-IFNULL(prd.QuantityReject,0)) AS Qty, poip.Valid
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                    LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN prdprocess p ON p.Oid = poip.ProductionProcess
                    LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND poip.ProductionProcess = prd.ProductionProcess
                    WHERE prd.GCRecord IS NULL AND poid.Oid = '".$row->Oid."'
                    GROUP BY p.Sequence, poid.Oid, p.Name, p.Code;";   
                $detail = DB::select($query);
                foreach ($detail as $rowdetail) {
                    $row->{$rowdetail->ProcessCode} = ($rowdetail->Valid ? $rowdetail->Qty : null);
                }
            }

            foreach ($data as $row) {
                $row->Details = $data2;
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    } 
    
    public function trackingperitem(Request $request)
    {        
        try {   
            $query = "SELECT poid.Oid, ip.Name AS ItemProduction, ig.Name AS ItemGlass, th.Name AS ProductionThickness,poid.Width,poid.Height, poid.Code, ps.Name AS Shape,
                bp.Name AS CustomerName, DATE_FORMAT(po.Date, '%d %M %Y') AS Date,po.Code AS Ordered,DATE_FORMAT(po.DeliveryDate, '%d %M %Y') AS DeliveryDate 
                FROM prdorder po
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prditemglass pig ON pig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prdthickness th ON th.Oid = pig.ProductionThickness
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN prdShape ps ON ps.Oid = poi.ProductionShape
                WHERE poid.Oid = '{$request->input('orderitemdetail')}'
                GROUP BY poid.Oid, ip.Name, ig.Name, th.Name, poid.Width, poid.Height, poid.Code, ps.Name,
                bp.Name, po.Date,po.Code,po.DeliveryDate";
            $data = DB::select($query);

            
            $query = "SELECT DATE_FORMAT(p.Date, '%Y-%m-%d') AS Date
            FROM prdproduction p
            WHERE p.ProductionOrderItemDetail = '{$request->input('orderitemdetail')}'
              GROUP BY p.Date";
            $date = DB::select($query);
            logger($query);

            foreach ($date as $row) {
                $query = "SELECT poid.Oid AS OrderItemDetail, p.Code AS ProcessCode, CONCAT(p.Name,' - ',p.Code) AS Process, SUM(IFNULL(prd.QuantityProduction,0)-IFNULL(prd.QuantityReject,0)) AS Qty,  SUM(IFNULL(poid.Quantity,0)) AS QtyOrder, poip.Valid
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                    LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN prdprocess p ON p.Oid = poip.ProductionProcess
                    LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND poip.ProductionProcess = prd.ProductionProcess
                    WHERE prd.GCRecord IS NULL AND poid.Oid = '{$request->input('orderitemdetail')}' AND prd.Date = '".$row->Date."'
                    GROUP BY p.Sequence, poid.Oid, p.Code, p.Name, p.Code;";   
                $detail = DB::select($query);
                foreach ($detail as $rowdetail) {
                    $row->Qty = $rowdetail->QtyOrder;
                    $row->{$rowdetail->ProcessCode} = ($rowdetail->Valid ? $rowdetail->Qty : null);
                }
            }
            foreach ($data as $row) {
                $row->Details = $date;
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    } 

    private function generateRole($role = null) {
        if (!$role) $role = $this->roleService->list('ProductionTracking');

        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $role->IsEdit,
            'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
        ];
    }
}
