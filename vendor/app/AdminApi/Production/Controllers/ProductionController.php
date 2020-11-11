<?php

namespace App\AdminApi\Production\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Production\Entities\Production;
use App\Core\Production\Entities\ProductionOrderItemDetail;
use App\Core\Production\Entities\ProductionProcess;
use App\Core\Production\Entities\ProductionOrderItem; 
use App\Core\Security\Services\RoleModuleService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Validator;

class ProductionController extends Controller
{
    protected $roleService;
    
    public function __construct(RoleModuleService $roleService){        
        $this->roleService = $roleService;
    }

    public function index(Request $request)
    {        
        try {            
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = Production::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('orderitemdetail') && $request->has('process')){
                $data->where('ProductionOrderItemDetail', $request->input('orderitemdetail'))->where('ProductionProcess', $request->input('process'));
            }
            $data = $data->orderBy('Date')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }
    
    public function show(Production $data)
    {
        try {            
            $data = Production::with(['ProductionOrderItemDetailObj','ProductionProcessObj','UserObj'])->findOrFail($data->Oid);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {       
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        
        $messsages = array(
            'ProductionOrderItemDetail.required'=>__('_.ProductionOrderItemDetail').__('error.required'),
            'ProductionOrderItemDetail.exists'=>__('_.ProductionOrderItemDetail').__('error.exists'),
            'ProductionProcess.required'=>__('_.ProductionProcess').__('error.required'),
            'ProductionProcess.exists'=>__('_.ProductionProcess').__('error.exists'),
        );
        $rules = array(
            'Details.*.ProductionOrderItemDetail' => 'required|exists:prdorderitemdetail,Oid',
            'Details.*.ProductionProcess' => 'required|exists:prdprocess,Oid',              
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $detail = [];
            DB::transaction(function () use ($request, $Oid, &$detail) {

                foreach ($request->Details as $row) {
                    $productionOrderItemDetail = ProductionOrderItemDetail::with('ProductionOrderItemObj.ItemProduct1Obj')->findOrFail($row->ProductionOrderItemDetail);
                    $productionOrderItem = $productionOrderItemDetail->ProductionOrderItemObj;
                    $item = $productionOrderItem->ItemProduct1Obj;
                    $process = ProductionProcess::findOrFail($row->ProductionProcess);

                    $qtyBefore = 0;
                    // $query = "SELECT pip.Oid,pip.ProductionProcess,p.Name AS ProcessName 
                    //     FROM prditemprocess pip
                    //     LEFT OUTER JOIN prdprocess p ON p.Oid = pip.ProductionProcess
                    //     WHERE pip.Item = '{$item->Oid}' AND pip.ProductionProcess != '{$row->ProductionProcess}' AND pip.Sequence <= ".$process->Sequence." 
                    //     ORDER BY p.Sequence DESC LIMIT 1";
                    $query = "SELECT pip.Oid,pip.ProductionProcess,p.Name AS ProcessName 
                        FROM prdorderitemprocess pip
                        LEFT OUTER JOIN prdprocess p ON p.Oid = pip.ProductionProcess
                        WHERE pip.ProductionOrderItem = '{$productionOrderItem->Oid}'
                        AND pip.ProductionProcess != '{$row->ProductionProcess}' 
                        AND p.Sequence <= ".$process->Sequence." AND pip.Valid = 1
                        ORDER BY p.Sequence DESC LIMIT 1";
                    $processBefore = DB::select($query);

                    if ($processBefore) {
                        $query = "SELECT 
                            CASE WHEN p.ProductionProcess = '{$processBefore[0]->ProductionProcess}' THEN SUM(IFNULL(p.QuantityProduction,0)) - SUM(IFNULL(p.QuantityReject,0)) ELSE 0 END QtyBefore
                            FROM prdproduction p 
                            WHERE p.ProductionOrderItemDetail = '{$row->ProductionOrderItemDetail}' 
                            AND p.ProductionProcess = '{$processBefore[0]->ProductionProcess}'
                            GROUP BY p.ProductionOrderItemDetail, p.ProductionProcess";
                        $dataCheckBefore = DB::select($query);
                        $qtyBefore = $dataCheckBefore[0]->QtyBefore;
                        $processBefore = $processBefore[0]->ProcessName;
                    } else {
                        $qtyBefore = $productionOrderItemDetail->Quantity;
                        $processBefore = "Cutting";
                    }
                    $qtyCurrent = 0; $strNote = '';
                    $query = "SELECT p.Note,
                        CASE WHEN p.ProductionProcess = '{$row->ProductionProcess}' THEN SUM(IFNULL(p.QuantityProduction,0)) - SUM(IFNULL(p.QuantityReject,0)) ELSE 0 END QtyCurrent
                        FROM prdproduction p 
                        WHERE p.ProductionOrderItemDetail = '{$row->ProductionOrderItemDetail}' 
                        AND p.ProductionProcess = '{$row->ProductionProcess}'
                        GROUP BY p.ProductionOrderItemDetail, p.ProductionProcess";
                    $dataCheckCurrent = DB::select($query);
                    if ($dataCheckCurrent) {
                        $qtyCurrent = $dataCheckCurrent[0]->QtyCurrent;
                        $qtyCurrent = $dataCheckCurrent[0]->QtyCurrent;
                    }

                    $query = "SELECT po.Code AS NoOrder, CONCAT(ip.Name,' - ',ig.Name) AS Item,poid.Quantity 
                        FROM prdorder po
                        LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                        LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                        LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                        LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                        WHERE poid.Oid = '{$row->ProductionOrderItemDetail}' ";
                    $dataOrder = DB::select($query);

                    //CHECK PRODUKSI = QtyProduksi + QtySkg - QtyReject > QtySblmnya
                    // dd($row->QuantityProduction.' + '.$qtyCurrent.' - '.$row->QuantityReject.' > '.$qtyBefore);
                    if ($row->QuantityProduction > 0) {
                        // dd($row->QuantityProduction.' > '.$qtyBefore);
                        if ($row->QuantityProduction > $qtyBefore) {
                            // dd($dataOrder[0]);
                            echo 'No Order : '.$dataOrder[0]->NoOrder.PHP_EOL.'Item : '.$dataOrder[0]->Item.PHP_EOL.'Qty Ordered : '.$dataOrder[0]->Quantity.PHP_EOL.
                            'Process Now : '.$process->Name.PHP_EOL.'Qty Now : '.$qtyCurrent.PHP_EOL.'Process Before : '.$processBefore.PHP_EOL.
                            'Qty Before : '.$qtyBefore.PHP_EOL.'Qty Process : '.$row->QuantityProduction;
                            die();
                        }
                        // dd($row->QuantityProduction.' + '.$qtyCurrent.' - '.$row->QuantityReject.' > '.$qtyBefore);
                        if ($row->QuantityProduction + $qtyCurrent - $row->QuantityReject > $qtyBefore) {
                            // dd($dataOrder[0]);
                            echo 'No Order : '.$dataOrder[0]->NoOrder.PHP_EOL.'Item : '.$dataOrder[0]->Item.PHP_EOL.'Qty Ordered : '.$dataOrder[0]->Quantity.PHP_EOL.
                            'Process Now : '.$process->Name.PHP_EOL.'Qty Now : '.$qtyCurrent.PHP_EOL.'Process Before : '.$processBefore.PHP_EOL.
                            'Qty Before : '.$qtyBefore.PHP_EOL.'Qty Process : '.$row->QuantityProduction;
                            die();
                        }
                    }

                    //CHECK REJECT = QuantityReject <= QtyProduksi + QtyOK
                    // dd($row->QuantityReject.' > '.$row->QuantityProduction.' + '.$qtyCurrent);
                    if ($row->QuantityReject > 0) {
                        // dd($row->QuantityProduction.' + '.$qtyCurrent.' - '.$row->QuantityReject.' > '.$qtyBefore);
                        if ($row->QuantityReject > $row->QuantityProduction + $qtyCurrent) {
                            // dd($dataOrder[0]);
                            echo 'No Order : '.$dataOrder[0]->NoOrder.PHP_EOL.'Item : '.$dataOrder[0]->Item.PHP_EOL.'Qty Ordered : '.$dataOrder[0]->Quantity.PHP_EOL.
                            'Process Now : '.$process->Name.PHP_EOL.'Qty Now : '.$qtyCurrent.PHP_EOL.'Process Before : '.$processBefore.PHP_EOL.
                            'Qty Before : '.$qtyBefore.PHP_EOL.'Qty Reject : '.$row->QuantityReject;
                            die();
                        }
                    }

                    if (!$Oid) $data = new Production();
                    else $data = Production::findOrFail($Oid);
                    // $data->Company = Auth::user()->Company;
                    $data->ProductionOrderItemDetail = $row->ProductionOrderItemDetail;
                    $data->ProductionProcess = $row->ProductionProcess;
                    $data->QuantityProduction = $row->QuantityProduction;
                    $data->QuantityReject = $row->QuantityReject;
                    $data->Note = $row->Note;
                    if ($data->QuantityReject > 0) $data->NoteReject = $request->NoteReject;
                    $data->Date = Carbon::parse($row->Date)->format('Y-m-d');
                    $data->User = Auth::user()->Oid;
                    if (isset($request->ProductionQuestionnaireDetail)) $data->ProductionQuestionnaireDetail = $request->ProductionQuestionnaireDetail;
                    $data->save();
                    $data->fresh();
                    $detail[] = $data;

                    if ($row->Note) {
                        $query = "UPDATE prdproduction SET Note ='{$row->Note}' 
                            WHERE ProductionOrderItemDetail = '{$row->ProductionOrderItemDetail}' 
                            AND ProductionProcess = '{$row->ProductionProcess}'";
                        DB::update($query);
                    }

                    if ($row->QuantityReject != 0) {
                        $query = "INSERT INTO prdproduction (Oid, Company, Date, ProductionOrderItemDetail, ProductionProcess, QuantityReject, ProductionReject)
                            SELECT UUID(), p.Company,NOW(),'".$row->ProductionOrderItemDetail."', p.ProductionProcess, $row->QuantityReject, '".$data->Oid."'
                            FROM prdorderitemprocess p 
                            LEFT OUTER JOIN prdprocess pro ON pro.Oid = p.ProductionProcess
                            WHERE p.ProductionOrderItem = '{$productionOrderItem->Oid}' AND p.ProductionProcess != '{$row->ProductionProcess}' AND pro.Sequence <= '{$process->Sequence}' AND p.Valid=1";
                        DB::insert($query);
                    }

                    if(!$data) throw new \Exception('Data is failed to be saved');
                }
            });

            $data = [
                // 'Url' => 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf',
                'Details' => $detail
            ];            

            // $data = (new ProductionResource($detail))->type('detail');
            return response()->json(
                $data, Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(Production $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->delete();
            });
            return response()->json(
                null, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function listProduction(Request $request)
    {        
        try {   
            $query = "SELECT po.Oid, po.Code, po.Date AS Date, po.DeliveryDate AS DeliveryDate , bp.Name AS BusinessPartner, poip.ProductionProcess,
                SUM(IFNULL(prd.QuantityOrdered,0)) AS Ordered, 
                -- SUM(CASE WHEN prd.Date <= DATE_FORMAT(NOW(), '%Y-%m-%d') THEN IFNULL(prd.QuantityProduction,0) - IFNULL(prd.QuantityReject,0) ELSE 0 END) AS Done,
                -- SUM(CASE WHEN prd.Date <= DATE_FORMAT(NOW(), '%Y-%m-%d') THEN IFNULL(prd.QuantityReject,0) ELSE 0 END) AS Reject,
                SUM(IFNULL(prd.QuantityProduction,0) - IFNULL(prd.QuantityReject,0)) AS Done,
                SUM(IFNULL(prd.QuantityReject,0)) AS Reject,
                SUM(IFNULL(prd.QuantityOrdered,0) - IFNULL(prd.QuantityProduction,0) + IFNULL(prd.QuantityReject,0)) AS Outstanding
                FROM prdorder po
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN prdprocess p ON p.Oid = poip.ProductionProcess
                LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND poip.ProductionProcess = prd.ProductionProcess
                LEFT OUTER JOIN sysstatus s ON s.Oid = po.Status
                WHERE po.GCRecord IS NULL AND poip.Valid = 1 AND s.Code = 'posted'";
            if ($request->has('businesspartner'))  {
                $query.= " AND po.Customer = '{$request->input('businesspartner')}'";
            }
            // if ($request->has('date'))  {
            //     $query.= " AND po.Date = '{$request->input('date')}'";
            // }
            // if ($request->has('deliverydate'))  {
            //     $query.= " AND po.DeliveryDate = '{$request->input('deliverydate')}'";
            // }
            if ($request->has('process'))  {
                $query.= " AND poip.ProductionProcess = '{$request->input('process')}'";
            }
            $query.= " GROUP BY po.Oid, po.Code, po.Date, po.DeliveryDate, bp.Name, bp.Code,poip.ProductionProcess;";  
            $data = DB::select($query);

            $result = [];
            $role = $this->roleService->list('Production');
            foreach ($data as $row) {
                // po.Oid ProductionOrder, poi.Oid ProductionOrderItem, pb.Oid, pb.Code, pb.Name, 
                $query = "SELECT po.Oid, SUM(IFNULL(prd.QuantityProduction,0)-IFNULL(prd.QuantityReject,0)) AS Quantity
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON po.Oid = poi.ProductionOrder
                    LEFT OUTER JOIN prdorderitemdetail poid ON poi.Oid = poid.ProductionOrderItem
                    LEFT OUTER JOIN prdorderitemprocess pip ON poi.Oid = pip.ProductionOrderItem
                    LEFT OUTER JOIN prdproduction prd ON prd.ProductionProcess = pip.ProductionProcessBefore AND prd.ProductionOrderItemDetail = poid.Oid
                    LEFT OUTER JOIN prdprocess p ON p.Oid = pip.ProductionProcess
                    LEFT OUTER JOIN prdprocess pb ON pb.Oid = pip.ProductionProcessBefore
                    WHERE p.Oid='{$row->ProductionProcess}' 
                    AND po.Oid = '{$row->Oid}' 
                    AND pip.Valid = 1
                    GROUP BY po.Oid;";
                $maxQuantity = DB::select($query);

                if ($row->Outstanding != 0) {
                    $result[] = [
                        'Oid' => $row->Oid,
                        'Code' => $row->Code,
                        'Date' => Carbon::parse($row->Date)->format('d/m'),
                        'DeliveryDate' => Carbon::parse($row->DeliveryDate)->format('d/m'),
                        'BusinessPartner' => $row->BusinessPartner,
                        'ProductionProcess' => $row->ProductionProcess,
                        'Ordered' => $row->Ordered,
                        'Max' => $maxQuantity ? $maxQuantity[0]->Quantity : 0,
                        'Done' => $row->Done,
                        'Reject' => $row->Reject,
                        'Outstanding' => $row->Outstanding,
                        'Role' => $this->GenerateRole($role)
                    ];
                }
            }

            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }

    public function listProductionItem(Request $request)
    {
        try {
            $process = ProductionProcess::findOrFail($request->input('process'));
            $query = "SELECT po.Code,
                po.Date,
                s.Name AS StatusName,
                po.DeliveryDate,
                bp.Name AS BusinessPartner,
                bp.ContactPerson,
                bp.FullAddress
                FROM prdorder po 
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
            WHERE po.GCRecord IS NULL  AND po.Oid ='{$request->input('order')}'";
            $data = DB::select($query);            

            $query2 = "SELECT poi.Oid AS ProductionOrderItem,
                ip.Name AS ItemProduction, ig.Name AS ItemGlass, th.Name AS ProductionThickness,
                CONCAT(ip.Name, ' - ', ig.Name) AS Item,
                poip.ProductionProcess,p.Sequence,
                SUM(IFNULL(prd.QuantityOrdered,0)) AS Ordered, 
                SUM(IFNULL(prd.QuantityProduction,0)) AS Production,
                SUM(IFNULL(prd.QuantityReject,0)) AS Reject,
                SUM(IFNULL(prd.QuantityProduction,0) - IFNULL(prd.QuantityReject,0)) AS Done,
                SUM(IFNULL(prd.QuantityOrdered,0) - IFNULL(prd.QuantityProduction,0) + IFNULL(prd.QuantityReject,0)) AS Outstanding,
                CASE WHEN prd.Note IS NOT NULL THEN prd.Note ELSE p.Remark END AS Note
                FROM prdorder po
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN prdprocess p ON p.Oid = poip.ProductionProcess
                LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND poip.ProductionProcess = prd.ProductionProcess
                LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prditemglass pig ON pig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prdthickness th ON th.Oid = pig.ProductionThickness
                WHERE po.GCRecord IS NULL AND po.Oid = '{$request->input('order')}' AND poip.ProductionProcess = '{$process->Oid}' AND poip.Valid = 1
                GROUP BY poi.CreatedAt, ip.Name, ig.Name, th.Name, poip.ProductionProcess, poi.Oid;";
            $data2 = DB::select($query2);

            foreach($data2 as $row) {
                // po.Oid ProductionOrder, poi.Oid ProductionOrderItem, pb.Oid, pb.Code, pb.Name, 
                $query = "SELECT poi.Oid, SUM(IFNULL(prd.QuantityProduction,0)-IFNULL(prd.QuantityReject,0)) AS Quantity
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON po.Oid = poi.ProductionOrder
                    LEFT OUTER JOIN prdorderitemdetail poid ON poi.Oid = poid.ProductionOrderItem
                    LEFT OUTER JOIN prdorderitemprocess pip ON poi.Oid = pip.ProductionOrderItem
                    LEFT OUTER JOIN prdproduction prd ON prd.ProductionProcess = pip.ProductionProcessBefore AND prd.ProductionOrderItemDetail = poid.Oid
                    LEFT OUTER JOIN prdprocess p ON p.Oid = pip.ProductionProcess
                    LEFT OUTER JOIN prdprocess pb ON pb.Oid = pip.ProductionProcessBefore
                    WHERE p.Oid='{$row->ProductionProcess}' 
                    AND poi.Oid = '{$row->ProductionOrderItem}' 
                    AND pip.Valid = 1
                    GROUP BY poi.Oid;";
                $maxQuantity = DB::select($query);
                $row->Max = $maxQuantity ? $maxQuantity[0]->Quantity : 0;
            }

            foreach ($data as $row) {
                $row->ProcessName = $process->Name.' - '.$process->Code;
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

    public function listProductionItemShipping(Request $request)
    {
        try {
            $data = [];
            $process = ProductionProcess::where('Code','Shp')->firstOrFail();
            $codeOrder = $request->input('code');
            $orderItems = ProductionOrderItem::whereHas('ProductionOrderObj', function ($query) use ($codeOrder) {
                $query->where('Code', $codeOrder);
            })->get();
            
            foreach ($orderItems as $orderItem){
                $query = "SELECT poid.Oid AS ProductionOrderItemDetail, poi.Oid AS ProductionOrderItem,
                    ip.Name AS ItemProduction, ig.Name AS ItemGlass, th.Name AS ProductionThickness,
                    poid.Code AS Code,
                    CONCAT(ip.Name, ' - ', ig.Name) AS Item,
                    CONCAT(poid.Width, ' x ', poid.Height) AS ItemSpec,
                    poid.Width,poid.Height,poip.ProductionProcess,p.Sequence,
                    SUM(IFNULL(prd.QuantityOrdered,0)) AS Ordered, 
                    SUM(IFNULL(prd.QuantityProduction,0)) AS Production,
                    SUM(IFNULL(prd.QuantityReject,0)) AS Reject,
                    -- SUM(CASE WHEN prd.Date <= DATE_FORMAT(NOW(), '%Y-%m-%d') THEN IFNULL(prd.QuantityProduction,0) - IFNULL(prd.QuantityReject,0) ELSE 0 END) AS Done,
                    SUM(IFNULL(prd.QuantityProduction,0) - IFNULL(prd.QuantityReject,0)) AS Done,
                    SUM(IFNULL(prd.QuantityOrdered,0) - IFNULL(prd.QuantityProduction,0) + IFNULL(prd.QuantityReject,0)) AS Outstanding,
                    CASE WHEN prd.Note IS NOT NULL THEN prd.Note ELSE p.Remark END AS Note
                    FROM prdorder po
                    LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                    LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                    LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN prdprocess p ON p.Oid = poip.ProductionProcess
                    LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND poip.ProductionProcess = prd.ProductionProcess
                    LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                    LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                    LEFT OUTER JOIN prditemglass pig ON pig.Oid = poi.ItemGlass1
                    LEFT OUTER JOIN prdthickness th ON th.Oid = pig.ProductionThickness
                    WHERE po.GCRecord IS NULL AND poi.Oid = '{$orderItem->Oid}' AND poip.ProductionProcess = '{$process->Oid}' AND poip.Valid = 1
                    GROUP BY poid.Sequence, poid.Oid, ip.Name, ig.Name, th.Name, poid.Code, poid.Width, poid.Height,poip.ProductionProcess;";
                $data = DB::select($query);
            }

            foreach ($data as $row) {
                if ($row->Sequence > 1) {
                    $query = "SELECT pip.Oid,pip.ProductionProcess,p.Name AS ProcessName 
                        FROM prdorderitemprocess pip
                        LEFT OUTER JOIN prdprocess p ON p.Oid = pip.ProductionProcess
                        WHERE pip.ProductionOrderItem = '{$row->ProductionOrderItem}'
                        AND pip.ProductionProcess != '{$row->ProductionProcess}' 
                        AND p.Sequence <= ".$row->Sequence." AND pip.Valid = 1
                        ORDER BY p.Sequence DESC LIMIT 1";
                    $processBefore = DB::select($query);
    
                    $query = "SELECT 
                        CASE WHEN p.ProductionProcess = '{$processBefore[0]->ProductionProcess}' THEN SUM(IFNULL(p.QuantityProduction,0)) - SUM(IFNULL(p.QuantityReject,0)) ELSE 0 END QtyBefore
                        FROM prdproduction p 
                        WHERE p.ProductionOrderItemDetail = '{$row->ProductionOrderItemDetail}' 
                        AND p.ProductionProcess = '{$processBefore[0]->ProductionProcess}'
                        GROUP BY p.ProductionOrderItemDetail, p.ProductionProcess";
                    $dataCheckBefore = DB::select($query);
                    if($dataCheckBefore[0]->QtyBefore < 0){
                        $row->Previous = 0;
                    }else{
                        $row->Previous = $dataCheckBefore[0]->QtyBefore;
                    }
                } else if ($row->Sequence == 1){
                    $row->Previous = $row->Ordered;
                } else $row->Previous = 0;
            }
               
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }

    public function listProductionDetail(Request $request)
    {        //ga ada masuk
        try {
            $process = ProductionProcess::findOrFail($request->input('process'));
            $query = "SELECT po.Code,
                po.Date,
                s.Name AS StatusName,
                po.DeliveryDate,
                bp.Name AS BusinessPartner,
                bp.ContactPerson,
                bp.FullAddress,
                CASE WHEN ip1.Name IS NULL THEN NULL ELSE CONCAT(ip1.Name, ' - ', ig1.Name) END AS ItemProduction1,
                CASE WHEN ip2.Name IS NULL THEN NULL ELSE CONCAT(ip2.Name, ' - ', ig2.Name) END AS ItemProduction2,
                CASE WHEN ip3.Name IS NULL THEN NULL ELSE CONCAT(ip3.Name, ' - ', ig3.Name) END AS ItemProduction3,
                CASE WHEN ip4.Name IS NULL THEN NULL ELSE CONCAT(ip4.Name, ' - ', ig4.Name) END AS ItemProduction4,
                CASE WHEN ip5.Name IS NULL THEN NULL ELSE CONCAT(ip5.Name, ' - ', ig5.Name) END AS ItemProduction5,
                ps.Name AS ProductionShape,
                poi.Note AS NoteParent,
                po.Discount1, 
                po.Discount2,
                poi.Image,
                poi.Oid AS ProductionOrderItem
                FROM prdorder po 
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN mstitem ip1 ON ip1.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig1 ON ig1.Oid = poi.ItemGlass1
                LEFT OUTER JOIN mstitem ip2 ON ip2.Oid = poi.ItemProduct2
                LEFT OUTER JOIN mstitem ig2 ON ig2.Oid = poi.ItemGlass2
                LEFT OUTER JOIN mstitem ip3 ON ip3.Oid = poi.ItemProduct3
                LEFT OUTER JOIN mstitem ig3 ON ig3.Oid = poi.ItemGlass3
                LEFT OUTER JOIN mstitem ip4 ON ip4.Oid = poi.ItemProduct4
                LEFT OUTER JOIN mstitem ig4 ON ig4.Oid = poi.ItemGlass4
                LEFT OUTER JOIN mstitem ip5 ON ip5.Oid = poi.ItemProduct5
                LEFT OUTER JOIN mstitem ig5 ON ig5.Oid = poi.ItemGlass5
                LEFT OUTER JOIN prdshape ps ON ps.Oid = poi.ProductionShape
                LEFT OUTER JOIN sysstatus s ON po.Status = s.Oid
            WHERE po.GCRecord IS NULL  AND poi.Oid ='{$request->input('orderitem')}'
            ORDER BY poi.Oid";
            $data = DB::select($query);

            $query = "SELECT p.Code, p.Name, p.Sequence FROM prdorderitemprocess poip LEFT OUTER JOIN prdprocess p ON poip.ProductionProcess = p.Oid WHERE poip.ProductionOrderItem = '{$request->input('orderitem')}' AND Valid=1 ORDER BY p.Sequence";
            $allprocess = DB::select($query);

            $query2 = "SELECT poid.Oid AS ProductionOrderItemDetail, poi.Oid AS ProductionOrderItem,
                ip.Name AS ItemProduction, ig.Name AS ItemGlass, th.Name AS ProductionThickness,
                poid.Code AS Code,
                CONCAT(ip.Name, ' - ', ig.Name) AS Item,
                CONCAT(poid.Width, ' x ', poid.Height) AS ItemSpec,
                poid.Width,poid.Height,poip.ProductionProcess,p.Sequence,
                SUM(IFNULL(prd.QuantityOrdered,0)) AS Ordered, 
                SUM(IFNULL(prd.QuantityProduction,0)) AS Production,
                SUM(IFNULL(prd.QuantityReject,0)) AS Reject,
                -- SUM(CASE WHEN prd.Date <= DATE_FORMAT(NOW(), '%Y-%m-%d') THEN IFNULL(prd.QuantityProduction,0) - IFNULL(prd.QuantityReject,0) ELSE 0 END) AS Done,
                SUM(IFNULL(prd.QuantityProduction,0) - IFNULL(prd.QuantityReject,0)) AS Done,
                SUM(IFNULL(prd.QuantityOrdered,0) - IFNULL(prd.QuantityProduction,0) + IFNULL(prd.QuantityReject,0)) AS Outstanding,
                CASE WHEN prd.Note IS NOT NULL THEN prd.Note ELSE p.Remark END AS Note
                FROM prdorder po
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = po.Customer
                LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN prdprocess p ON p.Oid = poip.ProductionProcess
                LEFT OUTER JOIN prdproduction prd ON prd.ProductionOrderItemDetail = poid.Oid AND poip.ProductionProcess = prd.ProductionProcess
                LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prditemglass pig ON pig.Oid = poi.ItemGlass1
                LEFT OUTER JOIN prdthickness th ON th.Oid = pig.ProductionThickness
                WHERE po.GCRecord IS NULL AND poi.Oid = '{$request->input('orderitem')}' AND poip.ProductionProcess = '{$process->Oid}' AND poip.Valid = 1
                GROUP BY poid.Sequence, poid.Oid, ip.Name, ig.Name, th.Name, poid.Code, poid.Width, poid.Height,poip.ProductionProcess;";
            $data2 = DB::select($query2);
           
            foreach ($data2 as $row) {
                if ($row->Sequence > 1) {
                    $query = "SELECT pip.Oid,pip.ProductionProcess,p.Name AS ProcessName 
                        FROM prdorderitemprocess pip
                        LEFT OUTER JOIN prdprocess p ON p.Oid = pip.ProductionProcess
                        WHERE pip.ProductionOrderItem = '{$row->ProductionOrderItem}'
                        AND pip.ProductionProcess != '{$row->ProductionProcess}' 
                        AND p.Sequence <= ".$row->Sequence." AND pip.Valid = 1
                        ORDER BY p.Sequence DESC LIMIT 1";
                    $processBefore = DB::select($query);
    
                    $query = "SELECT 
                        CASE WHEN p.ProductionProcess = '{$processBefore[0]->ProductionProcess}' THEN SUM(IFNULL(p.QuantityProduction,0)) - SUM(IFNULL(p.QuantityReject,0)) ELSE 0 END QtyBefore
                        FROM prdproduction p 
                        WHERE p.ProductionOrderItemDetail = '{$row->ProductionOrderItemDetail}' 
                        AND p.ProductionProcess = '{$processBefore[0]->ProductionProcess}'
                        GROUP BY p.ProductionOrderItemDetail, p.ProductionProcess";
                    $dataCheckBefore = DB::select($query);
                    if($dataCheckBefore[0]->QtyBefore < 0){
                        $row->Previous = 0;
                    }else{
                        $row->Previous = $dataCheckBefore[0]->QtyBefore;
                    }
                } else if ($row->Sequence == 1){
                    $row->Previous = $row->Ordered;
                } else $row->Previous = 0;
            }
            
            foreach ($data as $row) {
                $row->ProcessName = $process->Name.' - '.$process->Code;
                $row->Process = $allprocess;
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

    private function generateRole($role = null) {
        if (!$role) $role = $this->roleService->list('Production');

        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $role->IsEdit,
            'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
        ];
    }
}
