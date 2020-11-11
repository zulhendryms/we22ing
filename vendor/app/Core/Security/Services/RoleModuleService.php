<?php

namespace App\Core\Security\Services;

use Illuminate\Support\Facades\DB;
use App\Core\Internal\Entities\RoleModuleCustom;
use App\Core\Internal\Entities\RoleModule;
use App\Core\Master\Entities\Company;
use App\Core\Internal\Entities\Status;
use Illuminate\Support\Facades\Auth;
use stdClass;
use App\Core\Base\Services\HttpService;

class RoleModuleService
{
    private $httpService; 

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService;
        $this->httpService
            ->baseUrl('http://ezbpostest.ezbooking.co:888')
            // ->baseUrl('http://localhost/ezb-laravel-admin-dev/public')
            // ->baseUrl(env('SERVER_URL'))
            ->json();
    }

    public function generateRole($status, $role = null, $action = null) {
        if ($status) $status = Status::findOrFail($status->Status);
        if (!$role) $role = $this->list('SalesInvoice');
        if (!$action) $action = $this->action('SalesInvoice');

        return [
            'IsRead' => isset($role->IsRead) ? $role->IsRead : false,
            'IsAdd' => isset($role->IsAdd) ? $role->IsAdd : false,
            'IsEdit' => $this->isAllowDelete($status, isset($role->IsEdit) ? $role->IsEdit : false),
            'IsDelete' => 0, //$this->isAllowDelete($status, $role->IsDelete),
            'Cancel' => $this->isAllowCancel($status, isset($action->Cancel) ? $action->Cancel : false),
            'Entry' => $this->isAllowEntry($status, isset($action->Entry) ? $action->Entry : false),
            'Post' => $this->isAllowPost($status, isset($action->Posted) ? $action->Posted : false),
            'ViewJournal' => $this->isPosted($status, 1),
            'ViewStock' => $this->isPosted($status, 1),
        ];
    }

    public function generateRoleProductionGlass($status, $role = null, $action = null) {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) $role = $this->list('ProductionOrder');
        if (!$action) $action = $this->action('ProductionOrder');
        return [
            'IsRead' => isset($role->IsRead) ? $role->IsRead : false,
            'IsAdd' => isset($role->IsAdd) ? $role->IsAdd : false,
            'IsEdit' => $this->isAllowDelete($status, isset($role->IsEdit) ? $role->IsEdit : false),
            'IsDelete' => 0, //$this->isAllowDelete($row->StatusObj, $role->IsDelete),
            'Cancel' => $this->isProductionAllowCancel($status, isset($action->Cancel) ? $action->Cancel : false),
            'Entry' => $this->isProductionAllowEntry($status, isset($action->Entry) ? $action->Entry : false),
            'Quoted' => $this->isProductionAllowQuoted($status, isset($action->Quoted) ? $action->Quoted : false),
            'Post' => $this->isProductionAllowPost($status, isset($action->Posted) ? $action->Posted : false),
            'PrintOrder' => $this->isProductionPosted($status, 1),
            'PrintQuotation' => $this->isProductionPosted($status, 1),
        ];
    }
    
    public function generateRoleMaster($role = null, $additional = null) {
        $result = [
            'IsRead' => isset($role->IsRead) ? $role->IsRead : false,
            'IsAdd' => isset($role->IsAdd) ? $role->IsAdd : false,
            'IsEdit' => isset($role->IsEdit) ? $role->IsEdit : false,
            'IsDelete' => isset($role->IsDelete) ? $role->IsDelete : false,
        ];
        if ($additional) $result = array_merge($result, $additional);
        return $result;
    }
    
    public function generateActionMaster($role = null, $additional = null) {
        $return = [];
        if (isset($role->IsEdit)) $return[] =[
                "name" => "Edit",
                "icon" => "PackageIcon",
                "type" => "edit"
        ];
        if (isset($role->IsDelete)) $return[] =[
                "name" => "Delete",
                "icon" => "PackageIcon",
                "type" => "delete"
        ];
        return $return;
    }
    
    public function generateActionMaster2() {
        $return = [];
        $return[] =[
                "name" => "Edit",
                "icon" => "PackageIcon",
                "type" => "edit"
        ];
        $return[] =[
                "name" => "Delete",
                "icon" => "PackageIcon",
                "type" => "delete"
        ];
        return $return;
    }

    public function generateRoleMasterCopy($role = null,$additional = null) {
        $result = [
            'IsRead' =>  $role->IsRead = 1,
            'IsAdd' =>  $role->IsAdd = 1,
            'IsEdit' => $role->IsEdit = 1,
            'IsDelete' => $role->IsDelete = 1,
        ];
        if ($additional) $result = array_merge($result, $additional);
        return $result;
    }
    
    public function generateRolePOS($status, $role = null, $action = null) {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) $role = $this->list('POS');
        if (!$action) $action = $this->action('POS');

        return [
            'IsRead' => $role->IsRead,
            'IsAdd' => $role->IsAdd,
            'IsEdit' => $this->isAllowDelete($status, $role->IsEdit),
            'IsDelete' => 0, //$this->isAllowDelete($status, $role->IsDelete),
            'Cancel' => $this->isAllowCancel($status, $action->Cancel),
            'Complete' => $this->isAllowComplete($status, $action->Complete),
            'Entry' => $this->isAllowEntry($status, $action->Entry),
            'Paid' => $this->isAllowPaid($status, $action->Paid),
            // 'Post' => $this->isAllowPost($status, $action->Posted),
            'ViewJournal' => $this->isPosted($status, 1),
            'ViewStock' => $this->isPosted($status, 1),
            'Print' => $this->isPosted($status, 1),
        ];
    }

    public function list($module)
    {
        $company = Auth::user()->CompanyObj;
        $user = Auth::user();
        $modules = "";
        // if ($user->CompanyObj->BusinessPartner !== $user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
        if ($company->ModulePOS) $modules = $modules.($modules ? "&" : "?")."pos=1";
        if ($company->ModuleTravel) $modules = $modules.($modules ? "&" : "?")."travel=1";
        if ($company->ModuleAccounting) $modules = $modules.($modules ? "&" : "?")."accounting=1";
        if ($company->ModuleProduction) $modules = $modules.($modules ? "&" : "?")."productionglass=1";
        if ($company->ModuleTrucking) $modules = $modules.($modules ? "&" : "?")."trucking=1";
        
        $data = $this->getRoleModule($modules);
        $result = new stdClass();
        foreach($data as $row) {
            // logger($row->Code);
            if ($row->Code == $module) {
                $roleModule = DB::select("SELECT * FROM rolemodules WHERE Role='{$user->Role}' AND Modules='{$module}'");
                // $roleModule = RoleModule::where('Role',$user->Role)
                //     ->where('Modules',$module)
                //     ->first();
                if (!$roleModule) {
                    $result->IsRead = 0;
                    $result->IsAdd = 0;
                    $result->IsEdit = 0;
                    $result->IsDelete = 0;                    
                } else {
                    $roleModule = $roleModule[0];
                    $result->IsRead = $roleModule->IsRead;
                    $result->IsAdd = $roleModule->IsAdd;
                    $result->IsEdit = $roleModule->IsEdit;
                    $result->IsDelete = $roleModule->IsDelete;
                }
            }
        }
        if (!$result) {
            $result->IsRead = 0;
            $result->IsAdd = 0;
            $result->IsEdit = 0;
            $result->IsDelete = 0;
        }
        return $result;
    }

    public function getRoleModule($modules) {
        $company = Auth::user()->CompanyObj;
        $blacklist = $company->ModuleBlacklist ? '&blacklist='.$company->ModuleBlacklist : "";
        if ($company->Code == 'dev_pos') return $this->httpService->get('/portal/api/module/list?allmodules=1'.$blacklist);
        else return $this->httpService->get('/portal/api/module/list'.$modules.$blacklist);
    }
    
    public function roleModule() {
        $company = Auth::user()->CompanyObj;
        $user = Auth::user();
        $modules = "";
        // if ($user->CompanyObj->BusinessPartner !== $user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
        if ($company->ModulePOS) $modules = $modules.($modules ? "&" : "?")."pos=1";
        if ($company->ModuleTravel) $modules = $modules.($modules ? "&" : "?")."travel=1";
        if ($company->ModuleAccounting) $modules = $modules.($modules ? "&" : "?")."accounting=1";
        if ($company->ModuleProduction) $modules = $modules.($modules ? "&" : "?")."productionglass=1";
        if ($company->ModuleTrucking) $modules = $modules.($modules ? "&" : "?")."trucking=1";

        $result = [];
        if ($company->Code == 'dev_pos') $data = $this->httpService->get('/portal/api/module/list?allmodules=1');
        else $data = $this->httpService->get('/portal/api/module/list'.$modules);

        foreach ($data as $row) {
            $roleModule = RoleModule::where('Role',$user->Role)
                ->where('IsRead',1)
                ->where('Modules',$row->Code)
                ->first();
            if ($roleModule) {
                $record = [
                    "Oid" => $roleModule->Oid,
                    "Role" => $user->Role,
                    "Code" => $row->Code,
                    "Name" => $row->Name,
                    "Url" => $row->Url,
                    "Sequence" => $row->Sequence,
                    "Icon" => $row->Icon,
                    "Parent" => $row->Parent,
                    "IsRead" => $roleModule->IsRead,
                    "IsAdd" => $roleModule->IsAdd,
                    "IsEdit" => $roleModule->IsEdit,
                    "IsDelete" => $roleModule->IsDelete
                ];
                $roleCustoms = DB::select("SELECT * FROM rolemodulescustom WHERE Role='{$user->Role}' AND IsEnable=1 AND Modules='{$row->Code}'");
                // $roleCustoms = RoleModuleCustom::where('Role',$user->Role)
                //     ->where('IsEnable',1)
                //     ->where('Modules',$row->Code)
                //     ->get();
                if (($roleCustoms) != 0) {
                    foreach ($roleCustoms as $roleCustom) {
                        $record = array_merge($record, [
                            $roleCustom->Action => 1
                        ]);
                    }
                }
                $result[] = $record;
            }
        }
        return $result;
    }

    
    public function action($module)
    {
        $company = Auth::user()->CompanyObj;
        $user = Auth::user();
        // $cancel = RoleModuleCustom::where('Modules',$module)->where('Role',$user->Role)->where('Action','Cancel')->first();
        // $paid = RoleModuleCustom::where('Modules',$module)->where('Role',$user->Role)->where('Action','Paid')->first();
        // $entry = RoleModuleCustom::where('Modules',$module)->where('Role',$user->Role)->where('Action','Entry')->first();
        // $posted = RoleModuleCustom::where('Modules',$module)->where('Role',$user->Role)->where('Action','Posted')->first();
        // $complete = RoleModuleCustom::where('Modules',$module)->where('Role',$user->Role)->where('Action','Complete')->first();
        // $quoted = RoleModuleCustom::where('Modules',$module)->where('Role',$user->Role)->where('Action','Quoted')->first();
        // $data = new stdClass();
        // $data->Cancel = $cancel ? $cancel->IsEnable : 0;
        // $data->Paid = $paid ? $paid->IsEnable : 0;
        // $data->Entry = $entry ? $entry->IsEnable : 0;
        // $data->Posted = $posted ? $posted->IsEnable : 0;
        // $data->Complete = $complete ? $complete->IsEnable : 0;
        // $data->Quoted = $quoted ? $quoted->IsEnable : 0;
        
        $data = new stdClass();
        $data->Cancel = 0;
        $data->Paid = 0;
        $data->Entry = 0;
        $data->Posted = 0;
        $data->Complete = 0;
        $data->Quoted = 0;
        
        $source = DB::select("SELECT * FROM rolemodulescustom WHERE Role='{$user->Role}' AND Modules='{$module}'");
        foreach($source as $row) $data->{$row->Action} = $row->IsEnable;
        return $data;
    }

    public function isAllowPost($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'entry') return 0;
        return 1;
    } 
    public function isAllowPaid($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status == 'paid') return 0;
        if ($status == 'completed') return 0;
        if ($status == 'cancel') return 0;
        if ($status == 'expired') return 0;
        return 1;
    } 
    public function isAllowEntry($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status == 'entry') return 0;
        if ($status == 'cancel') return 0;
        return 1;
    } 
    public function isAllowCancel($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'entry') return 0;
        return 1;
    } 
    public function isAllowDelete($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'entry') return 0;
        return 1;
    } 
    public function isAllowComplete($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'posted' && $status != 'paid') return 0;
        return 1;
    } 
    public function isPosted($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'posted' && $status != 'paid' && $status != 'completed') return 0;
        return 1;
    } 
    
    public function isProductionAllowPost($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'quoted') return 0;
        return 1;
    }  
    
    public function isProductionAllowQuoted($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'posted' && $status != 'entry') return 0;
        return 1;
    }  
    
    public function isProductionAllowEntry($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'quoted') return 0;
        return 1;
    } 
    
    public function isProductionAllowCancel($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'entry') return 0;
        return 1;
    } 

    public function isProductionPosted($status, $role)
    {
        if ($status instanceof Status) $status = $status->Code;
        if (!$role) return 0;
        if ($status == null) return 0;
        if ($status != 'posted' && $status != 'quoted') return 0;
        return 1;
    }     
    
    public function disablefield()
    {
        $query = "SELECT cd.Modules, cd.Field
            FROM companydisable cd 
            WHERE cd.IsDisable = 1
            GROUP BY cd.Modules, cd.Field";
        $data = DB::select($query);
        return $data;
    }
}