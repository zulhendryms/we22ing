<?php

namespace App\AdminApi\Development\Controllers;

use Validator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Internal\Services\FileCloudService;
use App\Core\Base\Services\HttpService;
use App\Core\Internal\Services\AutoNumberService;
use App\AdminApi\Development\Controllers\ServerCRUDController;

use App\Core\Security\Entities\User;
use App\Core\Pub\Entities\PublicPost;
use App\Core\Internal\Entities\Status;
use App\Core\Master\Entities\CostCenter;
use App\Core\Master\Entities\PaymentTerm;

class CRUDDevelopmentController extends Controller
{
    private $httpService;
    protected $fileCloudService;
    protected $roleService;
    private $autoNumberService;
    private $CRUDController;
    private $serverCRUD;
    public function __construct()
    {
        $this->httpService = new HttpService();
        $this->httpService->baseUrl('http://api1.ezbooking.co:888')->json();
        $this->autoNumberService = new AutoNumberService();
        $this->roleService = new RoleModuleService($this->httpService);
        $this->fileCloudService = new FileCloudService();
        $this->CRUDController = new ServerCRUDController();
        $this->serverCRUD = new ServerCRUDController();
    }

    public function vueview($code)
    {
        return $this->httpService->get('portal/api/development/table/vueview?code='.$code);
    }

    public function config($table)
    {
        try {
            $tableData = $this->CRUDController->getDataJSON($table, 'all');
            $fields = $this->CRUDController->generateVueList($table, $tableData->FormType == 'Transaction');
            $fieldCombos = $this->CRUDController->functionGetFieldsComboFromTable($table, 'config');
            if (!isset($fields)) {
                throw new \Exception("Table is not found");
            }
            foreach ($fields as &$row) { //combosource
                if (gettype($row) == 'array') {
                    $row = json_decode(json_encode($row), false);
                }
                foreach ($fieldCombos as $combo) {
                    if ($row->headerName == $combo->FieldName) {
                        if ($combo->ComboSourceManual) {
                            $row->source = json_decode($combo->ComboSourceManual);
                        }
                        // else $row->source = $this->functionComboSelect($combo->TableName);
                    }
                }
            };
            if (isset($tableData->topbutton) && (isJson($tableData->topbutton) || gettype($tableData->topbutton) == 'array')) {
                $fields[0]->topButton = $tableData->topbutton;
            }
            $fields[0]->cellRenderer = 'actionCell';
            return $fields;
        } catch (\Exception $e) {
            err_return($e);
        }
    }
    public function presearch($table)
    {
        try {
            $table = $this->CRUDController->getDataJSON($table, 'presearch');
            if (isset($table) && (isJson($table) || gettype($table) == 'array')) {
                return $table;
            }
        } catch (\Exception $e) {
            err_return($e);
        }
    }
    public function action($table)
    {
        try {
            $table = $this->CRUDController->getDataJSON($table, 'action');
            if (isset($table) && (isJson($table) || gettype($table) == 'array')) {
                return $table;
            }
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    private function functionComboSelect($tableName)
    {
        //combo1
        if ($tableName == 'user') {
            return DB::table('user')
            ->select('Oid', DB::raw("UserName AS Name"))->whereRaw('GCRecord IS NULL')->where('IsActive', true)->orderBy('UserName')->limit(100)->get();
        } elseif ($tableName == 'role') {
            return DB::table('role')
            ->select('Oid', DB::raw("Name AS Name"))->orderBy('Name')->limit(100)->get();
        } elseif ($tableName == 'company') {
            return DB::table('company')
            ->select('Oid', DB::raw("Code AS Name"))->orderBy('Code')->limit(100)->get();
        } elseif ($tableName == 'mstcurrency') {
            return DB::table('mstcurrency')
            ->select('Oid', DB::raw("Code AS Name"))->orderBy('Code')->limit(100)->get();
        } elseif (in_array($tableName, ['pospointofsale'])) {
            return DB::table('pospointofsale')
            ->select('Oid', DB::raw("Code AS Name"))->orderBy('Name')->limit(100)->get();
        } else {
            return DB::table($tableName)
            ->select('Oid', DB::raw("CONCAT(Name, ' - ', Code) AS Name"))->whereRaw('GCRecord IS NULL')->orderBy('Name')->limit(100)->get();
        }
    }
    
    public function list($table, $data = null, $request, $action = false)
    {
        try {
            $logger = false;

            // ########### declaration ####################################################################
            if ($logger) {
                logger('list - 1 - '.$table);
            }
            $user = Auth::user();
            $company = $user->CompanySourceObj;
            $tableData = $this->CRUDController->getDataJSON($table, 'all');
            $fields = $this->CRUDController->generateVueList($table, $tableData->FormType == 'Transaction');
            $fieldCombos = $this->CRUDController->functionGetFieldsComboFromTable($table, 'list');
            if (in_array($tableData->Code, ['accaccount','accaccountgroup','accaccountsection'])) $defaultSort = 'Code';
            elseif (in_array($tableData->Code, ['mstcurrencyratedate'])) $defaultSort = 'Date';
            elseif ($tableData->IsFieldDate) $defaultSort = 'Date';
            elseif ($tableData->IsFieldName) $defaultSort = 'Name';
            elseif ($tableData->IsFieldCode) $defaultSort = 'Code';
            else $defaultSort = 'CreatedAt';
            if ($logger) {
                logger('list - 2 - '.$table);
            }

            // ########### table join ####################################################################
            if (!$data) {
                $data = DB::table($tableData->Code.' as data');
            }
            
            foreach ($fieldCombos as $combo) {
                $data = $data->leftJoin($combo->TableName." AS ".$combo->FieldName, $combo->FieldName.".Oid", "=", "data.".$combo->FieldName);
            }
            if ($logger) {
                logger('list - 3 - '.$table);
            }

            // ########### selected fields ####################################################################
            $selectFields = [];
            $selectFields[] = 'data.Oid';
            foreach ($fields as $row) {
                if (gettype($row) == 'array') {
                    $row = json_decode(json_encode($row), false);
                }
                if ($row->field == 'Action') {
                    continue;
                }
                if (!isset($row->type)) {
                    $row->type = "text";
                }
                if ($row->type == 'combobox' || $row->type == 'autocomplete') {
                    $field = isset($row->fieldjoin) ? $row->fieldjoin : 'data.'.$row->fieldToSave;
                    $selectFields[] = $field.' AS '.$row->fieldToSave;
                    $selectFields[] = $row->fieldToSearch.' AS '.($row->field == $row->fieldToSave ? $row->field."Name" : $row->field);
                } else {
                    $field = isset($row->fieldToSearch) ? $row->fieldToSearch : 'data.'.$row->field;
                    if ($row->field == 'IsActive') {
                        $selectFields[] = DB::raw("CASE WHEN ".$field."=1 THEN 'Y' ELSE 'N' END AS ".$row->field);
                    } elseif ($row->field == 'Date') {
                        $selectFields[] = DB::raw("DATE_FORMAT(".$field.", '%Y-%m-%d') AS ".$row->field);
                    } else {
                        $selectFields[] = DB::raw($field.' AS '.$row->field);
                    }
                }
            }
            if ($logger) {
                logger('list - 4 - '.$table);
            }


            // ########### sorting ####################################################################
            if ($defaultSort == 'Name') {
                $defaultSort = 'data.Name';
            } elseif (strpos($defaultSort, 'Name') < 1) {
                $defaultSort = 'data.'.$defaultSort;
            }
            if ($request->query->has('sort')) {
                $sort = returnDataField($request->query('sort'));
            } else {
                $sort = $defaultSort;
            }
            foreach ($fields as $row) {
                if (gettype($row) == 'array') {
                    $row = json_decode(json_encode($row), false);
                }
                if ($sort == $row->field) {
                    $field = isset($row->field) ? $row->field : 'data.'.$row->field;
                    $sort = $field;
                    break;
                }
            }
            if ($logger) {
                logger('list - 5 - '.$table);
            }


            // ########### pagination declaration ####################################################################
            $page = $request->query->has('page') ? $request->query('page') : 1;
            $size = $request->query->has('size') ? $request->query('size') : 20;
            $sort = $request->query->has('sort') ? $sort : $defaultSort;
            if ($logger) {
                logger('list - 6 - '.$table);
            }

            // ########### search ####################################################################
            if (in_array($sort, ['Date','data.Date','data.UpdatedAt','data.CreatedAt','UpdatedAt','CreatedAt'])) {
                $sortAsc = 'desc';
            } else {
                $sortAsc = 'asc';
            }
            $sorttype = $request->query->has('sorttype') ? $request->query('sorttype') : $sortAsc;
            if (!$sort) {
                $sort = $defaultSort;
            }
            
            $stringSearch=null;
            foreach ($fields as $row) {
                if (gettype($row) == 'array') {
                    $row = json_decode(json_encode($row), false);
                }
                if ($row->field == 'Action') {
                    continue;
                }
                if (isset($row->hide)) {
                    if ($row->hide) {
                        continue;
                    }
                }
                $field = !isset($row->fieldToSearch) ? 'data.'.$row->field : $field = $row->fieldToSearch;
                if ($request->has($field)) {
                    $data = $data->where($field, 'LIKE', $request->query($field)[0].'%');
                }
                if ($request->has('search')) {
                    $stringSearch = ($stringSearch ? $stringSearch." OR " : "").$field." LIKE '%".$request->query('search')."%'";
                }
            }
            if ($stringSearch) {
                $data = $data->whereRaw("(".$stringSearch.")");
            }
            if ($logger) {
                logger('list - 7 - '.$table);
            }

            // ########### company filter ####################################################################
            $found = '';
            if (substr($table, 0, 3) != 'sys') {
                $tmp = isJson($company->ModuleGlobal) ? json_decode($company->ModuleGlobal) : $company->ModuleGlobal;
                if ($found == '' && $tmp) {
                    $found = companyMultiModuleFound($tmp, $tableData->Code, 'Global');
                }
                $tmp = isJson($company->ModuleGroup) ? json_decode($company->ModuleGroup) : $company->ModuleGroup;
                if ($found == '' && $tmp) {
                    $found = companyMultiModuleFound($tmp, $tableData->Code, 'Group');
                }
                
                $criteriaCompany = companyMultiModuleCriteria($found, $company, 'Company');
                
                if ($criteriaCompany) {
                    $data->whereRaw($criteriaCompany);
                }
            }
            if ($logger) {
                logger('list - 8 - '.$table);
            }

            // ########### server side return ####################################################################
            // dd($data->first());
            $data = $data->select($selectFields)->whereRaw('data.GCRecord IS NULL')->orderBy($sort, $sorttype)->limit(500)->paginate($size);
            
            if ($logger) {
                logger('list - 9 - '.$table);
            }
            $data = collect($data);
            if ($logger) {
                logger('list - 10 - '.$table);
            }
            $data = [
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

            if ($action != false && $action != null) {
                $actionReturn = [];
                $role = $this->roleService->list($tableData->Name);
                $actionReturn = $this->roleService->generateActionMaster($role);
                if (gettype($action) == 'array') {
                    $actionReturn = array_merge($actionReturn, $action);
                }
                foreach ($data['data'] as $row) {
                    $row->Action = $actionReturn;
                }
            }
            
            return json_decode(json_encode($data), false);
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function index($table, $data = null, $request, $returnAction =false)
    {
        try {
            $tableData = $this->serverCRUD->getDataJSON($table, 'all');
            $fieldCombo = $tableData->Combo1 ?: 'data.Oid';
            if ($data == null) {
                $data = DB::table($tableData->Code.' as data');
            }
            
            if ($request->has('Item')) {
                $data->where('Item', $request->input('Item'));
            }
            $type = $request->has('type') ? $request->input('type') : 'combo';
            
            // if ($type == 'combo') $data = $data->addSelect(['Oid','Code',DB::raw($fieldCombo." AS Name")]);
            if ($type == 'combo') {
                $data = $data->addSelect(['Oid',DB::raw($fieldCombo." AS Name")]);
            }
            
            $data = $data->whereNull('GCRecord')->orderBy(DB::raw($fieldCombo))->get();

            if ($type == 'combo' || !$request->has('type')) {
                return $data;
            }
            
            if ($returnAction || $type != 'combo') {
                $result = [];
                foreach ($data as $row) {
                    $result[] = $this->detail($table, $row->Oid);
                }
                return $result;
            // return response()->json($result, Response::HTTP_OK);
            } else {
                return $data;
            }
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    private function functionSetComboData($data, $f)
    {
        // +"Oid": "aa6da29d-305a-11ea-818a-1a582ceaab05"
        // +"FieldName": "TravelHotelRoomType"
        // +"TableName": "trvhotelroomtype"
        // +"TableParentCode": "ItemContent"
        // +"TableParentDisplayName": null
        // +"TableComboCode": "TravelHotelRoomType"
        // +"TableComboField": "Name"
        // +"ComboSourceManual": null
        // +"TableFieldDisplay": "Name"
        // +"TableFormType": "Auto"
        $logger = false;
        if ($f->FieldName == 'TravelTransportRoute') $logger = true;
        if (!isset($data->{$f->FieldName})) {
            return $data;
        }
        // if ($logger) dd($data->{$f->FieldName.'Obj'});
        if (!isset($data->{$f->FieldName.'Obj'})) {
            if ($f->TableName !== 'ferferryschedule') {
                //{{url}}/admin/api/v1/report/traveltransaction?action=preview&report=acc_otherincome&DateStart=2020-06-01&DateUntil=2020-06-30
                $class = config('autonumber.'.$f->TableName);
                $class = $class::where('Oid', $data->{$f->FieldName})->first();
                if ($class) {
                    $data->{$f->FieldName.'Name'} = $class ? $class->Name : null;
                }
            }
        } else {
            $data->{$f->FieldName.'Name'} = $data->{$f->FieldName.'Obj'} ? $data->{$f->FieldName.'Obj'}->{$f->TableComboField} : null;
        }
        unset($data->{$f->FieldName.'Obj'});
        return $data;
    }

    public function detail($table, $Oid)
    {
        $logger = true;
        try {
            //declaration
            $tableData = $this->CRUDController->getDataJSON($table, 'all');
            $tableDetails = $this->CRUDController->getTableDetails($tableData);
            $fieldCombos = $this->CRUDController->functionGetFieldsComboFromTable($table, 'list');
            $fieldComboDetails = $this->CRUDController->functionGetFieldsComboFromTable($table, 'detail', $tableData);
            $user = Auth::user();
            $class = config('autonumber.'.$tableData->Code);
            $data = $class::whereNull('GCRecord');
            // dd($data->where('Oid',$Oid)->first());
            
            //getdata & with
            $with = [];
            foreach ($tableDetails as $row) {
                $with = array_merge($with, [$row->APITableParentRelationshipName]);
            }
            if ($with) {
                $data = $data->with($with);
            }
            if ($tableData->IsUsingModuleComment) {
                $data = $data->with(['Comments' => function ($query) {
                    $query->orderBy('CreatedAt');
                }]);
            }
            if ($tableData->IsUsingModuleEmail) {
                $data = $data->with(['Emails' => function ($query) {
                    $query->orderBy('CreatedAt');
                }]);
            }
            if ($tableData->IsUsingModuleApproval) {
                $data = $data->with(['Approvals' => function ($query) {
                    $query->orderBy('Sequence');
                }]);
            }
            $data = $data->where('Oid', $Oid)->first();
            // KEMUNGKINAN DATA TDK KELUAR:
            // COMPANYOBJ, USEROBJ
            // COMMENTS, APPROVALS, FILES
            
            // //combo parent
            foreach ($fieldCombos as $row) {
                $data= $this->functionSetComboData($data, $row);
            }

            // //combo detail
            foreach ($tableDetails as $detail) { //per details
                if (in_array($detail->APITableParentRelationshipName, ['Comments','Approvals','Images','Files','Logs','Emails'])) {
                    continue;
                }

                if (!$detail->EditBatchFieldGroup) { //notbatch
                    if ($detail->IsFieldSequence) {
                        $dataDetails = collect($data->{$detail->APITableParentRelationshipName})->sortBy('Sequence');
                    } elseif ($detail->IsFieldDate) {
                        $dataDetails = collect($data->{$detail->APITableParentRelationshipName})->sortBy('Date');
                    } else {
                        $dataDetails = collect($data->{$detail->APITableParentRelationshipName})->sortBy('CreatedAt');
                    }
                    $result = [];
                    foreach ($dataDetails as $row) { //per record
                        foreach ($fieldComboDetails as $combo) { //per combo di detail
                            if ($combo->FieldName == 'Company') continue;
                            if ($combo->TableName == $tableData->Code) continue;
                            if ($combo->TableParentDisplayName == $detail->APITableParentRelationshipName) {                                
                                $row= $this->functionSetComboData($row, $combo);
                            }
                        }
                        $result[] = $row;
                    }
                    unset($data->{$detail->APITableParentRelationshipName});
                    $data->{$detail->APITableParentRelationshipName} = $result;
                } else {
                    //batch
                    $criteria = "AND tbf.IsActive = TRUE
                    AND tbf.Code != 'Company'
                    AND tb.Code ='{$detail->Code}' 
                    AND tbf.IsListShowPrimary = true";
                    $firstField = $this->CRUDController->generateVueListSub($criteria);
                    if ($firstField) {
                        $firstField = $firstField[0];
                    }
            
                    // $groups = $data->{$detail->APITableParentRelationshipName}->sortBy('Description')->pluck($detail->EditBatchFieldGroup);
                    // $groups = removeDuplicateArray($groups);
                    $query = "SELECT {$detail->EditBatchFieldGroup} AS val FROM {$detail->Code} WHERE {$detail->FieldParent}='{$data->Oid}' GROUP BY {$detail->EditBatchFieldGroup}";
                    $groups = DB::select($query);
                    $arrResult = [];
                    foreach ($groups as $group) {
                        $arrGroup = [];
                        $dataDetails = collect($data->{$detail->APITableParentRelationshipName})->sortBy($firstField->Code);
                        foreach ($dataDetails as $row) {
                            if ($row->{$detail->EditBatchFieldGroup} == $group->val) {
                                $arrGroup[] = $row;
                            }
                        }
                        if ($arrGroup) {
                            $arrResult[] = [
                        'Oid' => null,
                        $firstField->Code => $group->val,
                        'Amount' => 100,
                        'Group' => true,
                        'Details' => $arrGroup,
                    ];
                        }
                    }
                    unset($data->{$detail->APITableParentRelationshipName});
                    $data->{$detail->APITableParentRelationshipName} = $arrResult;
                }
            }
            
            if ($tableData->IsUsingModuleComment) { //comment
                foreach ($data->Comments as $row) {
                    $row = $user->returnUserObj($row, 'User');
                }
            }

            if ($tableData->IsUsingModuleApproval) { //approval
                $data->Approvals = $data->Approvals->filter(function ($value, $key) use ($tableData) {
                    return $value->Action !== "Request";
                });
                foreach ($data->Approvals as $row) {
                    $row->NextUserName = $row->NextUserObj ? $row->NextUserObj->Name : null;
                    $row->UserName = $row->UserObj ? $row->UserObj->Name : null;
                }
            }
            // if ($tableData->IsUsingModuleImage)
            // if ($tableData->IsUsingModuleFile)
            // if ($tableData->IsUsingModuleEmail)
            
            //CREATEDBY UPDATEDBY
            if (isset($data->CreatedBy)) {
                $tmp = User::where('Oid', $data->CreatedBy)->first();
                $data->CreatedByName = $tmp ? $tmp->Name : null;
            }
            if (isset($data->UpdatedBy)) {
                $tmp = User::where('Oid', $data->UpdatedBy)->first();
                $data->UpdatedByName = $tmp ? $tmp->Name : null;
            }

            if ($tableData->ActionDropDownRow) {
                $data->Action = json_decode($tableData->ActionDropDownRow);
            }
            // dd($data->where('Oid',$Oid)->first());
            return $data;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function saveTotal($data)
    {
        $totalAmount = 0;
        if (isset($data->Details)) {
            foreach ($data->Details as $row) {
                $totalAmount = $totalAmount + $row->TotalAmount;
            }
        }
        if (isset($data->SubtotalAmount)) {
            $data->SubtotalAmount = $totalAmount;
        }
        if (isset($data->Price)) {
            $totalAmount = $totalAmount + $data->Price;
        }
        if (isset($data->Amount)) {
            $totalAmount = $totalAmount + $data->Amount;
        }
        if (isset($data->TaxAmount)) {
            $totalAmount = $totalAmount + $data->TaxAmount;
        }
        if (isset($data->AdditionalAmount)) {
            $totalAmount = $totalAmount + $data->AdditionalAmount;
        }
        if (isset($data->DiscountAmount)) {
            $totalAmount = $totalAmount - $data->DiscountAmount;
        }
        if (isset($data->DiscountPercentageAmount)) {
            $totalAmount = $totalAmount - $data->DiscountPercentageAmount;
        }
        if (!isset($data->Details)) {
            if (isset($data->Quantity)) {
                $totalAmount = $totalAmount * $data->Quantity;
            }
        }
        if (isset($data->TotalAmountWording)) {
            $data->TotalAmountWording = convert_number_to_words($data->TotalAmount);
        }
        $data->TotalAmount = $totalAmount;
        $rate = isset($data->Rate) ? $data->Rate : (isset($data->RateAmount) ? $data->RateAmount : 1);
        if (isset($data->TotalBase)) {
            $data->TotalBase = $rate * $data->TotalAmount;
        }
        if (isset($data->QuantityBase)) {
            $data->QuantityBase = $data->Quantity;
        }
        $data->save();
        return $data;
    }
    private function disabledFieldsForEdit()
    {
        return ['id','Index','GCRecord','OptimisticLock','CreatedAt','UpdatedAt','CreatedAtUTC','UpdatedAtUTC','CreatedBy','UpdatedBy', 'prev', 'index','Index','exists','CurrencyRateDateName'];
    }
    public function save($table, $data, $request, $dataParent = null)
    {
        try {
            $logger = false;
            //field type, validation require, permission
            $company = Auth::user()->CompanyObj ?: company();
            $tableData = $this->CRUDController->getDataJSON($table, 'all');
            $fields = $this->CRUDController->functionGetFieldsFromTable($tableData->Code);
            $class = config('autonumber.'.$tableData->Code);
            
            //check duplicate code
            $moduleExcludeCheckCode = ["trvtransactiondetail"];
            if (isset($request->Code) && !in_array($table, $moduleExcludeCheckCode)) {
                if (!in_array($request->Code, ['<<Auto>>','<<AutoGenerate>>'])) {
                    if ($request->Code != $data->Code) {
                        $tmp = $class::whereNull('GCRecord')
                            ->where('Code', $request->Code)
                            ->where('Oid', '!=', $data->Oid)
                            ->where('Company', isset($data->Company) ? $data->Company : (isset($request->Company) ? $request->Company : $dataParent->Company))
                            ->first();
                        if ($tmp) {
                            throw new \Exception($request->Code." is used before");
                        }
                    }
                }
            }
            //check duplicate code
            if (isset($request->RequestCode)) {
                if (!in_array($request->RequestCode, ['<<Auto>>','<<AutoGenerate>>'])) {
                    if ($request->RequestCode != $data->RequestCode) {
                        $tmp = $class::whereNull('GCRecord')
                            ->where('RequestCode', $request->RequestCode)
                            ->where('Oid', '!=', $data->Oid)
                            ->where('Company', isset($data->Company) ? $data->Company : (isset($request->Company) ? $request->Company : $dataParent->Company))
                            ->first();
                        if ($tmp) {
                            throw new \Exception($request->RequestCode." is used before");
                        }
                    }
                }
            }

            //parent
            if ($dataParent) {
                $data->Company = $dataParent->Company;
                $data->{$tableData->APITableParentFieldName} = $dataParent->Oid;
            }
            
            foreach ($fields as $field) {
                if (in_array($field->Code, $this->disabledFieldsForEdit())) {
                    continue;
                }
                $err = false;
                try {
                    $value = is_null($request->{$field->Code});
                } catch (\Exception $ex) {
                    $err = true;
                }

                if ($err) {
                    continue;
                }
                // if (!isset($request->{$field->Code})) continue;
                if ($logger) {
                    logger('1. SET VALUE: '.$field->Code.'; FROM: '.$data->{$field->Code}.'; TO BE: '.$request->{$field->Code});
                }
                if ($request->{$field->Code} == "" && !in_array($field->FieldType, ['bit','bool','boolean'])) {
                    $request->{$field->Code} = null;
                }
                if (isset($request->{$field->Code}->base64)) {
                    if ($request->{$field->Code} == null) {
                        if ($data->{$field->Code}) {
                            $this->fileCloudService->deleteImage($data->{$field->Code});
                        }
                        $data->{$field->Code} = null;
                    } else {
                        $prefix = $company->Code."-".(isset($data->Code) ? $data->Code : null);
                        $data->{$field->Code} = $this->fileCloudService->uploadImage($request->{$field->Code}, $data->{$field->Code}, $prefix, $tableData->IsImageMultiSize);
                    }
                } elseif (in_array($field->FieldType, ['double','int','integer','decimal','money','smallint','bigint'])) {
                    $request->{$field->Code} = $request->{$field->Code} == "" ? null : $request->{$field->Code};
                    $request->{$field->Code} = str_replace(",", "", $request->{$field->Code}); //hilangkan koma dari vue money
                    $data->{$field->Code} = (float)$request->{$field->Code} ?: 0;
                } elseif (in_array($field->FieldType, ['bit','bool','boolean'])) {
                    if ($request->{$field->Code} === null) {
                        $data->{$field->Code} = 0;
                    } elseif ($request->{$field->Code} === "1") {
                        $data->{$field->Code} = 1;
                    } elseif ($request->{$field->Code} === "0") {
                        $data->{$field->Code} = 0;
                    } elseif ($request->{$field->Code} === "true") {
                        $data->{$field->Code} = 1;
                    } elseif ($request->{$field->Code} === "false") {
                        $data->{$field->Code} = 0;
                    } elseif ($request->{$field->Code} === true) {
                        $data->{$field->Code} = 1;
                    } elseif ($request->{$field->Code} === false) {
                        $data->{$field->Code} = 0;
                    } else {
                        $data->{$field->Code} = $request->{$field->Code};
                    }
                } else {
                    $data->{$field->Code} = $request->{$field->Code};
                }
            }

            //company
            if (isset($request->Company)) {
                $data->Company = $request->Company;
            } //kdg tdk mau ke set dg yg diisi
            if (isset($request->Type) && !in_array($table, ['mstbusinesspartner','trdsalesinvoice'])) {
                $data->Type = $request->Type;
            } //kdg tdk mau ke set dg yg diisi
            //defaulvalue
            $codeAutoGenerate = false;
            foreach ($fields as $f) {
                if ($f->Code == 'Company'          && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = $company->Oid;
                }
                if ($f->Code == 'Date'             && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = now()->addHours(company_timezone())->toDateTimeString();
                }
                if ($f->Code == 'ItemUnit'         && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = $company->ItemUnit;
                }
                // if ($f->Code == 'BusinessPartner'  && !isset($data->{$f->Code})) $data->{$f->Code} = $company->CustomerCash;
                if ($f->Code == 'Status'           && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = Status::entry()->first()->Oid;
                }
                if ($f->Code == 'Warehouse'        && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = $company->POSDefaultWarehouse;
                }
                if ($f->Code == 'Currency'         && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = $company->Currency;
                }
                if ($f->Code == 'Rate'             && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = 1;
                }
                if ($f->Code == 'RateAmount'       && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = 1;
                }
                if ($f->Code == 'IsActive'         && !isset($data->{$f->Code})) {
                    $data->{$f->Code} = 1;
                }
                if ($f->Code == 'Code') {
                    if (!isset($data->Code)) {
                        $data->Code = '<<Auto>>';
                    }
                }
                if ($f->Code == 'Code') {
                    if ($data->{$f->Code} == '<<AutoGenerate>>' || $data->{$f->Code} == '<<Auto>>') {
                        $codeAutoGenerate = true;
                    }
                }
                if ($logger) {
                    logger('2. DEF VALUE: '.$f->Code.': '.$data->{$f->Code});
                }
            }
            $data->save();
            
            //autogenerate
            if ($codeAutoGenerate) {
                $data->Code = $this->autoNumberService->generate($data, $table);
                $data->save();
            }
            return $data;
        } catch (\Exception $e) {
            throw err_return($e);
        }
    }
    
    public function saving($table, $request, $Oid = null, $returnAction = true)
    {
        try {
            $tableData = $this->CRUDController->getDataJSON($table, 'all');
            $tableDetails = $this->CRUDController->getTableDetails($tableData);
            $class = config('autonumber.'.$tableData->Code);
            
            if (!$Oid) {
                $data = new $class();
            } else {
                $data = $class::findOrFail($Oid);
            }
            DB::transaction(function () use ($request, &$data, $tableData, $tableDetails) {
                $r = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
                $data = $this->save($tableData->Code, $data, $r);

                $found = false;
                $tmp = json_decode(Auth::user()->CompanyObj->CostCenterGenerate);
                if ($tmp) {
                    foreach ($tmp as $row) {
                        if ($row == $tableData->Name) {
                            $found = true;
                        }
                    }
                }
                if ($found) {
                    $costcenter = CostCenter::where($tableData->Name, $data->Oid)->orWhere('Oid', $data->Oid)->first();
                    if (!$costcenter) {
                        $costcenter = new CostCenter();
                    }
                    $costcenter->Type = $tableData->Name;
                    $costcenter->Oid = $data->Oid;
                    $costcenter->Company = $data->Company;
                    $costcenter->Code = $data->Code;
                    $costcenter->{$tableData->Name} = $data->Oid;
                    $costcenter->Name = $data->Name.' ('.$tableData->Name.') '.$data->CompanyObj->Code;
                    $costcenter->GCRecord = null;
                    $costcenter->save();
                }

                foreach ($tableDetails as $tbd) {
                    if (isset($r->{$tbd->APITableParentRelationshipName})) {
                        if (isset($r->{$tbd->APITableParentRelationshipName}[0]->Group)) { // NOT BATCH REMODELED
                            $arrResult = [];
                            foreach ($r->{$tbd->APITableParentRelationshipName} as $group) {
                                foreach ($group->Details as $groupdtl) {
                                    $arrResult[] = $groupdtl;
                                }
                            }
                            $r->{$tbd->APITableParentRelationshipName} = $arrResult;
                        }

                        $this->deleteDetail($data->{$tbd->APITableParentRelationshipName}, $r->{$tbd->APITableParentRelationshipName});
                        $detail = config('autonumber.'.$tbd->Code);
                        
                        //sequence
                        $sequence = null;
                        if ($tbd->IsFieldSequence) {
                            try {
                                $sequence = ($detail::where($tableData->Name, $data->Oid)->max('Sequence') ?: 0 + 1);
                            } catch (\Exception $ex) {
                                $err = true;
                            }
                        }
                        
                        foreach ($r->{$tbd->APITableParentRelationshipName} as $row) {
                            // logger($row->POSFeatureInfo);
                            if (isset($row->Oid)) {
                                $detail = $detail::where('Oid', $row->Oid)->first();
                            } else {
                                $detail = new $detail();
                            }
                            $detail = $this->save($tbd->Code, $detail, $row, $data);
                            if ($sequence != null && $tbd->IsFieldSequence) {
                                if (!isset($detail->Sequence) || $detail->Sequence == 0) {
                                    $detail->Sequence = $sequence;
                                    $sequence = $sequence + 1;
                                }
                            }
                            $detail->save();
                        }
                        $data->load($tbd->APITableParentRelationshipName);
                        $data->fresh();
                    }
                }
                if (!$data) {
                    throw new \Exception("Data is failed to save");
                }
            });
            
            if ($returnAction) {
                $role = $this->roleService->list($table); //rolepermission
                $data = $this->detail($table, $data->Oid);
                $data->Action = $this->roleService->generateActionMaster($role);
                return $data;
            } else {
                return $data;
            }
        } catch (\Exception $e) {
            throw err_return($e);
        }
    }

    public function delete($table, $data, $force = false)
    {
        try {
            DB::transaction(function () use ($data, $table) {
                if (gettype($data) == 'string') {
                    $class = config('autonumber.'.$table);
                    $data = $class::where('Oid', $data)->first();
                }
                if (!$data) {
                    throw new \Exception("Data is failed to save");
                }
                $tableData = $this->CRUDController->getDataJSON($table, 'all');

                $tableConstraints = $this->CRUDController->getTableConstraint($tableData);
                foreach ($tableConstraints as $tb) {
                    $query = "SELECT * FROM {$tb->TableCode} WHERE {$tb->Code}='{$data->Oid}'";
                    // logger($query);
                    $check = DB::select($query);
                    if ($check) {
                        $check = $check[0];
                        $reff = isset($check->Code) ? $check->Code : null;
                        if (!$reff) {
                            $reff = isset($check->Name) ? $check->Name : null;
                        }
                        if (!$reff) {
                            $reff = $check->Oid;
                        }
                        throw new \Exception("Delete fail, it is in used at ".$tb->Name." ".$reff);
                    }
                }

                $tableDetails = $this->CRUDController->getTableDetails($tableData);
                foreach ($tableDetails as $tb) {
                    if (in_array($tb->Code, ['pubcomment','pubapproval','mstimage','pubfile','pubemail'])) {
                        continue;
                    }
                    DB::delete("DELETE FROM {$tb->Code} WHERE {$tableData->Name}='{$data->Oid}'");
                }
                if ($tableData->IsUsingModuleComment) {
                    DB::delete("DELETE FROM pubcomment WHERE PublicPost='{$data->Oid}'");
                }
                if ($tableData->IsUsingModuleApproval) {
                    DB::delete("DELETE FROM pubapproval WHERE PublicPost='{$data->Oid}' OR ObjectOid='{$data->Oid}'");
                }
                if ($tableData->IsUsingModuleEmail) {
                    DB::delete("DELETE FROM pubemail WHERE PublicPost='{$data->Oid}' OR ObjectOid='{$data->Oid}'");
                }
                if ($tableData->IsUsingModuleImage) {
                    DB::delete("DELETE FROM mstimage WHERE PublicPost='{$data->Oid}'");
                }
                if ($tableData->IsUsingModuleFile) {
                    DB::delete("DELETE FROM pubfile WHERE PublicPost='{$data->Oid}'");
                }
                $post = PublicPost::with('Likes', 'Approvals', 'Comments', 'Notifications')->where('Oid', $data->Oid)->first();
                if ($post) {
                    foreach ($post->Likes as $row) {
                        DB::delete("DELETE FROM pubpostlike WHERE PublicPost='{$data->Oid}'");
                    }
                    foreach ($post->Approvals as $row) {
                        DB::delete("DELETE FROM pubapproval WHERE PublicPost='{$data->Oid}'");
                    }
                    foreach ($post->Comments as $row) {
                        DB::delete("DELETE FROM pubcomment WHERE PublicPost='{$data->Oid}'");
                    }
                    foreach ($post->Notifications as $row) {
                        DB::delete("DELETE FROM notification WHERE PublicPost='{$data->Oid}'");
                    }
                    $post->delete();
                }
                $data->delete();
            });
            return response()->json(
                null,
                Response::HTTP_NO_CONTENT
            );
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function deleteDetail($data, $request)
    {
        try {
            if ($data->count() != 0) {
                foreach ($data as $rowdb) {
                    $found = false;
                    foreach ($request as $rowapi) {
                        if (isset($rowapi->Oid)) {
                            if ($rowdb->Oid == $rowapi->Oid) {
                                $found = true;
                            }
                        }
                    }
                    if (!$found) {
                        $detail = $data->where('Oid', $rowdb->Oid)->first();
                        $detail->delete();
                    }
                }
            }
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function combo($data, $fields = ['Oid', 'Name'], $order ='Oid')
    {
        try {
            $data = $data->addSelect($fields);
            $data = $data->orderBy($order)->get();
            return $data;
        } catch (\Exception $e) {
            err_return($e);
        }
    }

    public function jsonConfig($fields, $withoutAction = false, $showAll = false)
    {
        $returnfield = [];
        if (!$withoutAction) {
            $returnfield[] = [
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
        }
        foreach ($fields as $row) {
            $type = 'inputtext';
            $required = false;
            if (!isset($row['t'])) {
                $row['t'] = "text";
            }
            if (isset($row['r'])) {
                if ($row['r'] == 1) {
                    $required = true;
                }
            }
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
                    $validationParams = "money".($required ? "|required" : "");
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
                    $fieldToSave = isset($row['f']) ? $row['f'] : $row['n'];
                    break;
            }

            if ($row['n'] == 'Oid') {
                $fieldToSave = 'data.Oid';
            } elseif ($row['t'] == 'list') {
                $fieldToSave = null;
            }

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
            if ($showAll == true) {
                if ($row['n'] == 'Oid') {
                    $hide = true;
                } else {
                    $hide = false;
                }
            } else {
                $hideField = ['Oid','Code','Date','Name','Currency','Item','Account','BusinessPartner,','Status','Warehouse','User','IsActive','TotalAmount','Customer','Subtitle','PurchaseBusinessPartnerName','TravelTransportBrand','City','Stock',
                'Department','Requestor1','Requestor2','Status','Purchaser','TruckingPrimeMover','CostPrice','DateExpiry','Updated','CreatedAt','DateValidFrom'];
                if ($row['n'] == 'Oid') {
                    $hide = true;
                } elseif (isset($row['h'])) {
                    $hide = $row['h'] == 1 ? true : false;
                } elseif (!in_array($field, $hideField)) {
                    $hide = true;
                } else {
                    $hide = false;
                }
            }
            
            if (isset($row['dis'])) {
                $disabled = $row['dis'] == true ? true : false;
            } else {
                $disabled = false;
            }
            
            if ($hide) {
                $arr = array_merge($arr, [ 'hide' => true, ]);
            }
            if ($disabled) {
                $arr = array_merge($arr, [ 'disabled' => true, ]);
            }
            if (isset($row['ol'])) {
                $arr = array_merge($arr, [ 'overrideLabel' =>$row['ol'], ]);
            }
            if (isset($row['fs'])) {
                $arr = array_merge($arr, [ 'fieldToSearch' =>$row['fs'], ]);
            }
            if (isset($row['hideInput'])) {
                $arr = array_merge($arr, [ 'hideInput' =>$row['hideInput'], ]);
            }
            if ($row['n']=='Oid') {
                $arr = array_merge($arr, [ 'hideInput' => true, ]);
            }
            
            if (!$hide) {
                $arr = array_merge($arr, [ 'width' => $row['w'] == 0 ? 100 : $row['w'], ]);
            }
            if ($row['n'] == 'Oid') {
                $arr = array_merge($arr, [ 'suppressToolPanel' => true, ]);
            }
            if ($validationParams) {
                $arr = array_merge($arr, [ 'validationParams' => $validationParams, ]);
            }
            if ($default) {
                $arr = array_merge($arr, [ 'default' => $default, ]);
            }
            $returnfield[] = $arr;
        }
        // $returnfield[0]['cellRenderer'] = 'actionCell';
        // $returnfield[0]['topButton'] =[
        //     [
        //     'name' => 'Add New',
        //     'icon' => 'PlusIcon',
        //     'type' => 'add'
        //     ]
        // ];
        
        if (gettype($fields) == 'array') {
            $fields = json_decode(json_encode($fields), false);
        }
        return $returnfield;
    }
    
    public function jsonList($data, $fields = [], $request, $tableName, $defaultSort = 'Name', $defaultSort2 = 'data.Oid', $withWhere = true)
    {
        $user = Auth::user();
        $company = $user->CompanyObj;
        $selectFields = [];
        foreach ($fields as $row) {
            if (!isset($row['t'])) {
                $row['t'] = "text";
            }
            if ($row['t'] == 'combo' || $row['t'] == 'autocomplete') {
                $field = isset($row['j']) ? $row['j'] : 'data.'.$row['n'];
                $selectFields[] = $field.' AS '.$row['n'];
                $selectFields[] = $row['f'].' AS '.$row['n'].'Name';
            } else {
                if (isset($row['field'])) {
                    if ($row['field'] == 'Action') {
                        continue;
                    }
                }
                $field = isset($row['fs']) ? $row['fs'] : (isset($row['f']) ? $row['f'] : 'data.'.$row['n']);
                if ($row['n'] == 'A') {
                    $selectFields[] = DB::raw("CASE WHEN ".$field."=1 THEN 'Y' ELSE 'N' END AS ".$row['n']);
                } elseif ($row['n'] == 'Date' || $row['t'] == 'date') {
                    $selectFields[] = DB::raw("DATE_FORMAT(".$field.", '%Y-%m-%d') AS ".$row['n']);
                } elseif (isset($row['count'])) {
                    $selectFields[] = DB::raw("COUNT(".$field.") AS ".$row['n']);
                } else {
                    $selectFields[] = $field.' AS '.$row['n'];
                }
            }
        }
        $page = $request->query->has('page') ? $request->query('page') : 1;
        $size = $request->query->has('size') ? $request->query('size') : 20;
        $sort = $request->query->has('sort') ? $request->query('sort') : $defaultSort;
        
        if ($sort == 'Date' || $sort == 'data.UpdatedAt' || $sort == 'data.CreatedAt') {
            $sortAsc = 'desc';
        } else {
            $sortAsc = 'asc';
        }
        $sorttype = $request->query->has('sorttype') ? $request->query('sorttype') : $sortAsc;
        $stringSearch=null;
        foreach ($fields as $row) {
            $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
            if ($withWhere == true) {
                if ($request->has($row['n'])) {
                    $data = $data->where($field, 'LIKE', '%'.$request->query($row['n'])[0].'%');
                    // $data = $data->where($field, 'LIKE', '%'.$request->query($row['n']).'%');
                }
            }
            if ($request->has('search') && $request->input('search') != '') {
                if (strpos($field, 'Code') > 0 || strpos($field, 'Name') > 0) {
                    $stringSearch = ($stringSearch ? $stringSearch." OR " : "").$field." LIKE '%".$request->query('search')."%'";
                }
            }
        }
        if ($stringSearch) {
            $data = $data->whereRaw("(".$stringSearch.")");
        }
        
        foreach ($fields as $row) {
            if ($sort == $row['n']) {
                $field = isset($row['f']) ? $row['f'] : 'data.'.$row['n'];
                $sort = $field;
                break;
            }
        }
        
        $found = '';
        $tmp = json_decode($company->ModuleGlobal);
        if ($found == '' && $tmp) {
            $found = companyMultiModuleFound($tmp, $tableName, 'Global');
        }
        $tmp = json_decode($company->ModuleGroup);
        if ($found == '' && $tmp) {
            $found = companyMultiModuleFound($tmp, $tableName, 'Group');
        }
        $criteriaCompany = companyMultiModuleCriteria($found, $company, 'Company');
        if ($criteriaCompany) {
            $data->whereRaw($criteriaCompany);
        }
        
        if ($defaultSort2) {
            $sort2 = $defaultSort2;
            if ($sort2 == 'Date' || $sort2 == 'data.UpdatedAt' || $sort2 == 'data.CreatedAt') {
                $sortAsc2 = 'desc';
            } else {
                $sortAsc2 = 'asc';
            }
            $sorttype2 = $sortAsc2;
            return $data->select($selectFields)->whereRaw('data.GCRecord IS NULL')->orderBy($sort, $sorttype)->orderBy($sort2, $sorttype2)->limit(500)->paginate($size);
        } else {
            return $data->select($selectFields)->whereRaw('data.GCRecord IS NULL')->orderBy($sort, $sorttype)->limit(500)->paginate($size);
        }
    }

    public function jsonListReturn($data)
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

    public function jsonSave($data, $request, $disabled = [])
    {
        $company = Auth::user()->CompanyObj ?: company();
        if ($disabled !=[]) {
            array_merge($disabled, disabledFieldsForEdit());
        }
        // if (!$excludeAutoNumber) if (isset($request->Code)) if ($request->Code == '<<Auto>>') $request->Code = now()->format('mdHis').str_random(2);
        foreach ($request as $field => $key) {
            if (in_array($field, $disabled)) {
                continue;
            }
            $data->{$field} = $request->{$field};
        }
        
        foreach ($data as $field => $key) {
            if ($field == 'Company'          && !isset($data->{$field})) {
                $data->{$field} = $company->Oid;
            }
            if ($field == 'Code'             && !isset($data->{$field})) {
                $data->{$field} = now()->format('mdHis').str_random(2);
            }
            if ($field == 'Date'             && !isset($data->{$field})) {
                $data->{$field} = now()->addHours(company_timezone())->toDateTimeString();
            }
            if ($field == 'ItemUnit'         && !isset($data->{$field})) {
                $data->{$field} = $company->ItemUnit;
            }
            if ($field == 'BusinessPartner'  && !isset($data->{$field})) {
                $data->{$field} = $company->CustomerCash;
            }
            if ($field == 'Status'           && !isset($data->{$field})) {
                $data->{$field} = Status::entry()->first()->Oid;
            }
            if ($field == 'Warehouse'        && !isset($data->{$field})) {
                $data->{$field} = $company->POSDefaultWarehouse;
            }
            if ($field == 'Currency'         && !isset($data->{$field})) {
                $data->{$field} = $company->Currency;
            }
            if ($field == 'Rate'             && !isset($data->{$field})) {
                $data->{$field} = 1;
            }
            if ($field == 'RateAmount'       && !isset($data->{$field})) {
                $data->{$field} = 1;
            }
        }
        return $data;
    }

    public function jsonFieldPopup($table, $fields)
    {
        return $this->CRUDController->generateVuePopup($table, $fields);
    }

    public function sendToChat(Request $request) {
        $user = Auth::user();
        $module = $request->input('Module');
        $id = $request->input('Oid');
        $r = json_decode(json_encode(object_to_array(json_decode($request->getContent()))));
        $param = [
            "User" => $user->Oid,
            "To" => $r->User,
            "Message" => $r->Message,
            "Action" => [
                'name' => 'Open',
                'icon' => 'ArrowUpRightIcon',
                'type' => 'open_view',
                'get' => strtolower($module)."/".$id,
                'portalget' => "development/table/vueview?code=".$module,
            ]
        ];        
        return $this->httpService->post('/portal/api/chat/sendobj',$param);
    }
}
