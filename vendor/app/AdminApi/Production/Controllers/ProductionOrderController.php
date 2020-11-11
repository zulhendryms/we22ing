<?php

namespace App\AdminApi\Production\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Production\Entities\ProductionOrder;
use App\Core\Production\Entities\ProductionOrderDetail;
use App\Core\Production\Entities\ProductionOrderItem;
use App\Core\Production\Entities\ProductionUnitConvertionDetail;
use App\Core\Production\Entities\ProductionOrderItemProcess; 
use App\Core\Production\Entities\ProductionPrice;
use App\Core\Production\Entities\ProductionItemGlass;
use App\Core\Production\Entities\ProductionThickness;
use App\Core\Production\Entities\ProductionPriceDetail;
use App\Core\Production\Entities\ProductionPriceProcessDetail;
use App\Core\Production\Entities\ProductionPriceProcess;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\Item;
use App\Core\Internal\Entities\Status;
use Hamcrest\Type\IsNumeric;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Validator;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ProductionOrderController extends Controller
{
    protected $roleService;
    private $autoNumberService;
    private $crudController;
    
    public function __construct(RoleModuleService $roleService, AutoNumberService $autoNumberService){        
        $this->roleService = $roleService;
        $this->autoNumberService = $autoNumberService;
        $this->crudController = new CRUDDevelopmentController();
    }
    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = serverSideConfigField('Code');
        $fields[] = serverSideConfigField('Date');
        $fields[] = ['w'=> 180, 't'=>'combo', 'n'=>'Customer',    'f'=>'bp.Name'];
        $fields[] = ['w'=> 180, 'n'=>'DeliveryDate',];
        $fields[] = serverSideConfigField('Status');
        return $fields;
    }

    public function config(Request $request) {
        $fields = $this->crudController->jsonConfig($this->fields());
        foreach ($fields as &$row) { //combosource
            if ($row['headerName']  == 'Company') $row['source'] = comboselect('company');
        }
        return $fields;
    }
    public function list(Request $request) {
        $fields = $this->fields();
        $data = DB::table('prdorder as data')
            ->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'data.Customer')
            ->leftJoin('sysstatus AS s', 's.Oid', '=', 'data.Status') //rolepermission
            ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
            ;
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'prdorder','Date');
        $role = $this->roleService->list('ProductionOrder');
        $action = $this->roleService->action('ProductionOrder');
        foreach($data as $row) $row->Role = $this->roleService->generateRoleProductionGlass($row, $role, $action);
        return $this->crudController->jsonListReturn($data, $fields);
    }

    public function index(Request $request)
    {        
        try {         
            $status = Status::where('Code','cancel')->first();
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = ProductionOrder::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('cancelled')) $data->where('Status',$status->Oid);
            else $data->where('Status', '!=',$status->Oid);
            $data = $data->get();

            $result = [];
            $role = $this->roleService->list('ProductionOrder');
            $action = $this->roleService->action('ProductionOrder');
            foreach ($data as $row) {
                $result[] = [
                    'Oid' => $row->Oid,
                    'Code' => $row->Code,
                    'Date' => Carbon::parse($row->Date)->format('Y-m-d'),
                    'Customer' => $row->Customer,
                    'DeliveryDate' => Carbon::parse($row->DeliveryDate)->format('Y-m-d'),
                    'Status' => $row->Status,
                    'CustomerName' => $row->CustomerObj ? $row->CustomerObj->Name : null,
                    'StatusName' => $row->StatusObj ? $row->StatusObj->Name : null,
                    'CurrencyCode' => $row->CurrencyObj ? $row->CurrencyObj->Code : null,
                    'Role' => $this->GenerateRole($row, $role, $action)
                ];
            }
            return $result;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function show(ProductionOrder $data)
    {
        try {            
            $order = ProductionOrder::with(['StatusObj','CustomerObj','DepartmentObj','CurrencyObj','Details','Items','Details.ItemObj','Items.ItemProduct1Obj','Items.ItemGlass1Obj','Items.ItemProduct2Obj','Items.ItemGlass2Obj','Items.ItemProduct3Obj',
            'Items.ItemGlass3Obj','Items.ItemProduct4Obj','Items.ItemGlass4Obj','Items.ItemProduct5Obj','Items.ItemGlass5Obj','Items.ProductionShapeObj','Items.ProductionUnitConvertionObj','Items.ProductionUnitConvertionObj.Details'])->findOrFail($data->Oid);
            $order->StatusName = $order->StatusObj ? $order->StatusObj->Name : null;
            
            $role = $this->roleService->list('ProductionOrder');
            $action = $this->roleService->action('ProductionOrder');
            $r = $this->GenerateRole($data, $role, $action);
            $data1 = collect($order);
            $data2 = collect($r);

            $arrReturn = $data1->merge($data2);
            if (isset($arrReturn->Items)) {
                foreach($arrReturn->Items as $row) {         
                    if (isset($row->ItemProduct1Obj)) $row->ItemProduct1Name = $row->ItemProduct1Obj->Name;
                    if (isset($row->ItemGlass1Obj)) $row->ItemGlass1Name = $row->ItemGlass1Obj->Name;
                }
            }
            return $arrReturn;
            // return (new ProductionOrderResource($data))->type('detail');
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
            'Code.required'=>__('_.Code').__('error.required'),
            'Code.max'=>__('_.Code').__('error.max'),
            'Customer.required'=>__('_.Customer').__('error.required'),
            'Customer.exists'=>__('_.Customer').__('error.exists'),
            // 'Status.required'=>__('_.Status').__('error.required'),
            // 'Status.exists'=>__('_.Status').__('error.exists'),
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Customer' => 'required|exists:mstbusinesspartner,Oid',
            // 'Status' => 'required|exists:sysstatus,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            if (!$Oid) $data = new ProductionOrder();
            else $data = ProductionOrder::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                if ($request->Code == '<<AutoGenerate>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                $disabled = ['Oid','Details','Items','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                if (!$data->Oid) $request->Status = Status::where('Code','entry')->first()->Oid;
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();   
                if ($data->Code == '<<AutoGenerate>>') $data->Code = $this->autoNumberService->generate($data, 'prdorder');
                
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            return $data;
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

    public function destroy(ProductionOrder $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->Details()->delete();
                $data->Items()->delete();
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

    public function quoted(ProductionOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {                
                $query = "DELETE FROM prdproduction WHERE ProductionOrderItemDetail IN (
                    SELECT poid.Oid
                    FROM prdorderitemdetail poid
                    LEFT OUTER JOIN prdorderitem poi ON poi.Oid = poid.ProductionOrderItem
                    WHERE poi.ProductionOrder = '{$data->Oid}') AND QuantityOrdered > 0;";
                DB::delete($query);

                if ($data->StatusObj->Code =='entry') {
                    // CALCULATION PROCESS FOR PRODUCTION ITEM
                    $orderItems = ProductionOrderItem::with(['ProductionOrderObj','ProductionShapeObj','OrderItemDetails','OrderItemProcess','OrderItemProcess.ProductionPriceProcessObj','OrderItemProcess.ProductionProcessObj'])->where('ProductionOrder',$data->Oid)->get();
                    foreach ($orderItems as $orderItem){
                        $cur = $orderItem->ProductionOrderObj->Currency;
                        $priceCalculation = [];
                        $isFeet = $orderItem->ProductionUnitConvertion != null;
                        if ($orderItem->ItemProduct1) {
                            $price = ProductionPrice::where('ItemProduct', $orderItem->ItemProduct1)->where('ItemGroup',$orderItem->ItemGlass1Obj->ItemGroup)->first();
                            if (!$price) break;
                            // logger('   1 '.$orderItem->ItemProduct1Obj->Name.' '.$this->getAmount($cur, $price, $orderItem->ItemGlass1, $isFeet));
                            $priceCalculation[] = [
                                'Description' => $orderItem->ItemProduct1Obj->Name,
                                'Method' => 99,
                                'Amount' => $this->getAmount($cur, $price, $orderItem->ItemGlass1, $isFeet),
                                'Value1' => 0, 'Value2' => 0,
                                'IsPriceSeperate' => 0,
                            ];
                        }
                        if ($orderItem->ItemProduct2) {
                            $price = ProductionPrice::where('ItemProduct', $orderItem->ItemProduct2)->where('ItemGroup',$orderItem->ItemGlass2Obj->ItemGroup)->first();
                            if (!$price) break;
                            // logger('   2 '.$orderItem->ItemProduct2Obj->Name.' '.$this->getAmount($cur, $price, $orderItem->ItemGlass2, $isFeet));
                            $priceCalculation[] = [
                                'Description' => $orderItem->ItemProduct2Obj->Name, 
                                'Method' => 99,              
                                'Amount' => $this->getAmount($cur, $price, $orderItem->ItemGlass2, $isFeet),
                                'Value1' => 0, 'Value2' => 0,
                                'IsPriceSeperate' => 0,
                            ];
                        }
                        if ($orderItem->ItemProduct3) {
                            $price = ProductionPrice::where('ItemProduct', $orderItem->ItemProduct3)->where('ItemGroup',$orderItem->ItemGlass3Obj->ItemGroup)->first();
                            if (!$price) break;
                            // logger('   3 '.$orderItem->ItemProduct3Obj->Name.' '.$this->getAmount($cur, $price, $orderItem->ItemGlass3, $isFeet));
                            $priceCalculation[] = [
                                'Description' => $orderItem->ItemProduct3Obj->Name, 
                                'Method' => 99,              
                                'Amount' => $this->getAmount($cur, $price, $orderItem->ItemGlass3, $isFeet),
                                'Value1' => 0, 'Value2' => 0,
                                'IsPriceSeperate' => 0,
                            ];
                        }
                        if ($orderItem->ItemProduct4) {
                            $price = ProductionPrice::where('ItemProduct', $orderItem->ItemProduct4)->where('ItemGroup',$orderItem->ItemGlass4Obj->ItemGroup)->first();
                            if (!$price) break;
                            // logger('   4 '.$orderItem->ItemProduct4Obj->Name.' '.$this->getAmount($cur, $price, $orderItem->ItemGlass4, $isFeet));
                            $priceCalculation[] = [
                                'Description' => $orderItem->ItemProduct4Obj->Name, 
                                'Method' => 99,              
                                'Amount' => $this->getAmount($cur, $price, $orderItem->ItemGlass4, $isFeet),
                                'Value1' => 0, 'Value2' => 0,
                                'IsPriceSeperate' => 0,
                            ];
                        }
                        if ($orderItem->ItemProduct5) {
                            $price = ProductionPrice::where('ItemProduct', $orderItem->ItemProduct5)->where('ItemGroup',$orderItem->ItemGlass5Obj->ItemGroup)->first();
                            if (!$price) break;
                            // logger('   5 '.$orderItem->ItemProduct5Obj->Name.' '.$this->getAmount($cur, $price, $orderItem->ItemGlass5, $isFeet));
                            $priceCalculation[] = [
                                'Description' => $orderItem->ItemProduct5Obj->Name, 
                                'Method' => 99,              
                                'Amount' => $this->getAmount($cur, $price, $orderItem->ItemGlass5, $isFeet),
                                'Value1' => 0, 'Value2' => 0,
                                'IsPriceSeperate' => 0,
                            ];
                        }
                        // logger('Cari process  ProductionOrderItem'.$orderItem->Oid.' whereNotNull ProductionPriceProcess');
                        $processes = ProductionOrderItemProcess::where('ProductionOrderItem', $orderItem->Oid)->whereNotNull('ProductionPriceProcess')->get();
                        foreach($processes as $process) {
                            $price = ProductionPriceProcess::findOrFail($process->ProductionPriceProcess);
                            // logger('   Process '.$process->ProductionProcessObj->Name.' '.$this->getAmountProcess($cur, $price, $orderItem->ItemGlass1));
                            $amt1 = is_numeric(strip_tags($process->AdditionalInfo1)) ? floatval(strip_tags($process->AdditionalInfo1)) : 1;
                            $amt2 = is_numeric(strip_tags($process->AdditionalInfo2)) ? floatval(strip_tags($process->AdditionalInfo2)) : 1;
                            $priceCalculation[] = [
                                'Description' => $process->ProductionProcessObj->Name,    
                                'Method' => $price->Method,           
                                'Amount' => $this->getAmountProcess($cur, $price, $orderItem->ItemGlass1),
                                'Value1' => $amt1, 'Value2' => $amt2,
                                'IsPriceSeperate' => $price->IsPriceSeperate,
                            ];
                        }
                        
                        if ($orderItem->OrderItemDetails()->count() != 0) {   
                            $shape = $orderItem->ProductionShapeObj;
                            foreach($orderItem->OrderItemDetails as $row) {
                                $detailDesc = "";
                                // logger('      Detail: '.$row->Width.' '.$row->Height);
                                $priceCalcDescription = 'W '.$row->Width.' H '.$row->Height.', Calculation: ';
                                $totalAmount = 0; $totalAmountGlass = 0;
                                foreach($priceCalculation as $price) {
                                    if ($price['IsPriceSeperate']) $sep = ' [Plat]'; else $sep = '';
                                    switch ($price['Method']) {
                                        case 99:
                                            if ($row->ProductionOrderItemObj->ProductionUnitConvertion) {
                                                $width = $this->feetConversion($row->ProductionOrderItemObj->ProductionUnitConvertion, $row->Width);
                                                $height = $this->feetConversion($row->ProductionOrderItemObj->ProductionUnitConvertion, $row->Height);
                                            } else {
                                                $width = $row->Width;
                                                $height = $row->Height;
                                            }
                                            $priceCalcDescription = $priceCalcDescription.$price['Description'].' '.$width.'x'.$height.'x'.number_format($price['Amount']).'/1jt'.$sep.';  ';
                                            $salesAmount = ($width ?: 0) *( $height ?: 0);
                                            $salesAmount = $salesAmount * ($price['Amount'] ?: 0)/1000000;
                                            break;
                                        case 0:
                                            $priceCalcDescription = $priceCalcDescription.$price['Description'].' '.$row->Width.'x'.$row->Height.'x'.number_format($price['Amount']).'/1jt'.$sep.';  ';
                                            $salesAmount = ($row->Width ?: 0) *( $row->Height ?: 0);
                                            $salesAmount = $salesAmount * ($price['Amount'] ?: 0)/1000000;
                                            break;
                                        case 1:
                                            $priceCalcDescription = $priceCalcDescription.$price['Description'].' (22/7)x'.$row->Width.'x'.number_format($price['Amount']).'/1rb'.$sep.';  ';
                                            $salesAmount = (22 / 7) * $row->Width;
                                            $salesAmount = $salesAmount * ($price['Amount'] ?: 0)/1000;
                                            break;
                                        case 2:
                                            $priceCalcDescription = $priceCalcDescription.$price['Description'].
                                            ' ('.$row->Width.'x'.$price['Value1'].')+('.$row->Height.'x'.$price['Value2'].')x'.number_format($price['Amount']).'/1rb'.$sep.';  ';
                                            $salesAmount = ($price['Value1'] * $row->Width) + ($price['Value2'] * $row->Height);
                                            $salesAmount = $salesAmount * ($price['Amount'] ?: 0)/1000;
                                            break;
                                        case 3:
                                            $priceCalcDescription = $price['Description'].$sep.';  ';
                                            $salesAmount = $price['Amount'];
                                            break;
                                    }
                                    // $priceCalcDescription = $priceCalcDescription.$price['Description'].' '.$row->Width.'x'.$row->Height.'x'.number_format($price['Amount']).'; ';
                                    // $salesAmount = ($row->Width ?: 0) *( $row->Height ?: 0) * ($price['Amount'] ?: 0);
                                    // logger('          Calc '.$priceCalcDescription.' '.$salesAmount);
                                    if ($price['IsPriceSeperate']) $totalAmountGlass = $totalAmountGlass + $salesAmount;
                                    else $totalAmount = $totalAmount + $salesAmount;
                                }

                                if ($orderItem->OrderItemOthers()->count() != 0) {   
                                    foreach ($orderItem->OrderItemOthers as $orderItemOther){
                                        if ($row->Sequence != $orderItemOther->Sequence) continue;
                                        $item = Item::where('Oid',$orderItemOther->Item)->first();
                                        $priceCalcDescription = $priceCalcDescription.$item->Name.' ('.($orderItemOther->Quantity ?: 0).' x '.($orderItemOther->Amount ?: 0).')'.$sep.';  ';
                                        $salesAmount = ($orderItemOther->Quantity ?: 0) * ($orderItemOther->Amount ?: 0);
                                        if ($orderItem->IsShowName) $detailDesc = $priceCalcDescription.$item->Name.' ('.($orderItemOther->Quantity ?: 0).')';
                                    }
                                }

                                if($shape->Additional > 0){
                                    $priceCalcDescriptionShape = $shape->Name.' +'.$shape->Additional.'%';
                                    $row->SalesAmount = $totalAmount + (($totalAmount * $shape->Additional)/100);
                                    $row->SalesAmountGlass = $totalAmountGlass;
                                    $row->SalesAmountDescription = $priceCalcDescription.'  '.$priceCalcDescriptionShape;
                                }else{
                                    $row->SalesAmount = $totalAmount;
                                    $row->SalesAmountGlass = $totalAmountGlass;
                                    $row->SalesAmountDescription = $priceCalcDescription;
                                }
                                if ($orderItem->ProductionOrderObj->CurrencyObj->Decimal == 0) {
                                    $row->SalesAmount = round($row->SalesAmount, -3);
                                    $row->SalesAmountGlass = round($row->SalesAmountGlass, -3);
                                } else {
                                    $row->SalesAmount = round($row->SalesAmount, 0);
                                    $row->SalesAmountGlass = round($row->SalesAmountGlass, 0);
                                }
                                if ($detailDesc) $row->Remark2 = $detailDesc;
                                $row->save();
                            }
                        }
                    }

                    //GETTING DESCRIPTION FOR PRODUCTION ORDER ITEM
                    foreach ($orderItems as $orderItem){
                        if ($orderItem->OrderItemProcess()->count() != 0) {     
                            $descpriceprocess = '';        
                            foreach($orderItem->OrderItemProcess as $row) {  
                                if($row->ProductionPriceProcess) {
                                    $priceprocess = ProductionPriceProcess::findOrFail($row->ProductionPriceProcess);
                                    if($priceprocess->Initial) $descpriceprocess = $descpriceprocess.' + '.$priceprocess->Initial;
                                } 
                            }
                        }
                        $description = '';
                        if($orderItem->ItemProduct1) $description = ($orderItem->ItemProduct1Obj->Initial ? $orderItem->ItemProduct1Obj->Initial.' ' : '').$orderItem->ItemGlass1Obj->Initial;
                        $description = $description.' '.$descpriceprocess;
                        if($orderItem->ItemProduct2) $description = $description.' + '.($orderItem->ItemProduct2Obj->Initial ? $orderItem->ItemProduct2Obj->Initial.' ' : '').$orderItem->ItemGlass2Obj->Initial;
                        if($orderItem->ItemProduct3) $description = $description.' + '.($orderItem->ItemProduct3Obj->Initial ? $orderItem->ItemProduct3Obj->Initial.' ' : '').$orderItem->ItemGlass3Obj->Initial;
                        if($orderItem->ItemProduct4) $description = $description.' + '.($orderItem->ItemProduct4Obj->Initial ? $orderItem->ItemProduct4Obj->Initial.' ' : '').$orderItem->ItemGlass4Obj->Initial;
                        if($orderItem->ItemProduct5) $description = $description.' + '.($orderItem->ItemProduct5Obj->Initial ? $orderItem->ItemProduct5Obj->Initial.' ' : '').$orderItem->ItemGlass5Obj->Initial;
                                                
                        $orderItem->Description = $description;
                        $orderItem->save();  
                    }
                    
                    // CALCULATION PROCESS FOR NON PRODUCTION ITEM
                    $Subtotalamountitem = 0;
                    $orderDetails = ProductionOrderDetail::with(['ProductionOrderObj','ItemObj'])->where('ProductionOrder',$data->Oid)->get();
                    foreach ($orderDetails as $orderDetail){
                        $Subtotalamountitem += ($orderDetail->Quantity * $orderDetail->Amount);
                    }
    
                    //GETTING TOTAL FOR PRODUCTION ITEM
                    $SubtotalAmount = 0; $SubtotalAmountGlass = 0;
                    foreach ($orderItems as $orderItem){
                        if ($orderItem->OrderItemDetails()->count() != 0) {   
                            $shape = $orderItem->ProductionShapeObj;                    
                            foreach($orderItem->OrderItemDetails as $row) {  
                                // logger($row->Oid.' Kaca: '.number_format($SubtotalAmount).' + '.number_format($row->SalesAmount).' = '.number_format($SubtotalAmount + $row->SalesAmount));
                                // logger($row->Oid.' Plat: '.number_format($SubtotalAmountGlass).' + '.number_format($row->SalesAmountGlass).' = '.number_format($SubtotalAmountGlass + $row->SalesAmountGlass));
                                $SubtotalAmount += $row->SalesAmount * $row->Quantity; 
                                $SubtotalAmountGlass += $row->SalesAmountGlass * $row->Quantity;
                            }
                        }
                    }
    
                    $order = ProductionOrder::findOrFail($data->Oid);
                    $order->SubtotalAmount = $SubtotalAmount;
                    $order->SubtotalAmountGlass = $SubtotalAmountGlass;
                    $order->SubtotalAmountItem = $Subtotalamountitem;
                    $amountForDiscount = $order->SubtotalAmount + $order->SubtotalAmountItem;
                    $order->DiscountAmount1 = $order->Discount1 ? ($amountForDiscount * $order->Discount1)/100 : 0;
                    $order->DiscountAmount2 = $order->Discount2 ? (($amountForDiscount - $order->DiscountAmount1) * $order->Discount2)/100 : 0;
                    $order->TotalAmount = $SubtotalAmount - $order->DiscountAmount1 - $order->DiscountAmount2 + $SubtotalAmountGlass + $Subtotalamountitem;
                    $order->save();
                }

                $data->Status = Status::quoted()->first()->Oid;
                $data->save();
            });
            
            return response()->json(
                $data, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function entry(ProductionOrder $data)
    {
        // $query = "SELECT COUNT(*) AS count FROM prdproduction p 
        //     LEFT OUTER JOIN prdorderitemdetail poid ON p.ProductionOrderItemDetail = poid.Oid
        //     LEFT OUTER JOIN prdorderitem poi ON poi.Oid = poid.ProductionOrderItem
        //     WHERE IFNULL(p.QuantityOrdered,0) = 0 AND p.GCRecord IS NULL AND poi.ProductionOrder = '{$data->Oid}'";
        // $count = DB::select($query);
        // if ($count[0]->count != 0) return response()->json('There is already data at production', Response::HTTP_NOT_FOUND);
        try {
            DB::transaction(function () use ($data) {                
                $orderItems = ProductionOrderItem::with(['OrderItemDetails','OrderItemProcess','OrderItemProcess.ProductionPriceProcessObj','OrderItemProcess.ProductionProcessObj'])->where('ProductionOrder',$data->Oid)->get();
                foreach ($orderItems as $orderItem){
                    if ($orderItem->OrderItemDetails()->count() != 0) {                        
                        foreach($orderItem->OrderItemDetails as $row) {
                            $row->SalesAmount = null;
                            $row->SalesAmountDescription = null;
                            $row->save();
                        }
                    }

                }
                $data->Status = Status::entry()->first()->Oid;
                $data->save();
            });
            
            return response()->json(
                $data, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function post(ProductionOrder $data)
    {
        try {
            DB::transaction(function () use ($data) {                                
                $query = "DELETE FROM prdproduction WHERE ProductionOrderItemDetail IN (
                    SELECT poid.Oid
                    FROM prdorderitemdetail poid
                    LEFT OUTER JOIN prdorderitem poi ON poi.Oid = poid.ProductionOrderItem
                    WHERE poi.ProductionOrder = '{$data->Oid}') AND QuantityOrdered > 0";
                DB::delete($query);

                $query = "INSERT INTO prdproduction (Oid, Company, ProductionOrderItemDetail,ProductionProcess,QuantityOrdered,Date)
                    SELECT UUID(), po.Company, poid.Oid, p.Oid, poid.Quantity,'".$data->Date."'
                    FROM prdorderitemdetail poid
                    LEFT OUTER JOIN prdorderitem poi ON poi.Oid = poid.ProductionOrderItem
                    LEFT OUTER JOIN prdorderitemprocess poip ON poip.ProductionOrderItem = poi.Oid
                    LEFT OUTER JOIN prdprocess p ON p.Oid = poip.ProductionProcess
                    LEFT OUTER JOIN prdorder po ON po.Oid = poi.ProductionOrder
                    WHERE po.Oid = '".$data->Oid."'";
                DB::insert($query);

                $query = "SELECT pip.Oid, pip.ProductionOrderItem, p.Oid ProcessOid, p.Name, p.Sequence, pip.ProductionProcessBefore
                    FROM prdorderitemprocess pip 
                    LEFT OUTER JOIN prdprocess p ON p.Oid = pip.ProductionProcess
                    WHERE pip.Valid = 1 AND pip.ProductionOrderItem 
                    IN (SELECT Oid FROM prdorderitem poi WHERE poi.ProductionOrder ='{$data->Oid}')
                    ORDER BY pip.ProductionOrderItem, p.Sequence";
                $processItem = DB::select($query);

                $processBefore = null;
                foreach($processItem as $row) {                    
                    $tmp = ProductionOrderItemProcess::findOrFail($row->Oid);
                    if ($row->Sequence == 1) $tmp->ProductionProcessBefore = $tmp->ProductionProcess;
                    else $tmp->ProductionProcessBefore = $processBefore;
                    $tmp->save();
                    $processBefore = $tmp->ProductionProcess;
                }

                $data->Status = Status::posted()->first()->Oid;
                $data->save();
            });
            
            return response()->json(
                $data, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function cancelled(ProductionOrder $data)
    {
        // $query = "SELECT COUNT(*) AS count FROM prdproduction p 
        //     LEFT OUTER JOIN prdorderitemdetail poid ON p.ProductionOrderItemDetail = poid.Oid
        //     LEFT OUTER JOIN prdorderitem poi ON poi.Oid = poid.ProductionOrderItem
        //     WHERE IFNULL(p.QuantityOrdered,0) = 0 AND p.GCRecord IS NULL AND poi.ProductionOrder = '{$data->Oid}'";
        // $count = DB::select($query);
        // if ($count[0]->count != 0) return response()->json('There is already data at production', Response::HTTP_NOT_FOUND);
        try {
            DB::transaction(function () use ($data) {
                $query = "DELETE FROM prdproduction WHERE ProductionOrderItemDetail IN (
                    SELECT poid.Oid
                    FROM prdorderitemdetail poid
                    LEFT OUTER JOIN prdorderitem poi ON poi.Oid = poid.ProductionOrderItem
                    WHERE poi.ProductionOrder = '{$data->Oid}') AND QuantityOrdered IS NOT NULL;";
                DB::delete($query);
                $data->Status = Status::cancelled()->first()->Oid;
                $data->CancelledDate = Carbon::now();
                $data->save();
            });
            
            return response()->json(
                $data, Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function feetConversion($unitConversion, $value){
        $data = ProductionUnitConvertionDetail::where('ProductionUnitConvertion', $unitConversion)->whereRaw('RangeUntil >= '.$value)->orderBy('RangeUntil')->first();
        if ($data) if ($value <= $data->RangeUntil) return $data->Amount;
    }

    public function getAmount($curTo, $price, $thickness, $isFeet = 0){
        $thickness = ProductionItemGlass::findOrFail($thickness)->ProductionThicknessObj->Sequence;
        $data = ProductionPriceDetail::where('ProductionPrice',$price->Oid)->whereRaw('ThicknessUntil >= '.$thickness)->orderBy('ThicknessUntil')->first();
        if ($data) {
            $amount = $isFeet ? $data->PriceFeet : $data->Price;
            $cur = $data->ProductionPriceObj->Currency;
            $curFrom = Currency::findOrFail($cur);
            if ($thickness <= $data->ThicknessUntil) return $curFrom->convertRate($curTo, $amount);
        }
    }

    public function getAmountProcess($curTo, $price, $thickness){
        $thickness = ProductionItemGlass::findOrFail($thickness)->ProductionThicknessObj->Sequence;
        $data = ProductionPriceProcessDetail::where('ProductionPriceProcess',$price->Oid)->whereRaw('ThicknessUntil >= '.$thickness)->orderBy('ThicknessUntil')->first();
        if ($data) if ($thickness <= $data->ThicknessUntil) return $data->Price;
    }

    public function quotation(ProductionOrder $data)
    {
        try {
            $idOrder = $data->Oid;
            $query = "SELECT poid.Oid, ip.Name AS ItemProduct,ig.Name AS ItemGlass,poid.Width,poid.Height,IFNULL(poid.SalesAmount,0) AS SalesAmount,IFNULL(poid.SalesAmountGlass,0) AS SalesAmountGlass, poid.SalesAmountDescription
                FROM prdorder po
                LEFT OUTER JOIN prdorderitem poi ON poi.ProductionOrder = po.Oid
                LEFT OUTER JOIN prdorderitemdetail poid ON poid.ProductionOrderItem = poi.Oid
                LEFT OUTER JOIN mstitem ip ON ip.Oid = poi.ItemProduct1
                LEFT OUTER JOIN mstitem ig ON ig.Oid = poi.ItemGlass1
                WHERE po.GCRecord IS NULL AND po.Oid ='{$data->Oid}'";
            $data = DB::select($query);

            $order = ProductionOrder::findOrFail($idOrder);

            $field = ['Oid', 'Quantity', 'Amount','Description','ItemName'];
            $orderDetails = ProductionOrderDetail::where('ProductionOrder',$idOrder)->get();
            foreach ($orderDetails as $orderDetail){
                $orderDetail->setVisible($field);
                $orderDetail->ItemName = $orderDetail->ItemObj ? $orderDetail->ItemObj->Name : null;
            }
            
            $data = [
                'Details'=> $data,
                'Items'=> $orderDetails,
                'SubtotalAmount' => $order->SubtotalAmount,
                'SubtotalAmountGlass' => $order->SubtotalAmountGlass,
                'SubtotalAmountItem' => $order->SubtotalAmountItem,
                'Discount1' => $order->Discount1,
                'Discount2' => $order->Discount2,
                'DiscountAmount1' => $order->DiscountAmount1,
                'DiscountAmount2' => $order->DiscountAmount2,
                'TotalAmount' => $order->TotalAmount,
            ];
            

            return response()->json(
                $data, Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function generateRole(ProductionOrder $data, $role = null, $action = null) {
        if (!$role) $role = $this->roleService->list('ProductionOrder');
        if (!$action) $action = $this->roleService->action('ProductionOrder');
        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $this->roleService->isAllowDelete($data->StatusObj, $role->IsEdit),
            'IsDelete' => 0, //$this->roleService->isAllowDelete($row->StatusObj, $role->IsDelete),
            'Cancel' => $this->roleService->isProductionAllowCancel($data->StatusObj, $action->Cancel),
            'Entry' => $this->roleService->isProductionAllowEntry($data->StatusObj, $action->Entry),
            'Quoted' => $this->roleService->isProductionAllowQuoted($data->StatusObj, $action->Quoted),
            'Post' => $this->roleService->isProductionAllowPost($data->StatusObj, $action->Posted),
            'PrintOrder' => $this->roleService->isProductionPosted($data->StatusObj, 1),
            'PrintQuotation' => $this->roleService->isProductionPosted($data->StatusObj, 1),
        ];
    }

    public function generateDescription()
    {
        $data = ProductionOrder::where('Oid','!=','9d030cb1-621c-476d-a8c4-c47decdde2d8')->where('Oid','!=','d469305a-1eac-4bf7-b3cd-fb10820b694a')->whereNull('GCRecord')->get();
        foreach ($data as $row){
            logger('$row->Oid');
            logger($row->Oid);
            $orderItems = ProductionOrderItem::with(['ProductionOrderObj','ProductionShapeObj','OrderItemDetails','OrderItemProcess','OrderItemProcess.ProductionPriceProcessObj','OrderItemProcess.ProductionProcessObj'])->where('ProductionOrder',$row->Oid)->get();
            if ($orderItems->count() != 0) { 
                foreach ($orderItems as $orderItem){
                    logger('$orderItem');
                    logger($orderItem->Oid);
                    if ($orderItem->OrderItemProcess()->count() != 0) {     
                        $descpriceprocess = '';        
                        foreach($orderItem->OrderItemProcess as $row) {  
                            if($row->ProductionPriceProcess) {
                                $priceprocess = ProductionPriceProcess::findOrFail($row->ProductionPriceProcess);
                                if($priceprocess->Initial) $descpriceprocess = $descpriceprocess.' + '.$priceprocess->Initial;
                            } 
                        }
                    }
                    $description = '';
                    if($orderItem->ItemProduct1) $description = ($orderItem->ItemProduct1Obj->Initial ? $orderItem->ItemProduct1Obj->Initial.' ' : '').$orderItem->ItemGlass1Obj->Initial;
                    $description = $description.' '.$descpriceprocess;
                    if($orderItem->ItemProduct2) $description = $description.' + '.($orderItem->ItemProduct2Obj->Initial ? $orderItem->ItemProduct2Obj->Initial.' ' : '').$orderItem->ItemGlass2Obj->Initial;
                    if($orderItem->ItemProduct3) $description = $description.' + '.($orderItem->ItemProduct3Obj->Initial ? $orderItem->ItemProduct3Obj->Initial.' ' : '').$orderItem->ItemGlass3Obj->Initial;
                    if($orderItem->ItemProduct4) $description = $description.' + '.($orderItem->ItemProduct4Obj->Initial ? $orderItem->ItemProduct4Obj->Initial.' ' : '').$orderItem->ItemGlass4Obj->Initial;
                    if($orderItem->ItemProduct5) $description = $description.' + '.($orderItem->ItemProduct5Obj->Initial ? $orderItem->ItemProduct5Obj->Initial.' ' : '').$orderItem->ItemGlass5Obj->Initial;
                    $orderItem->Description = $description;
                    $orderItem->save();  
                }
            }
        }
    }
    


}
