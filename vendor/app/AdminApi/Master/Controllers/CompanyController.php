<?php
namespace App\AdminApi\Master\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\Company;
use App\Core\Security\Entities\User;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

use App\Core\Master\Entities\ItemPriceMethod;

class CompanyController extends Controller
{
    protected $roleService;
    private $module;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->module = 'company';
        $this->roleService = $roleService;
        $this->crudController = new CRUDDevelopmentController();
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'Oid'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Code'];
        $fields[] = ['w' => 400, 'r' => 0, 't' => 'text', 'n' => 'Name'];
        return $fields;
    }

    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        $fields[0]['cellRenderer'] = 'actionCell';
        $fields[0]['topButton'] = [];
        return $fields;
    }

    public function presearch(Request $request)
    {
        try {
            return $this->crudController->presearch($this->module);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function list(Request $request)
    {
        try {
            $user = Auth::user();
            $data = DB::table($this->module.' as data');
            $data = $data->addSelect(['Oid','Code','Name']);
            
            if ($user->CompanyAccess) {
                $data = $data->leftJoin('company AS CompanySecurity', 'CompanySecurity.Oid', '=', 'data.Company');
                $tmp = json_decode($user->CompanyAccess);
                $data = $data->whereIn('CompanySecurity.Code', $tmp);
            } else {
                $data = $data->where('Oid', $user->Company);
            }
            $data = $data->get();            
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function index(Request $request)
    {
        $company = Auth::user()->CompanyObj;

        $query = "SELECT Oid, Code AS Name FROM company WHERE GCRecord IS NULL";
        
        
        if ($request->has('all')) $query = $query." AND GCRecord IS NULL";
        else {
            $found = false;
            $tmp = json_decode($company->ModuleGlobal);        
            if ($found == '' && $tmp) foreach($tmp as $row) if ($row == 'company') $found = 'Global';        
            $tmp = json_decode($company->ModuleGroup);
            if ($found == '' && $tmp) foreach($tmp as $row) if ($row == 'company') $found = 'Group';
            if ($found == 'Group') {
                $query = $query." AND (CompanySource = '".$company->CompanySource."' OR CompanySource = '".$company->Oid."')";
            } elseif ($found == '') {
                $query." AND Oid = '".$company->Oid."'";
            } else {
                // logger("HELPER LIST NOFILTER ".'company'."=".$company->Oid);
            }
        }        
        if ($request->has('excludeself')) $query = $query." AND Oid != '".$company->Oid."'";
        if ($request->input('type') == 'combo') return DB::select($query);
        else return $this->list($request->input('type'));
    }
    
    public function masterlist(Request $request)
    {       
        $user = Auth::user();
        $query = "SELECT Oid, Code AS Name FROM company WHERE GCRecord IS NULL";
        if ($request->has('excludeself')) $query = $query." AND Oid != '".$user->Company."'";        
        return DB::select($query);
    }

    private function showSub($Oid)
    {
        try {
            $data = $this->crudController->detail($this->module, $Oid);
            return $data;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function show(Company $data)
    {
        try {
            return $this->showSub($data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function save(Request $request, $Oid = null)
    {
        try {
            $data;
            DB::transaction(function () use ($request, &$data, $Oid) {
                $data = $this->crudController->saving($this->module, $request, $Oid, true);
                
                if($data->PriceMethodApitude == null){
                    $itemPriceMethod = new ItemPriceMethod();
                    $itemPriceMethod->save();
                    $data->PriceMethodApitude = $itemPriceMethod->Oid;
                    $data->save();
                }
                if($data->PriceMethodGlobaltix == null){
                    $itemPriceMethod = new ItemPriceMethod();
                    $itemPriceMethod->save();
                    $data->PriceMethodGlobaltix = $itemPriceMethod->Oid;
                    $data->save();
                }
            });
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(Company $data)
    {
        try {
            return $this->crudController->delete($this->module, $data->Oid);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    

    public function getPriceMethodApitude()
    {
        try {          
            $dataCompany = Auth::user()->CompanyObj;
            $Oid= $dataCompany->Oid;

            $company = Company::findOrFail($Oid); 
            $data = ItemPriceMethod::with(['SalesAddMethodObj','SalesAdd1MethodObj','SalesAdd2MethodObj','SalesAdd3MethodObj','SalesAdd4MethodObj','SalesAdd5MethodObj'])->where('Oid',$company->PriceMethodApitude)->first();
    
            return response()->json(
                $data,
                Response::HTTP_OK
            );

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function savePriceMethodApitude(Request $request)
    {  
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

        try {            
            $CompanyOid = Auth::user()->CompanyObj->Oid;

            $Oid = Company::findOrFail($CompanyOid)->PriceMethodApitude; 
            if (!$Oid) throw new \Exception('Data is failed to be saved');
            $data = ItemPriceMethod::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $data->Code = $request->Code;
                $data->Name = $request->Name;
                $data->IsActive = 1;
                $data->SalesAddMethod = $request->SalesAddMethod;
                $data->SalesAddAmount1 = $request->SalesAddAmount1;
                $data->SalesAddAmount2 = $request->SalesAddAmount2;
                $data->SalesAdd1Method = $request->SalesAdd1Method;
                $data->SalesAdd1Amount1 = $request->SalesAdd1Amount1;
                $data->SalesAdd1Amount2 = $request->SalesAdd1Amount2;
                $data->SalesAdd2Method = $request->SalesAdd2Method;
                $data->SalesAdd2Amount1 = $request->SalesAdd2Amount1;
                $data->SalesAdd2Amount2 = $request->SalesAdd2Amount2;
                $data->SalesAdd3Method = $request->SalesAdd3Method;
                $data->SalesAdd3Amount1 = $request->SalesAdd3Amount1;
                $data->SalesAdd3Amount2 = $request->SalesAdd3Amount2;
                $data->SalesAdd4Method = $request->SalesAdd4Method;
                $data->SalesAdd4Amount1 = $request->SalesAdd4Amount1;
                $data->SalesAdd4Amount2 = $request->SalesAdd4Amount2;
                $data->SalesAdd5Method = $request->SalesAdd5Method;
                $data->SalesAdd5Amount1 = $request->SalesAdd5Amount1;
                $data->SalesAdd5Amount2 = $request->SalesAdd5Amount2;
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

    public function getPriceMethodGlobalTix()
    {
        try {          
            $dataCompany = Auth::user()->CompanyObj;
            $Oid= $dataCompany->Oid;

            $company = Company::findOrFail($Oid);   
            $data = ItemPriceMethod::with(['SalesAddMethodObj','SalesAdd1MethodObj','SalesAdd2MethodObj','SalesAdd3MethodObj','SalesAdd4MethodObj','SalesAdd5MethodObj'])->where('Oid',$company->PriceMethodGlobaltix)->first();
    
            return response()->json(
                $data,
                Response::HTTP_OK
            );

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function savePriceMethodGlobalTix(Request $request)
    {  
        $request = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

        try {            
            $CompanyOid = Auth::user()->CompanyObj->Oid;

            $Oid = Company::findOrFail($CompanyOid)->PriceMethodGlobaltix; 
            if (!$Oid) throw new \Exception('Data is failed to be saved');
            $data = ItemPriceMethod::findOrFail($Oid);
            DB::transaction(function () use ($request, &$data) {
                $data->Code = $request->Code;
                $data->Name = $request->Name;
                $data->IsActive = 1;
                $data->SalesAddMethod = $request->SalesAddMethod;
                $data->SalesAddAmount1 = $request->SalesAddAmount1;
                $data->SalesAddAmount2 = $request->SalesAddAmount2;
                $data->SalesAdd1Method = $request->SalesAdd1Method;
                $data->SalesAdd1Amount1 = $request->SalesAdd1Amount1;
                $data->SalesAdd1Amount2 = $request->SalesAdd1Amount2;
                $data->SalesAdd2Method = $request->SalesAdd2Method;
                $data->SalesAdd2Amount1 = $request->SalesAdd2Amount1;
                $data->SalesAdd2Amount2 = $request->SalesAdd2Amount2;
                $data->SalesAdd3Method = $request->SalesAdd3Method;
                $data->SalesAdd3Amount1 = $request->SalesAdd3Amount1;
                $data->SalesAdd3Amount2 = $request->SalesAdd3Amount2;
                $data->SalesAdd4Method = $request->SalesAdd4Method;
                $data->SalesAdd4Amount1 = $request->SalesAdd4Amount1;
                $data->SalesAdd4Amount2 = $request->SalesAdd4Amount2;
                $data->SalesAdd5Method = $request->SalesAdd5Method;
                $data->SalesAdd5Amount1 = $request->SalesAdd5Amount1;
                $data->SalesAdd5Amount2 = $request->SalesAdd5Amount2;
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
}
