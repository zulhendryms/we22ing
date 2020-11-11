<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Project; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Base\Services\CoreService;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Internal\Entities\BusinessPartnerRole;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\AutoNumberService;
use App\Core\Internal\Services\ExportExcelService;
use Validator;
use Carbon\Carbon;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ProjectController extends Controller
{
    private $coreService;
    protected $roleService;
    protected $excelExportService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService,
        CoreService $coreService,
        ExportExcelService $excelExportService)
    {
        $this->roleService = $roleService;
        $this->coreService = $coreService;
        $this->excelExportService = $excelExportService;
        $this->module = 'mstproject';
        $this->crudController = new CRUDDevelopmentController();
    }
    public function fields() {    
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = serverSideConfigField('Oid');
        $fields[] = serverSideConfigField('IsActive');
        $fields[] = ['w'=> 250, 'r'=>1, 'h'=>0, 't'=>'text', 'n'=>'Code','ol'=>'Project Code',];
        $fields[] = ['w'=> 250, 'r'=>1, 'h'=>0, 't'=>'text', 'n'=>'Name','ol'=>'Name (Auto Generate)',];
        $fields[] = ['w'=> 200, 'r'=>0, 'h'=>0,  't'=>'combo',  'n'=>'BusinessPartner', 'f'=>'bp.Name','ol'=>'Business Partner',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'combo', 'n'=>'City', 'f'=>'cit.Name','ol'=>'Province',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'combo', 'n'=>'Employee', 'f'=>'e.Name','ol'=>'Employee',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'date', 'n'=>'StartDate','ol'=>'DateStart',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'date', 'n'=>'EndDate','ol'=>'DateEnd',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty', 'ol'=>'Total Pax',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty1', 'ol'=>'Qty Adult',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty2', 'ol'=>'Qty 60-64',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty3', 'ol'=>'Qty 65 Above',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty4', 'ol'=>'Qty Child',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty5', 'ol'=>'Qty 18-30',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty6', 'ol'=>'Qty 30-50',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty7', 'ol'=>'Qty 50-70',];
        $fields[] = ['w'=> 0, 'r'=>0, 't'=>'int', 'n'=>'Qty8', 'ol'=>'Qty 70 Above',];
        return $fields;
    }

    public function config(Request $request) {
        $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        foreach ($fields as &$row) { //combosource
            if ($row['headerName'] == 'Employee') $row['source'] = comboSelect('mstemployee');
            elseif ($row['headerName'] == 'City') $row['source'] = comboSelect('mstcity');
            elseif ($row['headerName']  == 'Company') $row['source'] = comboselect('company');
        }
        
        $fields[0]['topButton'] =[
            [
                'name' => 'Synchronize',
                'icon' => 'RefreshIcon',
                'type' => 'confirm',
                'post' => 'project/sync'
            ]
        ];
        return $fields;
    }
    public function list(Request $request) {
        $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        $data = DB::table('mstproject as data')
        ->leftJoin('mstemployee AS e', 'e.Oid', '=', 'data.Employee')
        ->leftJoin('mstbusinesspartner AS bp', 'bp.Oid', '=', 'data.BusinessPartner')
        ->leftJoin('mstcity AS cit', 'cit.Oid', '=', 'data.City')
        ->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company')
        ;
        $data = jsonList($data, $fields, $request, 'mstproject');
        $role = $this->roleService->list('Project'); //rolepermission
        foreach($data as $row) $row->Action = $this->roleService->generateActionMaster($role);
        return $this->crudController->jsonListReturn($data, $fields);
    }

    public function index(Request $request)
    {        
        try {            
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = Project::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            
            $data = $data->orderBy('Code')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }

    private function showSub($Oid) {
        $data = Project::findOrFail($Oid);
        $data->EmployeeName = $data->EmployeeObj ? $data->EmployeeObj->Name : null;
        $data->BusinessPartnerName = $data->BusinessPartnerObj ? $data->BusinessPartnerObj->Name : null;
        $data->CityName = $data->CityObj ? $data->CityObj->Name : null;
        $data->CompanyName = $data->CompanyObj ? $data->CompanyObj->Name : null;
        return $data;
    }
    
    public function show(Project $data)
    {
        try {            
            // return (new ProjectResource($data))->type('detail');
            return $this->showSub($data->Oid);
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
        );
        $rules = array(
            'Code' => 'required|max:255', 
        );

        $validator = Validator::make($dataArray, $rules,$messsages);

        if ($validator->fails()) {
            return response()->json(
                $validator->messages(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {            
            if (!$Oid) $data = new Project();
            else $data = Project::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                if ($request->Code == '<<Auto>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                if($data->BusinessPartner) {
                    $bpname = explode(' ', $data->BusinessPartnerObj->Name, 2);
                    if(isset($bpname[1])) $bpname = strtoupper($bpname[0].' '.isset($bpname[1]).' ');
                    else $bpname = strtoupper($bpname[0].' ');
                } else {
                    $bpname = ' ';
                }
                $tourcode = $data->BusinessPartner ? $data->BusinessPartnerObj->Code : null;
                $city = $data->City ? $data->CityObj->Name : null;
                $dateStart = Carbon::parse($data->StartDate)->format('d/m');
                $dateEnd = Carbon::parse($data->EndDate)->format('d/m');
                $data->Name = $tourcode.' '.$bpname.$dateStart.'-'.$dateEnd.' '.$city;
                $data->save();
                if(!$data) throw new \Exception('Data is failed to be saved');
            });

            $role = $this->roleService->list('Project'); //rolepermission
            // $data = (new ProjectResource($data))->type('detail');
            $data->EmployeeName = $data->Employee ? $data->EmployeeObj->Name : null;
            $data->BusinessPartnerName = $data->BusinessPartner ? $data->BusinessPartnerObj->Name : null;
            $data->CityName = $data->City ? $data->CityObj->Name : null;
            $data->Role = $this->roleService->generateActionMaster($role);
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

    public function saveProjectSimple(Request $request, $Oid = null)
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
            if (!$Oid) $data = new Project();
            else $data = Project::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                if ($request->Code == '<<Auto>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
                $disabled = disabledFieldsForEdit();
                foreach ($request as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $request->{$field};
                }
                $data->save();
                if ($data->Code == '<<Auto>>') $data->Code = $this->autoNumberService->generate($data, 'mstproject');
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

    public function destroy(Project $data)
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

    public function sendProject()
    {
        try {       
            $bprole = BusinessPartnerRole::where('Code','TrvOutlet')->first();
            $bp = BusinessPartner::where('BusinessPartnerRole',$bprole->Oid)->whereNotNull('Token')->get();
            $project = Project::whereNull('GCRecord')->where('IsActive',true)->get();

            foreach($bp as $row) {
                $this->coreService->postapi("/admin/api/v1/project/send",$row->Token, ['Data' => $project ]);
            }

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


    public function receiveProject(Request $request)
    {
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
    
        try {
            foreach ($request->Data as $row) {
                
                $data = Project::where('Oid',$row->Oid)->first();
                if (!$data) $data = new Project();

                $row->Company = company()->Oid;
                $disabled = ['BusinessPartner','City','CreatedBy','UpdatedBy', 'Employee'];
                foreach ($row as $field => $key) {
                    if (in_array($field, $disabled)) continue;
                    $data->{$field} = $row->{$field};
                }
                $data->save();
            }
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function export(Request $request)
    {
        try {
            // Contoh pakai eloquent
            // $data = Project::get();

            // Contoh pakai eloquent select field
            $data = Project::select('Code','Name')->get();

            // Contoh pakai query
            // $query = "SELECT p.Oid, p.Code, p.Name
            //     FROM mstproject p";
            // $data = DB::select($query);

            return $this->excelExportService->export($data);

            // return $this->excelExportService->export($data, 'project');  // pakai nama file

            // Contoh pakai eloquent custom field
            // $data = Project::with(['BusinessPartnerObj'])->get();
            // $result = [];
            // foreach ($data as $row){
            //     $result[] = [
            //         'Code' => $row->Code,
            //         'Name' => $row->Name,
            //         'BusinessPartner' => $row->BusinessPartnerObj ? $row->BusinessPartnerObj->Name : null
            //     ];
            // }

            // return $this->excelExportService->export($result);
            
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

}
