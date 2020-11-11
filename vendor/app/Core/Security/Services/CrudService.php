<?php

namespace App\Core\Security\Services;

use Illuminate\Support\Facades\DB;
use App\Core\Internal\Entities\RoleModule;
use App\Core\Internal\Entities\Status;
use Illuminate\Support\Facades\Auth;
use stdClass;
use App\Core\Base\Services\HttpService;

class CrudService
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

    public function generateRoleMasterCopy($role = null) {
        return [
            'IsRead' => isset($role->IsRead) ? $role->IsRead : 1,
            'IsAdd' => isset($role->IsAdd) ? $role->IsAdd : 1,
            'IsEdit' => isset($role->IsEdit) ? $role->IsEdit : 1,
            'IsDelete' => isset($role->IsDelete) ? $role->IsDelete : 1,
        ];
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
        $data = $this->httpService->get('/portal/api/module/list'.$modules);
        $result = new stdClass();
        foreach($data as $row) {
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
        return $this->httpService->get('/portal/api/module/list'.$modules);
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
        $data = $this->httpService->get('/portal/api/module/list'.$modules);
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








    
    
    public function dataJSon($data, $fields = [], $request, $defaultSort = 'data.Name')
    {
        $user = Auth::user();
        $selectFields = [];
        $selectFields[] = 'data.Oid';
        foreach($fields as $row) {        
            if ($row->field == 'Action') continue;             
            if (!isset($row->type)) $row->type = "text";
            if ($row->type == 'combobox' || $row->type == 'autocomplete') {
                $field = isset($row->fieldjoin) ? $row->fieldjoin : 'data.'.$row->fieldToSave;
                $selectFields[] = $field.' AS '.$row->fieldToSave;
                $selectFields[] = $row->fieldToSave.'.Name AS '.$row->field;
            } else {
                $field = isset($row->fieldToSave) ? $row->fieldToSave : 'data.'.$row->fieldToSave;
                if ($row->field == 'IsActive') $selectFields[] = DB::raw("CASE WHEN data.".$field."=1 THEN 'Y' ELSE 'N' END AS ".$row->field);
                elseif ($row->field == 'Date') $selectFields[] = DB::raw("DATE_FORMAT(data.".$field.", '%Y-%m-%d') AS ".$row->field);
                else $selectFields[] = 'data.'.$field.' AS '.$row->field;  
            }
        }
        if ($defaultSort == 'Name') $defaultSort = 'data.Name';
        elseif (strpos($defaultSort,'Name') < 1) $defaultSort = 'data.'.$defaultSort;
        if ($request->query->has('sort')) $sort = $this->returnDataField($request->query('sort'));

        $page = $request->query->has('page') ? $request->query('page') : 1;
        $size = $request->query->has('size') ? $request->query('size') : 20;
        $sort = $request->query->has('sort') ? $sort : $defaultSort;
        $sorttype = $request->query->has('sorttype') ? $request->query('sorttype') : ($sort == 'Date' ? 'desc' : 'asc');
        $stringSearch=null;
        foreach($fields as $row) {
            if ($row->field == 'Action') continue;
            if (isset($row->hide)) if ($row->hide) continue;
            $field = !isset($row->fieldToSearch) ? 'data.'.$row->field : $field = $row->fieldToSearch;
            if ($request->has($field)) $data = $data->where($field,'LIKE',$request->query($field)[0].'%');                
            if ($request->has('search')) $stringSearch = ($stringSearch ? $stringSearch." OR " : "").$field." LIKE '".$request->query('search')."%'";
        }
        $data = $data->where('data.Company',$user->Company);
        if ($stringSearch) $data = $data->whereRaw("(".$stringSearch.")");
        foreach($fields as $row) {
            if ($sort == $row->field) {
                $field = isset($row->field) ? $row->field : 'data.'.$row->field;
                $sort = $field;
                break;
            }
        }
        return $data->select($selectFields)->whereRaw('data.GCRecord IS NULL')->orderBy($sort, $sorttype)->limit(500)->paginate($size);
    }

    public function dataJSonNoCompany($data, $fields = [], $request, $defaultSort = 'data.Name')
    {
        $user = Auth::user();
        $selectFields = [];
        $selectFields[] = 'data.Oid';
        foreach($fields as $row) {        
            if ($row->field == 'Action') continue;             
            if (!isset($row->type)) $row->type = "text";
            if ($row->type == 'combobox' || $row->type == 'autocomplete') {
                $field = isset($row->fieldjoin) ? $row->fieldjoin : 'data.'.$row->fieldToSave;
                $selectFields[] = $field.' AS '.$row->fieldToSave;
                $selectFields[] = $row->fieldToSave.'.Name AS '.$row->field;
            } else {
                $field = isset($row->fieldToSave) ? $row->fieldToSave : 'data.'.$row->fieldToSave;
                if ($row->field == 'IsActive') $selectFields[] = DB::raw("CASE WHEN data.".$field."=1 THEN 'Y' ELSE 'N' END AS ".$row->field);
                elseif ($row->field == 'Date') $selectFields[] = DB::raw("DATE_FORMAT(data.".$field.", '%Y-%m-%d') AS ".$row->field);
                else $selectFields[] = 'data.'.$field.' AS '.$row->field;  
            }
        }
        if ($defaultSort == 'Name') $defaultSort = 'data.Name';
        elseif (strpos($defaultSort,'Name') < 1) $defaultSort = 'data.'.$defaultSort;
        if ($request->query->has('sort')) $sort = $this->returnDataField($request->query('sort'));

        $page = $request->query->has('page') ? $request->query('page') : 1;
        $size = $request->query->has('size') ? $request->query('size') : 20;
        $sort = $request->query->has('sort') ? $sort : $defaultSort;
        $sorttype = $request->query->has('sorttype') ? $request->query('sorttype') : ($sort == 'Date' ? 'desc' : 'asc');
        $stringSearch=null;
        foreach($fields as $row) {
            if ($row->field == 'Action') continue;
            if (isset($row->hide)) if ($row->hide) continue;
            $field = !isset($row->fieldToSearch) ? 'data.'.$row->field : $field = $row->fieldToSearch;
            if ($request->has($field)) $data = $data->where($field,'LIKE',$request->query($field)[0].'%');                
            if ($request->has('search')) $stringSearch = ($stringSearch ? $stringSearch." OR " : "").$field." LIKE '".$request->query('search')."%'";
        }
        if ($stringSearch) $data = $data->whereRaw("(".$stringSearch.")");
        foreach($fields as $row) {
            if ($sort == $row->field) {
                $field = isset($row->field) ? $row->field : 'data.'.$row->field;
                $sort = $field;
                break;
            }
        }
        return $data->select($selectFields)->whereRaw('data.GCRecord IS NULL')->orderBy($sort, $sorttype)->limit(500)->paginate($size);
    }

    private function returnDataField($sort)
    {
        if ($sort == 'Name') $sort = 'data.Name';
        elseif ($sort == 'Code') $sort = 'data.Code';
        elseif (strpos($sort,'Name') < 1) $sort = 'data.'.$sort;
        return $sort;
    }

    public function data($data, $fields = [], $request, $defaultSort = 'Name')
    {
        $user = Auth::user();
        $selectFields = [];
        foreach($fields as $row) {
            if (!isset($row['t'])) $row['t'] = "text";
            if ($row['t'] == 'combo' || $row['t'] == 'autocomplete') {
                $field = isset($row['j']) ? $row['j'] : 'data.'.$row['n'];
                $selectFields[] = $field.' AS '.$row['n'];
                $selectFields[] = $row['f'].' AS '.$row['n'].'Name';
            } else {
                $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
                if ($row['n'] == 'A') $selectFields[] = DB::raw("CASE WHEN ".$field."=1 THEN 'Y' ELSE 'N' END AS ".$row['n']);
                elseif ($row['n'] == 'Date') $selectFields[] = DB::raw("DATE_FORMAT(".$field.", '%Y-%m-%d') AS ".$row['n']);
                else $selectFields[] = $field.' AS '.$row['n'];  

            }
        }

        $page = $request->query->has('page') ? $request->query('page') : 1;
        $size = $request->query->has('size') ? $request->query('size') : 20;
        $sort = $request->query->has('sort') ? $request->query('sort') : $defaultSort;
        $sorttype = $request->query->has('sorttype') ? $request->query('sorttype') : ($sort == 'Date' ? 'desc' : 'asc');
        $stringSearch=null;
        foreach($fields as $row) {
            $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
            if ($request->has($row['n'])) $data = $data->where($field,'LIKE','%'.$request->query($row['n'])[0].'%');
            if ($request->has('search')) {
                if (strpos($field, 'Code') > 0 || strpos($field, 'Name') > 0) $stringSearch = ($stringSearch ? $stringSearch." OR " : "").$field." LIKE '%".$request->query('search')."%'";
            }
        }
        $data = $data->where('data.Company',$user->Company);
        if ($stringSearch) $data = $data->whereRaw("(".$stringSearch.")");
        // if ($request->has('search')) {
        //     $data = $data->where(function($query) use ($fields,$request) {
        //         foreach($fields as $row) {
        //             $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
        //             $query->orWhere($field, $request->query('search'));
        //         }
        //     }
        // }
        foreach($fields as $row) {
            if ($sort == $row['n']) {
                $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
                $sort = $field;
                break;
            }
        }
        return $data->select($selectFields)->whereRaw('data.GCRecord IS NULL')->orderBy($sort, $sorttype)->limit(500)->paginate($size);
    }

    public function dataAllCompany($data, $fields = [], $request, $defaultSort = 'Name')
    {
        $selectFields = [];
        foreach($fields as $row) {
            if (!isset($row['t'])) $row['t'] = "text";
            if ($row['t'] == 'combo' || $row['t'] == 'autocomplete') {
                $field = isset($row['j']) ? $row['j'] : 'data.'.$row['n'];
                $selectFields[] = $field.' AS '.$row['n'];
                $selectFields[] = $row['f'].' AS '.$row['n'].'Name';
            } else {
                $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
                if ($row['n'] == 'A') $selectFields[] = DB::raw("CASE WHEN ".$field."=1 THEN 'Y' ELSE 'N' END AS ".$row['n']);
                elseif ($row['n'] == 'Date') $selectFields[] = DB::raw("DATE_FORMAT(".$field.", '%Y-%m-%d') AS ".$row['n']);
                else $selectFields[] = $field.' AS '.$row['n'];  

            }
        }
        $page = $request->query->has('page') ? $request->query('page') : 1;
        $size = $request->query->has('size') ? $request->query('size') : 20;
        $sort = $request->query->has('sort') ? $request->query('sort') : $defaultSort;
        $sorttype = $request->query->has('sorttype') ? $request->query('sorttype') : ($sort == 'Date' ? 'desc' : 'asc');
        $stringSearch=null;
        foreach($fields as $row) {
            $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
            // if ($request->has($row['n'])) $data = $data->whereIn($field,$request->query($row['n']));            
            if ($request->has($row['n'])) $data = $data->where($field,'LIKE',$request->query($row['n'])[0].'%');
                // dd($field.' '.$request->query($row['n'])[0]);
            if ($request->has('search')) $stringSearch = ($stringSearch ? $stringSearch." OR " : "").$field." LIKE '".$request->query('search')."%'";
        }
        if ($stringSearch) $data = $data->whereRaw("(".$stringSearch.")");
        // if ($request->has('search')) {
        //     $data = $data->where(function($query) use ($fields,$request) {
        //         foreach($fields as $row) {
        //             $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
        //             $query->orWhere($field, $request->query('search'));
        //         }
        //     }
        // }
        foreach($fields as $row) {
            if ($sort == $row['n']) {
                $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
                $sort = $field;
                break;
            }
        }
        return $data->select($selectFields)->whereRaw('data.GCRecord IS NULL')->orderBy($sort, $sorttype)->limit(500)->paginate($size);        
    }

    public function data2($data, $fields = [], $request, $defaultSort = 'Name')
    {
        $selectFields = [];
        foreach($fields as $row) {
            if (!isset($row['t'])) $row['t'] = "text";
            if ($row['t'] == 'combo' || $row['t'] == 'autocomplete') {
                $field = isset($row['j']) ? $row['j'] : 'data.'.$row['n'];
                $selectFields[] = $field.' AS '.$row['n'];
                $selectFields[] = $row['f'].' AS '.$row['n'].'Name';
            } else {
                $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
                if ($row['n'] == 'A') $selectFields[] = DB::raw("CASE WHEN ".$field."=1 THEN 'Y' ELSE 'N' END AS ".$row['n']);
                elseif ($row['n'] == 'Date') $selectFields[] = DB::raw("DATE_FORMAT(".$field.", '%Y-%m-%d') AS ".$row['n']);
                else $selectFields[] = $field.' AS '.$row['n'];  

            }
        }
        
        $page = $request->query->has('page') ? $request->query('page') : 1;
        $size = $request->query->has('size') ? $request->query('size') : 20;
        $sort = $request->query->has('sort') ? $request->query('sort') : $defaultSort;
        $sorttype = $request->query->has('sorttype') ? $request->query('sorttype') : ($sort == 'Date' ? 'desc' : 'asc');
        $stringSearch=null;
        foreach($fields as $row) {
            $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
            // if ($request->has($row['n'])) $data = $data->whereIn($field,$request->query($row['n']));            
            if ($request->has($row['n'])) $data = $data->where($field,'LIKE',$request->query($row['n'])[0].'%');
                // dd($field.' '.$request->query($row['n'])[0]);
            if ($request->has('search')) $stringSearch = ($stringSearch ? $stringSearch." OR " : "").$field." LIKE '".$request->query('search')."%'";
        }
        if ($stringSearch) $data = $data->whereRaw("(".$stringSearch.")");
        // if ($request->has('search')) {
        //     $data = $data->where(function($query) use ($fields,$request) {
        //         foreach($fields as $row) {
        //             $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
        //             $query->orWhere($field, $request->query('search'));
        //         }
        //     }
        // }
        foreach($fields as $row) {
            if ($sort == $row['n']) {
                $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
                $sort = $field;
                break;
            }
        }
        return $data->select($selectFields)->orderBy($sort, $sorttype)->limit(500)->paginate($size);
    }

    public function fields($fields, $withoutAction = false)
    {
        $returnfield = [];
        if (!$withoutAction) $returnfield[] = [
            'headerName' => '',
            'field' => 'Action',
            'width' => 50,
            'resizable' => false,
            'cellRenderer' => 'comboBoxCell',
            // 'cellRendererParams' => [
            //     'edit' => true,
            //     'delete' => true,
            //     ]
            ];
        foreach($fields as $row) {
            $type = 'inputtext';
            $required = false;
            if (!isset($row['t'])) $row['t'] = "text";
            if (isset($row['r'])) if ($row['r'] == 1) $required = true;
            switch ($row['t']) {
                case 'autocomplete':
                    $type = 'autocomplete';
                    $validationParams = $required ? "required" : null;
                    $default = isset($row['d']) ? $row['d'] : null;
                    $field = $row['n'].'Name';
                    $fieldToSave = $row['n'];
                    break;
                case 'combo':
                    $type = 'combobox';
                    $validationParams = $required ? "required" : null;
                    $default = isset($row['d']) ? $row['d'] : null;
                    $field = $row['n'].'Name';
                    $fieldToSave = $row['n'];
                    break;
                case 'bool':
                    $type = 'checkbox';
                    $validationParams = $required ? "required" : null;
                    $default = isset($row['d']) ? $row['d'] : false;
                    $field = $row['n'];
                    $fieldToSave = $row['n'];
                    break;
                case 'date':
                    $type = 'inputdate';
                    $validationParams = $required ? "required" : null;
                    $default = isset($row['d']) ? $row['d'] : now()->format('Y-m-d');
                    $field = $row['n'];
                    $fieldToSave = $row['n'];
                    break;
                case 'int':
                    $type = 'inputtext';
                    $validationParams = "integer".($required ? "|required" : "");
                    $default = isset($row['d']) ? $row['d'] : 0;
                    $field = $row['n'];
                    $fieldToSave = $row['n'];
                    break;
                case 'double':
                    $type = 'inputtext';
                    $validationParams = "decimal".($required ? "|required" : "");
                    $default = isset($row['d']) ? $row['d'] : 0;
                    $field = $row['n'];
                    $fieldToSave = $row['n'];
                    break;
                case 'picture':
                    $type = 'image';
                    $validationParams = null;
                    $default = isset($row['d']) ? $row['d'] : null;
                    $field = $row['n'];
                    $fieldToSave = $row['n'];
                    break;
                default:
                    $type = 'inputtext';
                    $validationParams = null;
                    $default = isset($row['d']) ? $row['d'] : null;
                    $field = $row['n'];
                    $fieldToSave = $row['n'];
                    break;
            }

            if ($row['n'] == 'Oid') $fieldToSave = null;
            elseif ($row['t'] == 'list') $fieldToSave = null;

            $arr =[
                'headerName' => $row['n'],
                'field' => $field,
                'fieldToSave' => $fieldToSave,
                'type' => $type,
                'filter' => 'agTextColumnFilter',
            //                'headerValueGetter' => 'this.translate',
            //                'pinned' => 'left',
            //                'filter' => true,
            ];
            $hideField = ['Oid','Code','Date','Name','Currency','Item','Account','BusinessPartner,','Status','Warehouse','User','IsActive','TotalAmount','Customer','Subtitle','PurchaseBusinessPartnerName','TravelTransportBrand','City','Stock',
            'Department','Requestor1','Requestor2','Status','Purchaser','TruckingPrimeMover'];
            if ($row['n'] == 'Oid') $hide = true;
            elseif (isset($row['h'])) $hide = $row['h'] == 1 ? true : false;
            elseif (!in_array($field, $hideField)) $hide = true;
            else $hide = false;
            
            if (isset($row['dis'])) $disabled = $row['dis'] == true ? true : false;
            else $disabled = false;
            
            if ($hide) $arr = array_merge($arr, [ 'hide' => true, ]);
            if ($disabled) $arr = array_merge($arr, [ 'disabled' => true, ]);
            if (isset($row['ol'])) $arr = array_merge($arr, [ 'overrideLabel' =>$row['ol'], ]);
            if (isset($row['hideInput'])) $arr = array_merge($arr, [ 'hideInput' =>$row['hideInput'], ]);
            
            if (!$hide) $arr = array_merge($arr, [ 'width' => $row['w'] == 0 ? 100 : $row['w'], ]);
            if ($row['n'] == 'Oid') $arr = array_merge($arr, [ 'suppressToolPanel' => true, ]);
            if ($validationParams) $arr = array_merge($arr, [ 'validationParams' => $validationParams, ]);
            if ($default) $arr = array_merge($arr, [ 'default' => $default, ]);
            $returnfield[] = $arr;
        }
        return $returnfield;
    }    

    public function dataReturn($data)
    {
        $data = collect($data);
        return [
            'data' => $data['data'],
            // 'fields' => $returnfield,
            'meta' => [
                'current_page' => $data['current_page'],
                'from' => $data['from'],
                'last_page' => $data['last_page'],
                'path' => $data['path'],
                'per_page' => $data['per_page'],
                'to' => $data['to'],
                'total' => $data['total'],
            ],
            'links' => [
                'first' => $data['first_page_url'],
                'last' => $data['last_page_url'],
                'next' => $data['next_page_url'],
                'previous' => $data['prev_page_url'],
            ],
        ];
    }

    public function save($data,$request,$disabled = [])
    {
        $company = company();
        if ($disabled !=[]) array_merge($disabled, disabledFieldsForEdit());
        // if (isset($request->Code)) if ($request->Code == '<<Auto>>') $request->Code = now()->format('ymdHis').'-'.str_random(3);
        if (isset($request->Code)) if ($request->Code == '<<Auto>>') $request->Code = now()->format('mdHis').str_random(2);
        foreach ($request as $field => $key) {
            if (in_array($field, $disabled)) continue;
            $data->{$field} = $request->{$field};
        }
        foreach ($request as $field => $key) {
            // if ($field == 'Code' && !isset($data->{$field})) $data->{$field} = now()->format('mdHis').str_random(2);
            // if ($field == 'Date' && !isset($data->{$field})) $data->{$field} = \Carbon\Carbon\Carbon::now();
            if ($field == 'Date' && !isset($data->{$field})) $data->{$field} = now()->addHours(company_timezone())->toDateTimeString();
            if ($field == 'ItemUnit' && !isset($data->{$field})) $data->{$field} = $company->ItemUnit;
            if ($field == 'BusinessPartner' && !isset($data->{$field})) $data->{$field} = $company->CustomerCash;
            // if ($field == 'Status' && !isset($data->{$field})) $data->{$field} = \App\Core\Internal\Entities\Status\Status::entry()->first()->Oid;
            if ($field == 'Status' && !isset($data->{$field})) $data->{$field} = Status::entry()->first()->Oid;
            if ($field == 'Warehouse' && !isset($data->{$field})) $data->{$field} = $company->Warehouse;
            if ($field == 'Currency' && !isset($data->{$field})) $data->{$field} = $company->Currency;
            if ($field == 'Rate' && !isset($data->{$field})) $data->{$field} = 1;
            if ($field == 'RateAmount' && !isset($data->{$field})) $data->{$field} = 1;
        }
        
        return $data;
    }

    public function defaultAction($action)
    {
        return array_merge($action, [
            [
                "name" => "Edit",
                "icon" => "SettingsIcon",
                "type" => "edit",
                "action" => ""

            ],
            [
                "name" => "Delete",
                "icon" => "SettingsIcon",
                "type" => "delete",
                "action" => ""
            ],
        ]);
    }

    public function delete($data,$request)
    {
        if ($data->count() != 0) {
            foreach ($data as $rowdb) {
                $found = false;               
                foreach ($request as $rowapi) {
                    if (isset($rowapi->Oid)) if ($rowdb->Oid == $rowapi->Oid) $found = true;
                }
                if (!$found) {
                    $detail = $data->where('Oid',$rowdb->Oid)->first();
                    $detail->delete();
                }
            }
        }
    }

    public function saveDetail($data,$request,$disabled = [])
    {
        if ($data->count() != 0) {
            foreach ($data as $rowdb) {
                $found = false;               
                foreach ($request as $rowapi) {
                    if (isset($rowapi->Oid)) if ($rowdb->Oid == $rowapi->Oid) $found = true;
                }
                if (!$found) {
                    $detail = $data->where('Oid',$rowdb->Oid)->first();
                    $detail->delete();
                }
            }
        }
        $details = [];
        if ($disabled !=[]) array_merge($disabled, disabledFieldsForEdit());
        foreach ($request as $row) {
            if (isset($row->Oid)) $detail = $data->where('Oid',$row->Oid)->first();
            else $detail = new $data;

            foreach ($row as $field => $key) {
                if (in_array($field, $disabled)) continue;
                $detail->{$field} = $row->{$field};
            }
            $detail->save();
        }
        return $details;
    }

    public function config($field)
    {
        switch($field) {
            case 'Oid':
                return ['w'=> 0, 'r'=>0, 't'=>'text', 'n'=>'Oid',];
                break;
            case 'Code':
                return ['w'=> 180, 'r'=>1, 'h'=>0,  't'=>'text', 'n'=>'Code', 'd'=>'<<Auto>>',];
                break;
            case 'Date':
                return ['w'=> 250, 'r'=>1, 'h'=>0,  't'=>'date',  'n'=>'Date',];
                break;
            case 'Name':
                return ['w'=> 250, 'r'=>1, 'h'=>0, 't'=>'text', 'n'=>'Name'];
                break;
            case 'Currency':
                return ['w'=> 90, 'r'=>1, 't'=>'combo', 'n'=>'Currency', 'f'=>'c.Code',];
                break;
            case 'Item':
                return ['w'=> 200, 'r'=>1, 'h'=>0,  't'=>'autocomplete',  'n'=>'Item', 'f'=>'i.Name',];
                break;
            case 'Account':
                return ['w'=> 200, 'r'=>1, 'h'=>0,  't'=>'combo',  'n'=>'Account', 'f'=>'a.Name',];
                break;
            case 'BusinessPartner':
                return ['w'=> 200, 'r'=>1, 'h'=>0,  't'=>'combo',  'n'=>'BusinessPartner', 'f'=>'bp.Name',];
                break;
            case 'Status':
                return ['w'=> 200, 'r'=>1, 'h'=>0,  't'=>'combo',  'n'=>'Status', 'f'=>'s.Name', 'dis'=>true, 'd'=>'09128d8c-a364-4dc7-bd3b-a2d15d8fefc5'];
                break;
            case 'Warehouse':
                return ['w'=> 70,  'r'=>1, 'h'=>0,  't'=>'combo', 'n'=>'Warehouse', 'f'=>'w.Name',];
                break;
            case 'User':
                return ['w'=> 70,  'r'=>1, 'h'=>0,  't'=>'combo', 'n'=>'User', 'f'=>'u.Name',];
                break;
            case 'IsActive':
                return ['w'=> 120,  'r'=>1, 'h'=>0,  't'=>'bool', 'n'=>'IsActive',];
                break;
        }
    }
}