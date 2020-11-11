<?php

namespace App\AdminApi\Security\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Internal\Entities\Role;
use App\Core\Internal\Entities\RoleModule;
use App\Core\Internal\Entities\RoleModuleCustom;
use App\Core\Internal\Entities\ModulesParent;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Services\RoleModuleService;

class RoleModuleController extends Controller
{
    protected $roleService;
    public function __construct(RoleModuleService $roleService)
    {
        $this->roleService = $roleService;
    }

    private function menuMobile() {
        return [
            [
                "url" => "/dashboard",
                "name" => "Dashboard",
                "icon" => "HomeIcon",
                "i18n" => "Dashboard"
            ],[
                "url" => "/purchaserequestapproval",
                "name" => "PurchaseRequestApproval",
                "icon" => "HomeIcon",
                "i18n" => "PurchaseRequestApproval"
            ]
        ];
    }

    public function index(Request $request)
    {
        $company = Auth::user()->CompanyObj;
        $user = Auth::user();
        $modules = "";
        // if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
        if ($company->ModulePOS) $modules = $modules.($modules ? "&" : "?")."pos=1";
        if ($company->ModuleTravel) $modules = $modules.($modules ? "&" : "?")."travel=1";
        if ($company->ModuleAccounting) $modules = $modules.($modules ? "&" : "?")."accounting=1";
        if ($company->ModuleProduction) $modules = $modules.($modules ? "&" : "?")."productionglass=1";
        if ($company->ModuleTrucking) $modules = $modules.($modules ? "&" : "?")."trucking=1";
        $type = $request->input('type') ?: 'combo';

        if ($request->input('type') == 'menu') return $this->generateMenuSub($user->Role);
        if ($request->input('type') == 'menumobile') return $this->generateMenuSub($user->Role);
        if ($request->input('type') == 'search') return $this->genSearch($user->Role);
        if ($request->input('type') == 'detail') {
            return $this->roleModule();
        }
        if ($request->has('Role') && $request->input('type') == 'list') {
            $role = Role::findOrFail($request->Role);
            $result = [];
            $data = $this->roleService->getRoleModule($modules);
            foreach($data as $row) {
                $roleModule = RoleModule::where('Role',$request->Role)
                    ->where('Modules',$row->Code)
                    ->first();
                if (!$roleModule) {
                    $roleModule = RoleModule::create([
                        'Role' => $role->Oid,
                        'Modules' => $row->Code,
                        'IsRead' => 0,
                        'IsAdd' => 0,
                        'IsEdit' => 0,
                        'IsDelete' => 0,
                    ]);
                }
                $result[] = [
                    "Oid" => $roleModule->Oid,
                    "Role" => $roleModule->Role,
                    "Url" => $row->Url,
                    "Sequence" => $row->Sequence,
                    "Icon" => $row->Icon,
                    "Modules" => $row->Code,
                    "Name" => ($row->Parent ? $row->Parent.' - ' : '').$row->Name,
                    "IsRead" => $roleModule->IsRead,
                    "IsAdd" => $roleModule->IsAdd,
                    "IsEdit" => $roleModule->IsEdit,
                    "IsDelete" => $roleModule->IsDelete,
                    "SelectAll" => $roleModule->IsRead && $roleModule->IsAdd && $roleModule->IsEdit && $roleModule->IsDelete ? 1 : 0
                ];
            }
            return $result;
        }
        if ($request->has('Role') && $request->input('type') == 'custom') {
            $role = Role::findOrFail($request->Role);
            $result = [];
            $data = $this->roleService->getRoleModule($modules);
            foreach($data as $row) {
                $roleCustoms = RoleModuleCustom::where('Role',$request->Role)
                    ->where('Modules',$row->Code)
                    ->get();
                if ($roleCustoms->count() != 0) {
                    foreach ($roleCustoms as $roleCustom) {
                        $result[] = [
                            "Oid" => $roleCustom->Oid,
                            "Role" => $roleCustom->Role,
                            "Modules" => $row->Code,
                            "Name" => $row->Name,
                            "Action" => $roleCustom->Action,
                            "IsEnable" => $roleCustom->IsEnable,
                        ];
                    }
                }
            }
            return $result;
        }

        $data = RoleModule::get();
        return $data;
    }

    public function roleModule() {        
        return $this->roleService->roleModule();
    }
    
    public function show(RoleModule $data)
    {
        return $data;
    }
    
    public function disablefield()
    {
        return $this->roleService->disablefield();
    }

    public function update(Request $request)
    {
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        $role = Role::findOrFail($input->Role->Oid);
        $roleModules = $input->RoleModule;
        DB::transaction(function() use ($role, $roleModules, $input) {
            if (is_array($roleModules) || is_object($roleModules)) {
                foreach ($roleModules as $roleModule) {
                    $data = RoleModule::where('Role',$role->Oid)->where('Modules',$roleModule->Modules)->first();
                    $data->IsRead = $roleModule->IsRead;
                    $data->IsAdd = $roleModule->IsAdd;
                    $data->IsEdit = $roleModule->IsEdit;
                    $data->IsDelete = $roleModule->IsDelete;
                    $data->save();
                }
            }
        });
        $this->generateMenu($role);
        return $input->RoleModule;
    }

    public function generateMenu($role) {   
        try {
            $jsonModules = $this->generateMenuSub($role);
            $role->Navigation = (string) json_encode($jsonModules);
            
            $jsonModules = $this->genSearch($role);
            $role->NavSearch = (string) json_encode($jsonModules);

            $role->save();
            return $role;

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }

    public function generateMenuSub($role) {
        $user = Auth::user();
        $company = $user->CompanyObj;
        $modules = "";

        //checking module 
        if ($company->ModulePOS) $modules = $modules.($modules ? "&" : "?")."pos=1";
        if ($company->ModuleTravel) $modules = $modules.($modules ? "&" : "?")."travel=1";
        if ($company->ModuleAccounting) $modules = $modules.($modules ? "&" : "?")."accounting=1";
        if ($company->ModuleProduction) $modules = $modules.($modules ? "&" : "?")."productionglass=1";
        if ($company->ModuleTrucking) $modules = $modules.($modules ? "&" : "?")."trucking=1";
        
        //global role
        if ($company->ModulePOS && $company->POSUsingGlobalRole) {
            $modules= "";
            if ($user->Role == '08095806-7a05-4d38-8fdd-86fc1e1ac410' || $user->RoleObj->Name == '_ Administrator **') $module = "posglobal=administrator";
            elseif ($user->Role == '27543532-8649-4af5-98fe-a9944d2df959' || $user->RoleObj->Name == '_ Supervisor' || $user->RoleObj->Name == '_ Management') $module = "posglobal=supervisor";
            else $module = "posglobal=cashier";
        }
        
        $modules = $this->roleService->getRoleModule($modules);
        $roleModule = RoleModule::where('Role', $role)->get();

        //DASHBOARD        
        $jsonModules = ""; $jsonGroup="";$tmp='';
        $tmp = '{ '.
            '"url":"/dashboard",'.
            '"name":"Dashboard",'.
            '"icon":"HomeIcon",'.
            '"i18n":"Dashboard"'.
            '}';
        $jsonModules = $jsonModules.($jsonModules ? ', ' : ' ').$tmp;

        $jsonGroup="";
        $titleGroup = '{ "header":"Master", "i18n":"Master", "name":"Master", "icon":"BriefcaseIcon", "items":[';

            $title = '{ "name":"SetupProduct", "icon":"SettingsIcon", "i18n":"SetupProduct", "submenu": [';
            $tmp = $this->gen($modules, $roleModule, "SetupProduct");
            if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';

            $title = '{ "name":"SetupBusinessPartner", "icon":"SettingsIcon", "i18n":"SetupBusinessPartner", "submenu": [';
            $tmp = $this->gen($modules, $roleModule, "SetupBusinessPartner");
            if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';

        $tmp = $this->gen($modules, $roleModule, "Master");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ',' : '').$tmp;

        if ($jsonGroup) $jsonModules = $jsonModules.($jsonModules ? ', ' : '').$titleGroup.$jsonGroup.']}';
        $jsonGroup="";


        $title = '{ "header":"Production", "i18n":"Production", "name":"Production", "icon":"BoxIcon", "items":[';
        $tmp = $this->gen($modules, $roleModule, "Production");
        if ($tmp) $jsonModules = $jsonModules.($jsonModules ? ', ' : ' ').$title.$tmp.']}';

        $title = '{ "header":"Accounting", "i18n":"Accounting", "name":"Accounting", "icon":"BookOpenIcon", "items":[';
        $tmp = $this->gen($modules, $roleModule, "Accounting");
        if ($tmp) $jsonModules = $jsonModules.($jsonModules ? ', ' : ' ').$title.$tmp.']}';

        $title = '{ "header":"Stock", "i18n":"Stock", "name":"Stock", "icon":"BoxIcon", "items":[';
        $tmp = $this->gen($modules, $roleModule, "Stock");
        if ($tmp) $jsonModules = $jsonModules.($jsonModules ? ', ' : '').$title.$tmp.']}';

        $title = '{ "header":"Sales", "i18n":"Sales", "name":"Sales", "icon":"ShoppingCartIcon", "items":[';
        $tmp = $this->gen($modules, $roleModule, "Sales");
        if ($tmp) $jsonModules = $jsonModules.($jsonModules ? ', ' : '').$title.$tmp.']}';
        
        $jsonGroup="";
        $titleGroup = '{ "header":"Setup", "i18n":"Setup", "name":"Setup", "icon":"SettingsIcon", "items":[';

        $title = '{ "name":"SetupGeneral", "icon":"SettingsIcon", "i18n":"Setup", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "Setup");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';
        
        $title = '{ "name":"SetupBusinessPartner", "icon":"SettingsIcon", "i18n":"SetupBusinessPartner", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "SetupBusinessPartner");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';
        
        $title = '{ "name":"SetupItem", "icon":"SettingsIcon", "i18n":"SetupItem", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "SetupItem");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';
        
        $title = '{ "name":"SetupControl", "icon":"SettingsIcon", "i18n":"SetupControl", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "SetupControl");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';
        
        $title = '{ "name":"SetupProduction", "icon":"SettingsIcon", "i18n":"SetupProduction", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "SetupProduction");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';
        
        $title = '{ "name":"SetupAccounting", "icon":"SettingsIcon", "i18n":"SetupAccounting", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "SetupAccounting");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';
        
        $title = '{ "name":"SetupTravel", "icon":"SettingsIcon", "i18n":"SetupTravel", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "SetupTravel");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';

        $title = '{ "name":"SetupFerry", "icon":"SettingsIcon", "i18n":"SetupFerry", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "SetupFerry");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';
        
        if ($jsonGroup) $jsonModules = $jsonModules.($jsonModules ? ',' : '').$titleGroup.$jsonGroup.']}';
        $jsonGroup="";

        $titleGroup = '{ "header":"Report", "i18n":"Report", "name":"Report", "icon":"PrinterIcon", "items":[';

        $title = '{ "name":"ReportProduction", "icon":"PrinterIcon", "i18n":"ReportProduction", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "ReportProduction");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';

        $title = '{ "name":"ReportSales", "icon":"PrinterIcon", "i18n":"ReportSales", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "ReportSales");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';

        $title = '{ "name":"ReportAccount", "icon":"PrinterIcon", "i18n":"ReportAccount", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "ReportAccount");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';

        $title = '{ "name":"ReportStock", "icon":"PrinterIcon", "i18n":"ReportStock", "submenu": [';
        $tmp = $this->gen($modules, $roleModule, "ReportStock");
        if ($tmp) $jsonGroup = $jsonGroup.($jsonGroup ? ', ' : '').$title.$tmp.']}';
        
        if ($jsonGroup) $jsonModules = $jsonModules.($jsonModules ? ', ' : '').$titleGroup.$jsonGroup.']}';
        $jsonGroup="";

        $jsonModules = "[".$jsonModules."]";
        return json_decode($jsonModules);
    }

    public function generateAllMenu() {
        try {
            $query = "SELECT Role FROM user WHERE Role IS NOT NULL GROUP BY Role";
            $data = DB::select($query);
            foreach($data as $row) {
                $role = Role::where('Oid',$row->Role)->first();
                if ($role) $this->generateMenu($role);                
            }            
            return response()->json(
                $data, Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        } 
    }

    public function updateCustom(Request $request)
    {
        $company = Auth::user()->CompanyObj;
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent())))); //WILLIAM ZEF
        $role = Role::findOrFail($input->Role->Oid);
        $modules = "";
        if ($company->ModulePOS) $modules = $modules.($modules ? "," : "")."'pos'";
        if ($company->ModuleTravel) $modules = $modules.($modules ? "," : "")."'travel'";
        if ($company->ModuleAccounting) $modules = $modules.($modules ? "," : "")."'accounting'";
        if ($company->ModuleTrucking) $modules = $modules.($modules ? "&" : "?")."trucking=1";
        $role = Role::where('Oid',$role->Oid)->first();        

        DB::transaction(function() use ($role, $input) {
            if (is_array($input->RoleModuleCustom) || is_object($input->RoleModuleCustom)) {
                foreach ($input->RoleModuleCustom as $roleModuleCustom) {
                    $data = RoleModuleCustom::where('Role',$role->Oid)->where('Modules',$roleModuleCustom->Modules)->first();
                    $data->IsEnable = $roleModuleCustom->IsEnable;
                    $data->save();
                }
            } 
            return $data;
        });
    }
    
    private function gen($modules, $roleModules, $key) {
        $result='';
        $user = Auth::user();
        foreach($modules as $module) {
            if ($module->Parent != $key) continue;
            foreach($roleModules as $roleModule) {
                if ($user->CompanyObj->Code == 'dev_pos' && $user->UserName == 'administrator@ezbooking.co') {                    
                    $tmp = '{ '.
                        '"url":"'.$module->Url.'", '.
                        '"name":"'.$module->Name.'", '.
                        '"slug":"'.strtolower($module->Code).'", '.
                        '"icon":"'.$module->Icon.'", '.
                        '"i18n":"'.$module->Name.'" '.
                        '}';
                    $result = $result.($result ? ', ' : ' ').$tmp;
                } else if (strtoupper($module->Code) == strtoupper($roleModule['Modules']) && $roleModule['IsRead'] && $module->Code != 'POSSessionAmount') {
                    // if ($module->Parent == 'Master') logger($module->Name);
                    $tmp = '{ '.
                        '"url":"'.$module->Url.'", '.
                        '"name":"'.$module->Name.'", '.
                        '"slug":"'.strtolower($module->Code).'", '.
                        '"icon":"'.$module->Icon.'", '.
                        '"i18n":"'.$module->Name.'" '.
                        '}';
                    $result = $result.($result ? ', ' : ' ').$tmp;
                }
            }    
        }
        return $result;
    }
    
    public function genSearch($role) {
        
        $company = Auth::user()->CompanyObj;
        $modules = "";
        if ($company->ModulePOS) $modules = $modules.($modules ? "&" : "?")."pos=1";
        if ($company->ModuleTravel) $modules = $modules.($modules ? "&" : "?")."travel=1";
        if ($company->ModuleAccounting) $modules = $modules.($modules ? "&" : "?")."accounting=1";
        if ($company->ModuleProduction) $modules = $modules.($modules ? "&" : "?")."productionglass=1";
        if ($company->ModuleTrucking) $modules = $modules.($modules ? "&" : "?")."trucking=1";
        $modules = $this->roleService->getRoleModule($modules);
        $roleModules = RoleModule::where('Role', $role)->get();

        $result=''; $i = 0;
        foreach($modules as $module) {
            foreach($roleModules as $roleModule) {
                if ($module->Code == $roleModule['Modules'] && $roleModule['IsRead'] && $module->Code != 'POSSessionAmount') {
                    $tmp = '{ '.
                        '"index": '.$i.', '.
                        '"url":"'.$module->Url.'", '.
                        '"label":"'.$module->Name.' ('.$module->Parent.')", '.
                        '"labelIcon":"'.$module->Icon.'", '.
                        '"highlightAction": false '.
                        '}';
                        $i = $i + 1;
                    $result = $result.($result ? ', ' : ' ').$tmp;
                }
            }    
        }
        $result = "[".$result."]";
        return json_decode($result);
    }
}
