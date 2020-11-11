<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Core\Base\Services\HttpService;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\ItemContent;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\CompanyItemContent;
use App\Core\Master\Entities\Company;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Core\Security\Services\RoleModuleService;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;
use Validator;
use Carbon\Carbon;

class CompanyItemContentController extends Controller
{
    private $httpService;
    protected $roleService;
    private $crudController;
    public function __construct(
        RoleModuleService $roleService
    ) {
        $this->roleService = $roleService;
        // $this->roleService = new RoleModuleService($this->httpService);
        $this->httpService = new HttpService();
        $this->crudController = new CRUDDevelopmentController();
    }

    public function presearch(Request $request)
    {
        return [
            [
                'fieldToSave' => 'Company',
                'hiddenField' => 'CompanyName',
                'type' => 'combobox',
                'column' => '1/4',
                'validationParams' => 'required',
                'source' => 'company',
                'onClick' => [
                    'action' => 'request',
                    'store' => 'combosource/company',
                    'params' => null
                ]
            ],
            [
                'fieldToSave' => 'ItemType',
                'hiddenField' => 'ItemTypeName',
                'type' => 'combobox',
                'column' => '1/4',
                'default' => null,
                'source' => 'data/itemtype',
                'onClick' => [
                    'action' => 'request',
                    'store' => 'data/itemtype',
                    'params' => null
                ]
            ],
            [
                'fieldToSave' => 'Status',
                'type' => 'combobox',
                'hiddenField' => 'StatusName',
                'column' => '1/4',
                'source' => [],
                'store' => '',
                'default' => null,
                'source' => [
                    ['Oid' => '1', 'Name' => 'SET'],
                    ['Oid' => '0', 'Name' => 'UNSET'],
                ]
            ],
            [
                'type' => 'action',
                'column' => '1/4'
            ]
        ];
    }

    public function fields()
    {
        $fields = []; //f = 'FIELD, t = TITLE
        $fields[] = ['w' => 0, 'r' => 0, 't' => 'text', 'n' => 'Oid', 'fs' => 'i.Oid'];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'Company',];
        $fields[] = ['w' => 600, 'r' => 0, 't' => 'text', 'n' => 'ItemContent',];
        $fields[] = ['w' => 140, 'r' => 0, 't' => 'text', 'n' => 'ItemType',];
        $fields[] = ['w' => 100, 'r' => 0, 't' => 'text', 'n' => 'Status',];
        return $fields;
    }

    public function config(Request $request)
    {
        $fields = $this->crudController->jsonConfig($this->fields(), false, true);
        $fields[0]['cellRenderer'] = 'actionCell';
        return $fields;
    }

    public function list(Request $request)
    {
        try {
            $criteriaWhere = "";
            $company = Auth::user()->Company;
            if ($request->has('ItemType')) {
                $criteriaWhere = $criteriaWhere . " AND it.Oid = '" . $request->input('ItemType') . "'";
            }
            if ($request->has('Company')) {
                $criteriaWhere = $criteriaWhere . " AND cic.Company = '" . $request->input('Company') . "'";
            }
            if ($request->has('Status')) {
                if ($request->input('status') == 0) $criteriaWhere = $criteriaWhere . " AND cic.Oid IS NULL";
                else $criteriaWhere = $criteriaWhere . " AND cic.Oid IS NOT NULL";
            }

            $query = "SELECT i.Oid, i.Code, i.Name AS ItemContent, it.Code AS ItemType,
                    CASE WHEN cic.Oid IS NULL THEN 'N' ELSE 'Y' END AS Status, 
                    CASE WHEN cic.Oid IS NULL THEN 'N' ELSE IFNULL(cic.IsUsingPriceMethod, 'N') END AS UsingGlobalPrice, 
                    CASE WHEN ics.Oid IS NULL THEN 'N' ELSE 'Y' END AS UsingGlobalContent, 
                    ics.Oid AS ItemContentCustom,
                    cic.Oid CompanyItemContent,
                    co.Name AS Company,
                    it.Oid ItemTypeOid
                    FROM mstitemcontent i
                    LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
                    LEFT OUTER JOIN sysitemtype it ON ig.ItemType = it.Oid
                    LEFT OUTER JOIN companyitemcontent cic ON i.Oid = cic.ItemContent AND cic.Company = '" . $company . "'
                    LEFT OUTER JOIN company co ON cic.Company = co.Oid
                    LEFT OUTER JOIN mstitemcontent ics ON ics.ItemContentSource = i.Oid AND ics.Company = '" . $company . "'
                    WHERE i.GCRecord IS NULL AND Item IS NULL {$criteriaWhere} AND i.Company !='{$company}'";
            $data = DB::select($query);
            // $role = $this->roleService->list('CompanyItemContent'); //rolepermission
            // foreach ($data->data as $row) {
            //     $tmp = CompanyItemContent::findOrFail($row->Oid);
            //     $row->Action = $this->action($tmp);
            //     $row->Role = $this->generateActionMaster2($row, $role);
            // }
            foreach ($data as $row) {
                $row->Action = $this->action();
            }
            return response()->json(
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json(
                errjson($e), 
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    private function generateActionMaster2() {
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

    public function index(Request $request)
    {
        try {
            $data = DB::table($this->module . ' as data');
            $data = $this->crudController->index($this->module, $data, $request, false);
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
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

    public function show(CompanyItemContent $data)
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
            });
            return response()->json($data, Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function destroy(CompanyItemContent $data)
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

    public function action()
    {
        $url = 'companyitemcontent';
        $actionSet = [
            'name' => 'UnSet',
            'icon' => 'XIcon',
            'type' => 'confirm',
            'url' => $url . '/unsetcompany?item={Oid}',
        ];
        $actionUnSet = [
            'name' => 'Set',
            'icon' => 'CheckIcon',
            'type' => 'confirm',
            'url' => $url . '/setcompany?item={Oid}',
        ];
        $return = [];
        $return[] = $actionSet;
        $return[] = $actionUnSet;
        return $return;
    }


    public function unsetCompanyTo(Request $request)
    {
        try {
            $company = Auth::user()->Company;
            $data = DB::select("SELECT i.Oid, i.Company, ig.ItemType 
                FROM mstitemcontent i 
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                LEFT OUTER JOIN sysitemtype ity ON ity.Oid = ig.ItemType
                WHERE i.Oid = '{$request->item}'");
            $data = $data[0];
            $check = DB::select("SELECT * FROM mstitemcontent WHERE Company='{$company}' AND ItemContentParent='{$data->Oid}'");
            if ($check) DB::delete("DELETE FROM mstitem WHERE Company='{$company}' AND ItemContent='{$check[0]->Oid}'");
            DB::delete("DELETE FROM mstitemcontent WHERE Company='{$company}' AND ItemContentParent='{$data->Oid}'");
            DB::select("DELETE FROM companyitemcontent WHERE Company='{$company}' AND ItemContent='{$data->Oid}'");
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function setCompanyTo(Request $request)
    {
        try {

            $user = Auth::user();

            $itemContent = DB::select("SELECT i.Oid, i.Company, ig.ItemType 
                FROM mstitemcontent i 
                LEFT OUTER JOIN mstitemgroup ig ON ig.Oid = i.ItemGroup
                LEFT OUTER JOIN sysitemtype ity ON ity.Oid = ig.ItemType
                WHERE i.Oid = '{$request->item}'");
            $itemContent = $itemContent[0];
            $companyFrom = $itemContent->Company;
            $companyTo = $user->Company;

            //CHECK #1 TIDAK BOLEH ADA DATA SEBELUMNYA
            $check = DB::select("SELECT * FROM companyitemcontent WHERE Company='{$companyTo}' AND ItemContent='{$itemContent->Oid}' AND Item IS NULL");
            if ($check) throw new \Exception('Data is already found');

            //CHECK #2 TIDAK BOLEH BELI DARI YG TIDAK KENAL
            // $check = DB::select("SELECT * FROM companyitemcontent WHERE Company='{$companyTo}' AND ItemContent='{$itemContent->Oid}'");
            // if ($check) throw new \Exception('Data is already found');

            //CHECK #3 APAKAH ITEM ADALAH SUMBER ATAU BUKAN
            $check = DB::select("SELECT * FROM companyitemcontent WHERE Company='{$companyFrom}' AND ItemContent='{$itemContent->Oid}' AND Item IS NULL");

            if (!$check) {
                if ($itemContent->Company != $companyFrom) throw new \Exception('Data is not found'); //BUKAN SUMBER TAPI COMPANYFROM BUKAN PEMILIK
                $parent = "null";
                $level = 1;
            } else {
                $check = $check[0];
                $parent = qstr($check->Oid);
                $level = $check->Level + 1;
            }
            $arr = [
                "Oid" => "UUID()",
                "CreatedBy" => qstr($user->Oid),
                "CreatedAt" => "NOW()",
                "Company" => qstr($companyTo),
                "IsActive" => 1,
                "ItemContent" => qstr($itemContent->Oid),
                "ItemType" => qstr($itemContent->ItemType),
                "CompanyItemType" => "null", //TODO: HARUS DI-ISI
                "CompanySupplier" => qstr($companyFrom),
                "BusinessPartnerCustomer" => "null", //TODO: HARUS DI-ISI
                "Parent" => $parent,
                "Level" => $level ?: 1,
                "IsUsingPriceMethod" => 1,
            ];

            $query = "INSERT INTO companyitemcontent (%s) SELECT %s";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query);

            return response()->json($arr, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
