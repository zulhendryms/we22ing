<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Internal\Entities\ItemType;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\Currency;
use App\Core\Internal\Entities\PriceMethod;
use App\Core\Internal\Entities\CompanyDisable;
use App\Core\Master\Entities\ItemTypePriceMethod;
use App\Core\Travel\Entities\TravelPriceBusinessPartner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ItemTypeController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
        )
        {
        $this->module = 'sysitemtype';
            $this->roleService = $roleService; 
            $this->crudController = new CRUDDevelopmentController();
        }

    public function config(Request $request) {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function presearch(Request $request) {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }
    

    public function list(Request $request) {
        try {
            $data = DB::table($this->module.' as data');
            if (CompanyDisable::where('Modules','Travel')->where('Field','Travel')->first()->IsDisable ?: 0) $data = $data->where('Code','<>','Travel');
            if (CompanyDisable::where('Modules','Travel')->where('Field','Transport')->first()->IsDisable ?: 0) $data = $data->where('Code','<>','Transport');
            if (CompanyDisable::where('Modules','Travel')->where('Field','Hotel')->first()->IsDisable ?: 0) $data = $data->where('Code','<>','Hotel');
            $data = $this->crudController->list($this->module, $data, $request);
            
            foreach($data->data as $row) $row->Role =  [
                'SetPriceForGlobal' => true,
                'Country' => true,
                'BusinessPartnerGroup' => true,
            ];
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function index(Request $request) {
        try {
            $data = DB::table($this->module.' as data');
            $data = ItemType::where('IsActive', true);
            if (CompanyDisable::where('Modules','Travel')->where('Field','Travel')->first()->IsDisable ?: 0) $data->where('Code','<>','Travel');
            if (CompanyDisable::where('Modules','Travel')->where('Field','Transport')->first()->IsDisable ?: 0) $data->where('Code','<>','Transport');
            if (CompanyDisable::where('Modules','Travel')->where('Field','Hotel')->first()->IsDisable ?: 0) $data->where('Code','<>','Hotel');
            $data = $this->crudController->index($this->module,$data,$request,false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    private function showSub($Oid)
    {
        $data = $this->crudController->detail($this->module, $Oid);
        $data->Action = $this->action($data);
        return $data;
    }

    public function show(ItemType $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, false);
                if (!$data) throw new \Exception('Data is failed to be saved');
            });

            $data = $this->showSub($data->Oid);
            return response()->json(
                $data,
                Response::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(ItemType $data)
    {
        DB::transaction(function () use ($data) {
            $data->delete();
        });
        return response()->json(
            null, Response::HTTP_NO_CONTENT
        );
    }

    public function getPriceMethod($Oid = null)
    {
        try {            
            $ItemTypePriceMethod = ItemTypePriceMethod::with(['SalesAddMethodObj','SalesAdd1MethodObj','SalesAdd2MethodObj','SalesAdd3MethodObj','SalesAdd4MethodObj','SalesAdd5MethodObj'])->where('ItemType',$Oid)->first();
            if(!$ItemTypePriceMethod){
                $ItemTypePriceMethod = new ItemTypePriceMethod();
                $ItemTypePriceMethod->save();
                $ItemTypePriceMethod->ItemType = $Oid;
                $ItemTypePriceMethod->save();
                $idItemPriceMethod = $ItemTypePriceMethod->Oid;
                $ItemTypePriceMethod = $ItemTypePriceMethod->Oid;
            }else{
                $idItemPriceMethod = $ItemTypePriceMethod->Oid;
            }
            $result = $ItemTypePriceMethod;
            $result->Oid = $Oid;
            $result->ItemTypePriceMethod = $idItemPriceMethod;
            unset($result->ItemType);
            return response()->json(
                $result,
                Response::HTTP_OK
            );

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function savePriceMethod(Request $request, $Oid = null)
    {  
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

        try {            
            if (!$Oid) throw new \Exception('Data is failed to be saved');
            $data = ItemType::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                    $ItemTypePriceMethod = ItemTypePriceMethod::where('ItemType',$data->Oid)->first();
                    if (!$ItemTypePriceMethod) $ItemTypePriceMethod = new ItemTypePriceMethod();
                    $ItemTypePriceMethod->ItemType = $data->Oid;
                    // $ItemTypePriceMethod->Code = $request->Code;
                    // $ItemTypePriceMethod->Name = $request->Name;
                    $ItemTypePriceMethod->IsActive = 1;
                    $ItemTypePriceMethod->SalesAddMethod = $request->SalesAddMethod;
                    $ItemTypePriceMethod->SalesAddAmount1 = $request->SalesAddAmount1;
                    $ItemTypePriceMethod->SalesAddAmount2 = $request->SalesAddAmount2;
                    $ItemTypePriceMethod->SalesAdd1Method = $request->SalesAdd1Method;
                    $ItemTypePriceMethod->SalesAdd1Amount1 = $request->SalesAdd1Amount1;
                    $ItemTypePriceMethod->SalesAdd1Amount2 = $request->SalesAdd1Amount2;
                    $ItemTypePriceMethod->SalesAdd2Method = $request->SalesAdd2Method;
                    $ItemTypePriceMethod->SalesAdd2Amount1 = $request->SalesAdd2Amount1;
                    $ItemTypePriceMethod->SalesAdd2Amount2 = $request->SalesAdd2Amount2;
                    $ItemTypePriceMethod->SalesAdd3Method = $request->SalesAdd3Method;
                    $ItemTypePriceMethod->SalesAdd3Amount1 = $request->SalesAdd3Amount1;
                    $ItemTypePriceMethod->SalesAdd3Amount2 = $request->SalesAdd3Amount2;
                    $ItemTypePriceMethod->SalesAdd4Method = $request->SalesAdd4Method;
                    $ItemTypePriceMethod->SalesAdd4Amount1 = $request->SalesAdd4Amount1;
                    $ItemTypePriceMethod->SalesAdd4Amount2 = $request->SalesAdd4Amount2;
                    $ItemTypePriceMethod->SalesAdd5Method = $request->SalesAdd5Method;
                    $ItemTypePriceMethod->SalesAdd5Amount1 = $request->SalesAdd5Amount1;
                    $ItemTypePriceMethod->SalesAdd5Amount2 = $request->SalesAdd5Amount2;
                    $ItemTypePriceMethod->save();
                    
                    // $items = Item::with('Details')->where('IsParent',1)->where('ItemType',$data->Oid)->where('IsUsingPriceMethod',1)->get();
                    $items = Item::with('ParentObj')->whereHas('ParentObj', function ($query) {
                        $query->whereNull('GCRecord');
                    })->where('IsDetail',1)->where('ItemType',$data->Oid)->where('IsUsingPriceMethod',1)->whereNotNull('ParentOid')->get();
                    $ipm = $ItemTypePriceMethod;
                    
                    logger('Items '.$items->count());
                    if ($items->count() != 0) {
                        $i=1;
                        foreach ($items as $row) {
                            logger('Items '.$i.' '.$row->Oid);
                            $i=$i+1;
                            // dd ($row->Oid.' '.$row->ParentOid);
                            if($row->ParentObj->IsUsingPriceMethod && $row->IsUsingPriceMethod){
                                //update only those detail not manual price, and parent also not manual
                                $detail = Item::findOrFail($row->Oid);
                                $itemType = $detail->ItemType ? $detail->ItemTypeObj->Code : null;
                                if(isset($ipm)){
                                    if($itemType == 'Outbound'){
                                       
                                        $dataOutbound = $detail->TravelItemOutboundObj;
                                       
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesSGL = $this->calcPriceOB('', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesTWN = $this->calcPriceOB('', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesTRP = $this->calcPriceOB('', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesQuad = $this->calcPriceOB('', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesQuint = $this->calcPriceOB('', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCHT = $this->calcPriceOB('', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCWB = $this->calcPriceOB('', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAddMethod)) $dataOutbound->SalesCNB = $this->calcPriceOB('', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesSGL1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesTWN1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesTRP1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesQuad1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesQuint1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCHT1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCWB1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd1Method)) $dataOutbound->SalesCNB1 = $this->calcPriceOB('1', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesSGL2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesTWN2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesTRP2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesQuad2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesQuint2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCHT2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCWB2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd2Method)) $dataOutbound->SalesCNB2 = $this->calcPriceOB('2', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesSGL3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesTWN3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesTRP3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesQuad3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesQuint3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCHT3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCWB3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd3Method)) $dataOutbound->SalesCNB3 = $this->calcPriceOB('3', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesSGL4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesTWN4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesTRP4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesQuad4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesQuint4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCHT4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCWB4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd4Method)) $dataOutbound->SalesCNB4 = $this->calcPriceOB('4', $ipm, $dataOutbound,'CNB');
    
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesSGL5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'SGL');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesTWN5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'TWN');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesTRP5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'TRP');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesQuad5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'Quad');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesQuint5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'Quint');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCHT5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CHT');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCWB5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CWB');
                                        if (isset($ipm->SalesAdd5Method)) $dataOutbound->SalesCNB5 = $this->calcPriceOB('5', $ipm, $dataOutbound,'CNB');
    
                                        $dataOutbound->save();
                                    }else{
                                        if (isset($ipm->SalesAddMethod))  $detail->SalesAmount = $this->calcPrice('', $ipm, $row);
                                        if (isset($ipm->SalesAdd1Method)) $detail->SalesAmount1 = $this->calcPrice('1', $ipm, $row);
                                        if (isset($ipm->SalesAdd2Method)) $detail->SalesAmount2 = $this->calcPrice('2', $ipm, $row);
                                        if (isset($ipm->SalesAdd3Method)) $detail->SalesAmount3 = $this->calcPrice('3', $ipm, $row);
                                        if (isset($ipm->SalesAdd4Method)) $detail->SalesAmount4 = $this->calcPrice('4', $ipm, $row);
                                        if (isset($ipm->SalesAdd5Method)) $detail->SalesAmount5 = $this->calcPrice('5', $ipm, $row);

                                        $detail->save();
                                    } 
                                } 
                            }
                        }
                    }
                    $data->ItemTypePriceMethod = $ItemTypePriceMethod->Oid;
                    $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

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

    public function listPriceGlobalMarkup(Request $request)
    {
        try {
            $itemtype = $request->input('itemtype');
            $data = TravelPriceBusinessPartner::with(['BusinessPartnerGroupObj'])->where('ItemType', $itemtype);
            $data = $data->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function detailListPriceGlobalMarkup($Oid) {        
        try {
            $data = TravelPriceBusinessPartner::findOrFail($Oid);
            $data->BusinessPartnerGroupName = $data->BusinessPartnerGroupObj ? $data->BusinessPartnerGroupObj->Name.' - '.$data->BusinessPartnerGroupObj->Code : null;
            unset($data->BusinessPartnerGroupObj);
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }


    public function savePriceGlobalMarkup(Request $request, $Oid = null)
    {        
        $itemtype = ItemType::where('Oid',$request->input('itemtype'))->firstOrFail();    
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        
        $messsages = array(
            'BusinessPartnerGroup.required'=>__('_.BusinessPartnerGroup').__('error.required'),
            'BusinessPartnerGroup.exists'=>__('_.BusinessPartnerGroup').__('error.exists'),
        );
        $rules = array(
            'BusinessPartnerGroup' => 'required|exists:mstbusinesspartnergroup,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {        
            if (!$Oid) $data = new TravelPriceBusinessPartner();
            else $data = TravelPriceBusinessPartner::findOrFail($Oid);
            DB::transaction(function () use ($request,$itemtype, &$data) {
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->ItemType = $itemtype->Oid;
                // $data->BusinessPartnerGroupName = $data->BusinessPartnerGroupObj ? $data->BusinessPartnerGroupObj->Name : null;
                $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

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

    public function destroyPriceGlobalMarkup(TravelPriceBusinessPartner $data)
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

    private function calcPrice($priceMethodNo, $itemPriceMethod, $item) {
        $salesMethod = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Method'};
        $salesAmount1 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount1'};
        $salesAmount2 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount2'};
        
        $curPurch = Currency::findOrFail($item->ParentObj->PurchaseCurrency);
        $cost = $curPurch->convertRate($item->ParentObj->SalesCurrency, $item->PurchaseAmount);
        if ($cost == 0) return 0;
        $data = PriceMethod::findOrFail($salesMethod);
        switch ($data->Code) {
            case "Percentage": return $cost + (($cost * $salesAmount1)/100);
            case "Amount": return $cost + $salesAmount1;
            case "PercentageAmount": return $cost + (($cost * $salesAmount1)/100) + $salesAmount2;
            case "AmountPercentage": return $cost + $salesAmount1 + ((($cost + $salesAmount1) * $salesAmount2)/100);
        }
    }

    private function calcPriceOB($priceMethodNo, $itemPriceMethod, $itemoutbound, $key) {
        $item = Item::findOrFail($itemoutbound->Oid);
        $salesMethod = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Method'};
        $salesAmount1 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount1'};
        $salesAmount2 = $itemPriceMethod->{'SalesAdd'.$priceMethodNo.'Amount2'};
        
        $curPurch = Currency::findOrFail($item->ParentObj->PurchaseCurrency);
        $cost = $curPurch->convertRate($item->ParentObj->SalesCurrency, $itemoutbound->{'Purchase'.$key});
        if ($cost == 0) return 0;
        $data = PriceMethod::findOrFail($salesMethod);
        switch ($data->Code) {
            case "Percentage": return $cost + (($cost * $salesAmount1)/100);
            case "Amount": return $cost + $salesAmount1;
            case "PercentageAmount": return $cost + (($cost * $salesAmount1)/100) + $salesAmount2;
            case "AmountPercentage": return $cost + $salesAmount1 + ((($cost + $salesAmount1) * $salesAmount2)/100);
        }
    }
}
