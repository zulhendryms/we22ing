<?php

namespace App\AdminApi\Development\Controllers;

use Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;
use App\Core\Security\Services\RoleModuleService;
use App\Core\Base\Exceptions\UserFriendlyException;

use App\Core\Master\Entities\Department;
use App\Core\Accounting\Entities\Account;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\Core\Master\Entities\BusinessPartnerAccountGroup;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\PaymentMethod;
use App\Core\Internal\Entities\ItemType;
use App\AdminApi\Development\Controllers\CRUDDevelopmentController;

class ComboSourceController extends Controller
{
    private $crudController;
    public function __construct() {
        $this->crudController = new CRUDDevelopmentController();
    }

    public function item(Request $request) {
        
        try {
            $user = Auth::user();
            $data = DB::table('mstitem as data');
            $data->LeftJoin('sysitemtype as ItemType','ItemType.Oid','data.ItemType');
            $data->LeftJoin('mstitemgroup as ItemGroup','ItemGroup.Oid','data.ItemGroup');
            if ($request->has('itemtypecode')) $data->where('ItemType.Code',$request->input('itemtypecode'));
            $data = $data->select('data.Oid', DB::raw("data.Code"), DB::raw("data.Name"))->whereNotNull('data.Code')->whereNotNull('data.Name')->orderBy('data.Code')->get();
            // foreach($data as $row) $row->Image = $row->Image ? $row->Image : 'https://is1-ssl.mzstatic.com/image/thumb/Purple128/v4/fe/a2/08/fea20835-7253-97ca-e93b-08ba4f5d0122/source/512x512bb.jpg';
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    public function field(Request $request) { 
        if ($request->input('Type') == 'CostCenter') {
            $fields[] = [
                'fieldToSave' => 'CostCenterGroup',
                "hiddenField" => "CostCenterGroupName",
                'type' => 'combobox',
                'column' => '1/3',
                'validationParams' => 'required',
                'source' => 'data/costcentergroup',
                'onClick' => [
                    'action' => 'request',
                    'store' => 'data/costcentergroup',
                    'params' => null
                ]
            ];
            $fields[] = $this->crudController->jsonFieldPopup('CostCenter', [
                'CostCenterGroup','Name'
            ])[0];
            return $fields;
        }
    }
    
    public function account(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = Account::whereNull('GCRecord');
            if (strtolower($request->input('form')) == 'cashbank')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['CASH', 'BANK']);
                });
            if (strtolower($request->input('form')) == 'purchaseadditional' || strtolower($request->input('form')) == 'salesdiscount')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['EX', 'EQ', 'OEX', 'INV', 'OP', 'FA', 'OA', 'COS', 'PWIP']);
                });
            if (strtolower($request->input('form')) == 'purchasediscount' || strtolower($request->input('form')) == 'salesadditional')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['INC', 'OI', 'EQ']);
                });
            if (strtolower($request->input('form')) == 'purchaseprepaid')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['PDP']);
                });
            if (strtolower($request->input('form')) == 'salesinvoice')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['AR']);
                });
            if (strtolower($request->input('form')) == 'purchaseinvoice')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['AP']);
                });
            if (strtolower($request->input('form')) == 'salesprepaid')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['SDP']);
                });
            if (strtolower($request->input('form')) == 'expense')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['EX', 'EQ', 'OEX', 'AR', 'FA', 'OA', 'OL']);
                });
            if (strtolower($request->input('form')) == 'income')
                $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['AP', 'INC', 'OI', 'EQ', 'OP', 'SWIP', 'OA', 'OL']);
                });
            // 0 income
            // 1 expense
            // 2 receipt
            // 3 payment
            // 4 transfer
            if (strtolower($request->input('additional')) == 1) {
                if ($request->input('form')=='1' || $request->input('form')=='3') $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['EX', 'EQ', 'OEX', 'AR', 'FA', 'OA', 'OL']);
                });
                if ($request->input('form')=='0' || $request->input('form')=='2') $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['AP', 'INC', 'OI', 'EQ', 'OP', 'SWIP', 'OA', 'OL']);
                });
            }
            if (strtolower($request->input('discount')) == 1) {
                if ($request->input('form')=='1' || $request->input('form')=='3') $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['AP', 'INC', 'OI', 'EQ', 'OP', 'SWIP', 'OA', 'OL']);
                });
                if ($request->input('form')=='0' || $request->input('form')=='2') $data->whereHas('AccountTypeObj', function ($query) {
                    $query->whereIn('Code', ['EX', 'EQ', 'OEX', 'AR', 'FA', 'OA', 'OL']);
                });
            }

            $data = $data->orderBy('Oid')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function department(Request $request)
    {
        $data = Department::with('PurchaserObj')->get();
        $result = [];
        foreach($data as $row) $result[] = [
            'Oid'=>$row->Oid,
            'Name'=>$row->Name,
            'Purchaser'=>[
                'Oid'=>$row->Purchaser,
                'Name'=>$row->PurchaserObj ? $row->PurchaserObj->Name : null,
            ]
        ];
        // $data = $data->LeftJoin('user as u','u.Oid','data.Purchaser');
        // $data = $this->crudController->combo($data, ['data.Oid','data.Name','Purchaser','u.Name AS PurchaserName'], 'Name');        
        return response()->json($result, Response::HTTP_OK);
    }

    public function paymentmethod(Request $request)
    {
        $company = Auth::user()->CompanyObj;
        $data = PaymentMethod::with('AccountObj')->get();
        $result = [];
        foreach ($data as $row) {
            $cur = $row->AccountObj ? ($row->AccountObj ? $row->AccountObj->CurrencyObj : $company->CurrencyObj) : $company->CurrencyObj;
            $rate = $cur->getRate(now());
            $result[] = [
                'Oid'=>$row->Oid,
                'Name'=>$row->Name,
                'Currency'=>[
                    'Oid'=>$cur->Oid,
                    'Name'=>$cur ? $cur->Name : null,
                ],
                'Rate'=> $rate ? $rate->MidRate : 1
            ];
        }
        // $data = $data->LeftJoin('user as u','u.Oid','data.Purchaser');
        // $data = $this->crudController->combo($data, ['data.Oid','data.Name','Purchaser','u.Name AS PurchaserName'], 'Name');        
        return response()->json($result, Response::HTTP_OK);
    }
    
    public function businesspartnergroup(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $data = BusinessPartnerGroup::whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('businesspartneraccountgroup')) $data->where('BusinessPartnerAccountGroup', $request->input('businesspartneraccountgroup'));
            if ($request->has('businesspartneraccountgroupcode')) {
                $businesspartner = $request->input('businesspartneraccountgroupcode');
                $data->whereHas('BusinessPartnerAccountGroupObj', function ($query) use ($businesspartner) {
                    $query->where('Code', $businesspartner);
                });
            }

            if ($request->has('businesspartnerrole')) $data->where('BusinessPartnerRole', $request->input('businesspartnerrole'));
            if ($type != 'combo') $data->with(['BusinessPartnerAccountGroupObj','BusinessPartnerRoleObj']);
            $data = $data->orderBy('Name')->get();
            if($type == 'list'){
                $businesspartners = BusinessPartnerAccountGroup::where('IsActive',1)->whereNull('GCRecord')->orderBy('Name')->get();
                foreach ($businesspartners as $bPartner) {
                    $details = [];
                    foreach ($data as $row) {
                        if ($row->BusinessPartnerAccountGroup == $bPartner->Oid)
                        $details[] = [
                            'Oid'=> $row->Oid,
                            'title' => $row->Name.' '.$row->Code,
                            'expanded' => false,
                        ];
                
                    }
    
                    $results[] = [
                        'Oid'=> $bPartner->Oid,
                        'title' => $bPartner->Name.' '.$bPartner->Code,
                        'expanded' => false,
                        'children' => $details
                    ];
                }

                return $results;
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function itemgroup(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';

            $data = ItemGroup::with('ItemAccountGroupObj')->whereNull('GCRecord');
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('itemtype')) $data->where('ItemType', $request->input('itemtype'));
            if ($request->has('itemtypecode')) {
                $itemtype = $request->input('itemtypecode');
                $data->whereHas('ItemTypeObj', function ($query) use ($itemtype) {
                    $query->where('Code', $itemtype);
                });
            }
            if ($request->has('itemaccountgroup')) $data->where('ItemAccountGroup', $request->input('itemaccountgroup'));
            if ($type != 'combo') $data->with(['ItemTypeObj','ItemAccountGroupObj']);
            $data = $data->orderBy('Name')->get();
            if($type == 'list'){
                $itemTypes = ItemType::where('IsActive',1)->whereNull('GCRecord')->orderBy('Name')->get();
                foreach ($itemTypes as $itemType) {
                    $details = [];
                    foreach ($data as $row) {
                        if ($row->ItemType == $itemType->Oid)
                        $details[] = [
                            'Oid'=> $row->Oid,
                            'title' => $row->Name.' '.$row->Code,
                            'expanded' => false,
                        ];
                    }

                    $results[] = [
                        'Oid'=> $itemType->Oid,
                        'title' => $itemType->Name.' '.$itemType->Code,
                        'expanded' => false,
                        'children' => $details
                    ];
                }

                return $results;
            } else {
                foreach ($data as $row) {
                    $tmp = $row->ItemAccountGroupObj;
                    unset($row->ItemAccountGroupObj);
                    $row->ItemAccountGroup = $tmp;
                }
            }
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function company(Request $request) {
        
        try {
            $user = Auth::user();
            $data = DB::table('company as data');
            //SECURITY FILTER COMPANY
            if ($user->CompanyAccess) {
                $tmp = json_decode($user->CompanyAccess);
                $data->whereIn('data.Code', $tmp);      
            }
            $data = $data->select('Oid', DB::raw("Code AS Name"), DB::raw("Name AS Description"), 'Image')->whereNotNull('Code')->whereNotNull('Name')->orderBy('Code')->get();
            foreach($data as $row) $row->Image = $row->Image ? $row->Image : 'https://is1-ssl.mzstatic.com/image/thumb/Purple128/v4/fe/a2/08/fea20835-7253-97ca-e93b-08ba4f5d0122/source/512x512bb.jpg';
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    public function traveltemplatenote(Request $request) {
        
        try {
            $user = Auth::user();
            $data = DB::table('trvtemplatenote as data');

            if ($request->has('Type')) $data = $data->where('Type',$request->input('Type'));
            
            // filter businesspartnergroupuser
            // $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
            // if ($businessPartnerGroupUser->count() > 0) $data->whereIn('BusinessPartnerGroup', $businessPartnerGroupUser);

            $data = $data->select('Oid', DB::raw("Name AS Name"), 'Note', DB::raw("Note AS NoteTourGuide"))->orderBy('Name')->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
