<?php

namespace App\AdminApi\Production\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Production\Entities\ProductionPriceProcess;
use App\Core\Production\Entities\ProductionPriceProcessDetail;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ProductionPriceProcessController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        $this->module = 'prdproductionprice';
        $this->crudController = new CRUDDevelopmentController();
    }

    public function config(Request $request)
    {
        try {
            return $this->crudController->config($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function presearch(Request $request)
    {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function list(Request $request)
    {
        try {
            $data = DB::table($this->module.' as data');
            $data = $this->crudController->list($this->module, $data, $request,true);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function index(Request $request)
    {
        $data = DB::table('prdpriceprocess as data');
        $data = $this->crudController->getIndex($data, $request, 'Name');
        if ($request->has('process')) $data->where('ProductionPriceProcess', $request->input('process'));
        $data = $data->orderBy('Name')->get();
        return response()->json($data, Response::HTTP_OK);
    }

    private function showSub($Oid)
    {
        $data = ProductionPriceProcess::whereNull('GCRecord');
        $data = $this->crudController->detail('prdpriceprocess', $data, $Oid);
        $data->Details = $data->Details->sortBy(function ($data, $key) {
            $data = $data->ProductionPriceProcessObj;
            $data = $data->ThicknessUntil;
        });
        return $data;
    }

    public function show(ProductionPriceProcess $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function create(Request $request)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        $messsages = array(
            'Code.required'=>__('_.Code').__('error.required'),
            'Code.max'=>__('_.Code').__('error.max'),
            'Name.required'=>__('_.Name').__('error.required'),
            'Name.max'=>__('_.Name').__('error.max'),
            'Currency.required'=>__('_.Currency').__('error.required'),
            'Currency.exists'=>__('_.Currency').__('error.exists'),
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Name' => 'required|max:255',
            'Currency' => 'required|exists:mstcurrency,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            $data = new ProductionPriceProcess();
            DB::transaction(function () use ($request, &$data) {
                if ($request->Code == '<<AutoGenerate>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                $disabled = ['Oid','Details','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();  
                if ($data->Code == '<<AutoGenerate>>') $data->Code = $this->autoNumberService->generate($data, 'prdpriceprocess');

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

    public function edit(Request $request, $Oid = null)
    {        
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $dataArray = object_to_array($request);
        $messsages = array(
            'Code.required'=>__('_.Code').__('error.required'),
            'Code.max'=>__('_.Code').__('error.max'),
            'Name.required'=>__('_.Name').__('error.required'),
            'Name.max'=>__('_.Name').__('error.max'),
            'Currency.required'=>__('_.Currency').__('error.required'),
            'Currency.exists'=>__('_.Currency').__('error.exists'),
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Name' => 'required|max:255',
            'Currency' => 'required|exists:mstcurrency,Oid',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            $data = ProductionPriceProcess::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                if ($request->Code == '<<AutoGenerate>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                $disabled = ['Oid','Details','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();        

                if ($data->Details()->count() != 0) {
                    foreach ($data->Details as $rowdb) {
                        $found = false;               
                        foreach ($request->Details as $rowapi) {
                            if (isset($rowapi->Oid)) {
                                if ($rowdb->Oid == $rowapi->Oid) $found = true;
                            }
                        }
                        if (!$found) {
                            $detail = ProductionPriceProcessDetail::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if($request->Details) {
                    $details = [];  
                    $disabled = ['Oid','ProductionPriceProcess','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                    foreach ($request->Details as $row) {
                        if (isset($row->Oid)) {
                            $detail = ProductionPriceProcessDetail::findOrFail($row->Oid);
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;
                                $detail->{$field} = $row->{$field};
                            }
                            $detail->save();
                        } else {
                            $arr = [];
                            foreach ($row as $field => $key) {
                                if (in_array($field, $disabled)) continue;                            
                                $arr = array_merge($arr, [
                                    $field => $row->{$field},
                                ]);
                            }
                            $details[] = new ProductionPriceProcessDetail($arr);
                        }
                    }
                    $data->Details()->saveMany($details);
                    $data->load('Details');
                    $data->fresh();
                }

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

    public function destroy(ProductionPriceProcess $data)
    {
        try {
            return $this->crudController->delete($this->module, $data);
        } catch (\Exception $e) {
  return response()->json(
  errjson($e), 
Response::HTTP_UNPROCESSABLE_ENTITY);
}
    }

    public function detailfields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = ['w'=> 0, 'h'=>0, 't'=>'text', 'n'=>'ProductionPriceProcess',];
        $fields[] = ['w'=> 0, 'h'=>0, 't'=>'int', 'n'=>'RangeUntil',];
        $fields[] = ['w'=> 0, 'h'=>0, 't'=>'double', 'n'=>'Price',];
        $fields[] = ['w'=> 0, 'h'=>0, 't'=>'int', 'n'=>'ThicknessUntil',];
        $fields[] = ['w'=> 0, 'h'=>0, 't'=>'double', 'n'=>'PriceFeet',];
        return $fields;
    }
    public function detailconfig(Request $request) {
        $fields = $this->crudController->jsonConfig($this->detailfields(),true);
        foreach ($fields as &$row) { //combosource
        }
        $result = [];
        $result[] = [
            "fieldToSave" => "Details",
            "addButton" => true,
            "showPopup" => false,
            "data" => $fields
        ];
        return $result;
    }

    public function autocomplete(Request $request)
    {
        $type = $request->input('type') ?: 'combo';
        $term = $request->term;
        $data = ProductionPriceProcess::whereNull('GCRecord');
        if ($request->has('process')) $data->where('ProductionProcess', $request->input('process'));
        $data->where(function($query) use ($term)
        {
            
            $query->where('Name','LIKE','%'.$term.'%')
            ->orWhere('Code','LIKE','%'.$term.'%');
        });
        $data = $data->orderBy('Name')->take(10)->get();
        
        return $data;
    }

}
