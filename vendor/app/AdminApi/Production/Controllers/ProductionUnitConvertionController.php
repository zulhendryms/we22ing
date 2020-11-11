<?php

namespace App\AdminApi\Production\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Production\Entities\ProductionUnitConvertion;
use App\Core\Production\Entities\ProductionUnitConvertionDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\AutoNumberService;
use Validator;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ProductionUnitConvertionController extends Controller
{
    private $autoNumberService;
    protected $roleService;
    private $crudController;
    public function __construct(RoleModuleService $roleService, AutoNumberService $autoNumberService)
    {
        $this->roleService = $roleService;
        $this->autoNumberService = $autoNumberService;
        $this->crudController = new CRUDDevelopmentController();
    }
    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = serverSideConfigField('Code');
        $fields[] = serverSideConfigField('Name');
        return $fields;
    }

    public function config(Request $request) {
        $fields = $this->crudController->jsonConfig($this->fields());
        foreach ($fields as &$row) { //combosource
            if ($row['headerName']  == 'Company') $row['source'] = comboselect('company');
        };
        return $fields;
    }
    public function list(Request $request) {
        $fields = $this->fields();
        $data = DB::table('prdunitconvertion as data')
        ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
        ;
        $data = $this->crudController->jsonList($data, $this->fields(), $request, 'prduniconversion');
        $role = $this->roleService->list('ProductionUnitConvertion'); //rolepermission
        foreach($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
        return $this->crudController->jsonListReturn($data, $fields);
    }
    public function index(Request $request)
    {        
        try {            
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = ProductionUnitConvertion::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            $data = $data->orderBy('Name')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function show(ProductionUnitConvertion $data)
    {
        try {            
            $data = ProductionUnitConvertion::with(['Details'])->findOrFail($data->Oid);
            // return $data;
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
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
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Name' => 'required|max:255',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            $data = new ProductionUnitConvertion();
            DB::transaction(function () use ($request, &$data) {
                if ($request->Code == '<<AutoGenerate>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                $disabled = ['Oid','Details','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();  

                if ($data->Code == '<<AutoGenerate>>') $data->Code = $this->autoNumberService->generate($data, 'prdunitconvertion');
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
        );
        $rules = array(
            'Code' => 'required|max:255',
            'Name' => 'required|max:255',
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            $data = ProductionUnitConvertion::findOrFail($Oid);
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
                            $detail = ProductionUnitConvertionDetail::findOrFail($rowdb->Oid);
                            $detail->delete();
                        }
                    }
                }
                if($request->Details) {
                    $details = [];  
                    $disabled = ['Oid','ProductionUnitConvertion','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy'];
                    foreach ($request->Details as $row) {
                        if (isset($row->Oid)) {
                            $detail = ProductionUnitConvertionDetail::findOrFail($row->Oid);
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
                            $details[] = new ProductionUnitConvertionDetail($arr);
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

    public function destroy(ProductionUnitConvertion $data)
    {
        try {            
            DB::transaction(function () use ($data) {
                $data->Details()->delete();
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

}
