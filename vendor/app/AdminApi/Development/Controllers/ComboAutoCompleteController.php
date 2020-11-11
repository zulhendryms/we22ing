<?php

namespace App\AdminApi\Development\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Laravel\Http\Controllers\Controller;

use App\Core\Accounting\Entities\Account;
use App\Core\Security\Entities\User;
use App\Core\Master\Entities\ItemContent;
use App\Core\Master\Entities\Item;
use App\Core\Internal\Entities\ItemType;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Internal\Entities\BusinessPartnerRole;
use App\Core\Master\Entities\BusinessPartnerGroupUser;
use App\Core\Master\Entities\Company;
use App\Core\Internal\Entities\Country;
use App\Core\Travel\Entities\TravelFlightNumber;
use App\Core\Travel\Entities\TravelHotelRoomType;
use App\Core\Trucking\Entities\TruckingAddress;
use App\Core\Production\Entities\ProductionPriceProcess;
use App\Core\Trading\Entities\PurchaseOrder;
use App\Core\Master\Entities\ItemGroup;
use App\Core\Master\Entities\Employee;
use App\Core\Master\Entities\ItemBusinessPartner;

class ComboAutoCompleteController extends Controller
{
    public function itemgroup(Request $request)
    {
        try {
            $user = Auth::user();
            $type = $request->input('type') ?: 'combo';
            $term = $request->term;

            $data = ItemGroup::with('ItemAccountGroupObj')->whereNull('GCRecord')->whereRaw("(Name LIKE '%{$term}%' OR Code LIKE '%{$term}%')")->where('IsActive',true);
            if ($user->BusinessPartner) $data = $data->where('Code', $user->BusinessPartner);
            if ($request->has('itemtype')) $data->where('ItemType', $request->input('itemtype'));
            if ($request->has('itemtypecode')) {
                $itemtype = $request->input('itemtypecode');
                $data->whereHas('ItemTypeObj', function ($query) use ($itemtype) {
                    $query->where('Code', $itemtype);
                });
            }
            if ($request->has('itemaccountgroup')) $data->where('ItemAccountGroup', $request->input('itemaccountgroup'));
            if ($type == 'combo') $data = $data->select('Oid','Code','Name','ItemAccountGroup')->with('ItemAccountGroupObj')->orderBy('Name')->take(10)->get();
            else $data = $data->with(['ItemTypeObj','ItemAccountGroupObj'])->orderBy('Name')->take(10)->get();

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
    
    public function account(Request $request)
    {
        $type = $request->input('type') ?: 'combo';
        $term = $request->term;
        $data = DB::table('accaccount as data')->whereNull('data.GCRecord')->where('data.IsActive',true);
        $data->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company');
        $data->leftJoin('accaccountgroup AS AccountGroup', 'AccountGroup.Oid', '=', 'data.AccountGroup');
        $data->leftJoin('sysaccounttype AS AccountType', 'AccountType.Oid', '=', 'data.AccountType');
        $data->whereRaw("(data.Name LIKE '%{$term}%' OR data.Code LIKE '%{$term}%')");
        
        if (in_array(strtolower($request->input('form')), ['cashbank'])) 
            $data->whereIn('AccountType.Code', ['CASH', 'BANK']);
        if (in_array(strtolower($request->input('form')), ['purchaseadditional','salesdiscount'])) 
            $data->whereIn('AccountType.Code', ['EX', 'EQ', 'OEX', 'INV', 'OP', 'FA', 'OA', 'COS', 'PWIP']);
        if (in_array(strtolower($request->input('form')), ['purchasediscount','salesadditional'])) 
            $data->whereIn('AccountType.Code', ['INC', 'OI', 'EQ']);
        if (in_array(strtolower($request->input('form')), ['purchaseprepaid'])) 
            $data->whereIn('AccountType.Code', ['PDP']);
        if (in_array(strtolower($request->input('form')), ['salesinvoice'])) 
            $data->whereIn('AccountType.Code', ['AR']);
        if (in_array(strtolower($request->input('form')), ['purchaseinvoice'])) 
            $data->whereIn('AccountType.Code', ['AP']);
        if (in_array(strtolower($request->input('form')), ['salesprepaid'])) 
            $data->whereIn('AccountType.Code', ['SDP']);
        if (in_array(strtolower($request->input('form')), ['expense'])) 
            $data->whereIn('AccountType.Code', ['EX', 'EQ', 'OEX', 'AR', 'FA', 'OA', 'OL']);
        if (in_array(strtolower($request->input('form')), ['income'])) 
            $data->whereIn('AccountType.Code', ['AP', 'INC', 'OI', 'EQ', 'OP', 'SWIP', 'OA', 'OL']);
            
        $data = $data->orderBy('data.Name')->take(30)->pluck('data.Oid');
        $data = Account::whereIn('Oid', $data)->get();
        
        $result = [];
        foreach($data as $row) {
            $currency = $row->CurrencyObj ? $row->CurrencyObj : company()->CurrencyObj;
            $fieldCurrency = $request->has('transfer') ? 'TransferCurrency' : 'Currency';
            $fieldRate = $request->has('transfer') ? 'TransferRateBase' : 'Rate';            
            $rate = $currency ? $currency->getRate() : null;
            $result[] = [
                'Oid' => $row->Oid,
                'Name' => $row->Name.' - '.$row->Code.' ('.$row->CompanyObj->Code.')',
                'Description' => $row->DefaultDescription ?: $row->Name,
                $fieldCurrency => [
                    'Oid' => $currency ? $currency->Oid : '',
                    'Name' => $currency ? $currency->Code : '',
                ],
                $fieldRate => $rate ? $rate->MidRate : 1,
            ];
        }
        return $result;
    }
    
    public function user(Request $request)
    {
        $type = $request->input('type') ?: 'combo';
        $term = $request->term;
        $user = Auth::user();  
        $result = [];   
        
        // $found = false;
        // $module = json_decode($user->CompanyObj->ModuleGlobalCombo);        
        // if ($module) foreach($module as $row) if ($row == 'user') $found = true;        
        // if ($found) {
        //     $data = DB::select("SELECT u.Oid, CONCAT(IFNULL(u.Name,''), ' ',IFNULL(u.UserName,'')) AS Name, u.BusinessPartner, 1 AS Rate, bpag.SalesInvoice AS Account
        //         FROM user u
        //         LEFT OUTER JOIN mstbusinesspartner bp ON bp.Oid = u.BusinessPartner
        //         LEFT OUTER JOIN mstcurrency c ON c.Oid = bp.SalesCurrency
        //         LEFT OUTER JOIN mstbusinesspartneraccountgroup bpag ON bpag.Oid = bp.BusinessPartnerAccountGroup
        //         WHERE u.GCRecord IS NULL AND (u.UserName LIKE '%{$term}%' OR u.Name LIKE '%{$term}%')");
        //     return $data;
        // } else {
            $data = User::whereNull('GCRecord');
       
            $data->where(function($query) use ($term) {
                $query->where('UserName','LIKE','%'.$term.'%')
                ->orWhere('Name','LIKE','%'.$term.'%');
            });
            if ($request->has('truckingdriver')) $data->whereHas('RoleObj', function ($query) {
                $query->where('IsTruckingDriver', true);
            });
            if ($request->has('company')) $data->where('Company', $user->Company);
            $data = $data->orderBy('Name')->take(10)->get();
           
            foreach($data as $row) {
                $businessPartner = $row->BusinessPartner ? $row->BusinessPartner : company()->CustomerCash;
                $businessPartner = BusinessPartner::where('Oid',$businessPartner)->first();
                $currency = $businessPartner ? $businessPartner->SalesCurrencyObj : company()->CurrencyObj;
                if ($businessPartner) {
                    $businessPartner = getObj($businessPartner);
                    $bpag = isset($businessPartner->BusinessPartnerAccountGroupObj) ? $businessPartner->BusinessPartnerAccountGroupObj->SalesInvoiceObj : company()->BusinessPartnerSalesInvoiceObj;
                    $account = getObj($bpag);
                } else {  
                    $businessPartner = getObj(null);
                    $account = getObj(null);
                }
                $emp = Employee::where('User', $row->Oid)->first();
                if ($emp) $mobile = $emp->Phone;
                else $mobile = '';
                
                $rate = $currency->getRate();
                $result[] = [
                    'Oid' => $row->Oid,
                    'Name' => $row->Name ?: $row->UserName,
                    // 'Name' => $row->Name.' '.$row->UserName,
                    'BusinessPartner' => $businessPartner,
                    'Currency' => $currency->Oid,
                    "Rate" => $rate ? $rate->MidRate : 1,
                    "StaffMobile" => $mobile,
                    'Account' => $account
                ];
            }
            return $result;
        // }
    }    

    public function businesspartner(Request $request)
    {
        $logger = false;
        $type = $request->input('type') ?: 'combo';
        $roletype = null;
        $term = $request->term;
        $user = Auth::user();

        //STARTING
        $data = DB::table('mstbusinesspartner as data')->whereNull('data.GCRecord')->where('data.IsActive',true);
        $data->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company');
        $data->leftJoin('mstbusinesspartnergroup AS BusinessPartnerGroup', 'BusinessPartnerGroup.Oid', '=', 'data.BusinessPartnerGroup');
        $data->leftJoin('sysbusinesspartnerrole AS BusinessPartnerRole', 'BusinessPartnerRole.Oid', '=', 'BusinessPartnerGroup.BusinessPartnerRole');
        if ($logger) $data = $data->limit(1);
        $tmp = Company::whereNull('GCRecord')->pluck('BusinessPartner');
        if ($tmp) $data->whereNotIn('data.Oid',$tmp);
        $data->whereRaw("(data.Name LIKE '%{$term}%' OR data.Code LIKE '%{$term}%')");

        //CRITERIA
        if ($request->has('role')) {  
            $roletype = $request->input('role');
            if ($roletype == 'Supplier') $data->where('BusinessPartnerRole.IsSupplier', true);
            elseif ($roletype == 'Customer') $data->where('BusinessPartnerRole.IsCustomer', true);
            elseif ($roletype == '3') $data->where('BusinessPartnerRole.IsSupplier', true);
            elseif ($roletype == '2') $data->where('BusinessPartnerRole.IsCustomer', true);
        }
        if ($request->has('businesspartnerrolecode')) {
            $bprolecode = $request->input('businesspartnerrolecode');
            if (in_array($bprolecode, ['Hotel','Attraction','Outbound','Transport','Restaurant'])) $bprolecode = "Supplier".$bprolecode;
            if ($bprolecode == 'User') {
                $bprolecode = 'Customer';
                $bprole = BusinessPartnerRole::where('Code',$bprolecode)->first();
                $roletype = $bprole->IsSupplier ? 'Supplier' : 'Customer';
            } else {
                if ($bprolecode == '3') $bprolecode = 'Supplier';
                elseif ($bprolecode == '2') $bprolecode = 'Customer';
                $bprole = BusinessPartnerRole::where('Code',$bprolecode)->first();
                $roletype = !isset($bprole) ? 'Supplier' : ($bprole->IsSupplier ? 'Supplier' : 'Customer');
                if ($bprolecode == 'Supplier') {
                    $data = $data->where('BusinessPartnerRole.Oid', $bprole->Oid);
                } else {
                    $data = $data->where('BusinessPartnerGroup.BusinessPartnerRole', $bprole->Oid);
                }
            }
        }

        // FILTER BUSINESSPARTNERGROUPUSER MARKET
        if ($roletype == 'Customer') {
            $businessPartnerGroupUser = BusinessPartnerGroupUser::select('BusinessPartnerGroup')->where('User', $user->Oid)->pluck('BusinessPartnerGroup');
            if ($businessPartnerGroupUser->count() > 0) $data->whereIn('BusinessPartnerGroup.Oid', $businessPartnerGroupUser);
        }

        // FILTER COMPANY
        //LILY GA MUNCUL BUSINESSPARTNER
        // $criteriaCompany = companyMultiModuleSearch('mstbusinesspartner');
        // if ($criteriaCompany) $data->whereRaw($criteriaCompany);

        // FILTER BUKAN STAF
        if ($user->BusinessPartner) $data = $data->where('data.Oid', $user->BusinessPartner);

        $data = $data->orderBy('data.Name')->take(20)->pluck('data.Oid');
        $data = BusinessPartner::whereIn('Oid', $data)->get();

        $result = [];
        foreach($data as $row) {
            $bpag = $row->BusinessPartnerAccountGroupObj ? $row->BusinessPartnerAccountGroupObj : null;
            if ($roletype == 'Supplier') {
                $accountDelivery = $bpag ? $bpag->PurchaseDeliveryObj : company()->BusinessPartnerPurchaseDeliveryObj;
                $accountInvoice = $bpag ? $bpag->PurchaseInvoiceObj : company()->BusinessPartnerPurchaseInvoiceObj;
                $paymentTerm = $bpag ? $bpag->PurchaseTermObj : $row->PurchaseTermObj;
                $currency = $row->PurchaseCurrencyObj ?: company()->CurrencyObj;
            } else {
                $accountDelivery = $bpag ? $bpag->SalesDeliveryObj : company()->BusinessPartnerSalesDeliveryObj;
                $accountInvoice = $bpag ? $bpag->SalesInvoiceObj : company()->BusinessPartnerSalesInvoiceObj;
                $paymentTerm = $bpag ? $bpag->SalesTermObj : $row->SalesTermObj;
                $currency = $row->SalesCurrencyObj ?: company()->CurrencyObj;
            }
            $currency = $currency ? $currency : company()->CurrencyObj;
            $rate = $currency ? $currency->getRate() : null;
            $result[] = [
                "Oid" => $row->Oid,
                "Name" => $row->Name.' - '.($row->CompanyObj ? $row->CompanyObj->Code : null),
                "Currency" => getObj($currency,'Code'),
                "Rate" => $rate ? $rate->MidRate : 1,
                "AccountDelivery" => getObj($accountDelivery),
                "Account" => getObj($accountInvoice),
                "BusinessPartnerAccountGroup" => $bpag ? $bpag->Oid : null,
                "ContactName" => $row->ContactPerson,
                "ContactPhone" => $row->Phone,
                "ContactEmail" => $row->Email,
                "PaymentTerm" => $paymentTerm,
                "Supplier1PaymentTerm" => $paymentTerm,
                "Supplier2PaymentTerm" => $paymentTerm,
                "Supplier3PaymentTerm" => $paymentTerm,
                "DiscountAmount1" => $row->DiscountAmount1,
                "DiscountAmount2" => $row->DiscountAmount2,
                "Discount1Percentage" => $row->Discount1Percentage,
                "Discount2Percentage" => $row->Discount2Percentage
            ];
        }

        return $result;
    }

    public function itemcontent(Request $request)
    {        
        try {        
            $user = Auth::user();    
            $type = $request->input('type') ?: 'combo';
            $term = $request->term;
            $data = ItemContent::with(['PurchaseBusinessPartnerObj','ItemTypeObj','ProductionItemObj'])->whereNull('GCRecord')->where('IsActive',true);
            if ($request->has('purchasebusinesspartner')) $data->where('PurchaseBusinessPartner', $request->input('purchasebusinesspartner'));
            if ($request->has('itemgroup')) $data->where('ItemGroup', $request->input('itemgroup'));
            if ($request->has('itemaccountgroup')) $data->where('ItemAccountGroup', $request->input('itemaccountgroup'));
            if ($request->has('city')) $data->where('City', $request->input('city'));
            if ($request->has('purchasecurrency')) $data->where('PurchaseCurrency', $request->input('purchasecurrency'));
            if ($request->has('salescurrency')) $data->where('SalesCurrency', $request->input('salescurrency'));
            if ($request->has('ecommerce')) {
                $input = $request->input('ecommerce');
                $data->whereHas('ItemECommerces', function ($query) use ($input) {
                    $query->where('ECommerce', $input)->where('IsActive', 1);
                });
            }
            if ($request->has('itemtypecode')) {
                $itemtype = ItemType::where('Code',$request->input('itemtypecode'))->first();
                $data->whereHas('ItemGroupObj', function ($query) use ($itemtype) {
                    $query->where('ItemType', $itemtype->Oid);
                });
            }
            if ($request->has('itemtype')) {
                $itemtype = $request->input('itemtype');
                $data->whereHas('ItemGroupObj', function ($query) use ($itemtype) {
                    $query->where('ItemType', $itemtype);
                });
            }
            if ($request->has('parent')) $data->where('IsParent', $request->input('parent'));
            
            if ($user->BusinessPartner) $data = $data->where('PurchaseBusinessPartner', $user->BusinessPartner);
            
            $data->where(function($query) use ($term)
            {
                $query->where('Name','LIKE','%'.$term.'%')
                ->orWhere('Code','LIKE','%'.$term.'%');
            });
            $data = $data->orderBy('Name') ->take(20)->get();
            return $data;
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_NOT_FOUND
            );
        }
    }
    
    public function item(Request $request)
    {
        try {
            $default = getDefault(company()->Oid);
            $type = $request->input('type') ?: 'combo';
            $term = $request->term;
            $data = DB::table('mstitem as data')->whereNull('data.GCRecord')->where('data.IsActive',true);
            $data->leftJoin('company AS Company', 'Company.Oid', '=', 'data.Company');
            $data->leftJoin('mstitemgroup AS ItemGroup', 'ItemGroup.Oid', '=', 'data.ItemGroup');
            $data->leftJoin('sysitemtype AS ItemType', 'ItemType.Oid', '=', 'data.ItemType');
            $data->leftJoin('mstbusinesspartner AS PurchaseBusinessPartner', 'PurchaseBusinessPartner.Oid', '=', 'data.PurchaseBusinessPartner');
            $data->leftJoin('mstbusinesspartnergroup AS BusinessPartnerGroup', 'BusinessPartnerGroup.Oid', '=', 'PurchaseBusinessPartner.BusinessPartnerGroup');
            $data->whereRaw("(data.Name LIKE '%{$term}%' OR data.Code LIKE '%{$term}%')");
                
            // SPESIFIC FOR TRAVEL
            if ($request->has('itemtypecode')) $data->where('ItemType.Code', $request->input('itemtypecode'));

            //NON TRAVEL
            if ($request->has('nontravel')) $data->whereIn('ItemType.Code', ['Attraction','Ferry','Outbound','Hotel','Transport']);
                
            //OTHER CRITERIA
            if ($request->has('itemcontent')) $data->where('data.ItemContent', $request->input('itemcontent'));
            if ($request->has('itemtype')) $data->limit(10)->whereNotNull('ItemContent')->where('data.ItemType', $request->input('itemtype'));
            if ($request->has('parentoid')) $data->where('data.ParentOid', $request->input('parentoid'));
            if ($type != 'combo') $data->with(['ItemGroupObj','PurchaseBusinessPartnerObj','SalesCurrencyObj']);
            // if (!$request->has('parent') && !$request->has('detail')) $data->where('IsDetail','!=',true);
            if ($request->has('parent')) $data->where('data.IsParent', $request->input('parent'));
            if ($request->has('company')) $data->where('data.Company', $default->company);
            if ($request->has('detail')) $data->where('data.IsDetail', $request->input('detail') == 1 ? true : false);
            if ($request->has('stock')) $data->where('data.IsStock', $request->input('stock'));
            if ($request->has('auto_stock')) $data->where('data.APIType', 'auto_stock');        
            if ($request->has('businesspartnergroup')) $data->where('PurchaseBusinessPartnerGroup.BusinessPartnerGroup', $request->input('businesspartnergroup'));
            
            if ($request->input('poseticketupload') == 1) $data->whereIn('ItemType.Code', ['Travel','Transport','Hotel']);
            if ($request->input('transport') == 1) $data->whereIn('ItemType.Code', ['Transport']);
            if ($request->input('product') == 1) $data->whereIn('ItemType.Code', ['Product']);
            if ($request->input('production') == 1) $data->whereIn('ItemType.Code', ['Production']);
            if ($request->input('glass') == 1) $data->whereIn('ItemType.Code', ['Glass']);
            if ($request->input('hotel') == 1) $data->whereIn('ItemType.Code', ['Hotel']);
            $data = $data->orderBy('data.Name')->take(20)->pluck('data.Oid');
            $data = Item::whereIn('Oid', $data)->get();

            $result = [];
            if ($request->has('itemtype')) {
                foreach ($data as $row) {
                    if ($row->ItemContentObj) {
                        $companyItemContent = getPriceMethodItemContent($default, $row->ItemContentObj);
                        $companyItem = getPriceMethodItem($default, $row->ItemContentObj);
                        $itemType = $row->ItemType ? $row->ItemTypeObj->Code : $row->ItemGroupObj->ItemTypeObj->Code;
                        $name = ($row->Title ? $row->ItemContentObj->Name.' '.$row->Title : $row->Name).' - '.$row->Code;
                        if($itemType == 'Outbound'){
                            $amount = $this->priceOutboundService->salesPriceForDetail($default, $row, $companyItemContent);
                            $result[] = [
                                'Oid' => $row->Oid,
                                'Name' => $name,
                                'Description' => $row->ItemGroupObj->DefaultNote ?: $name,
                                'SalesSGL' => $amount['SalesSGL'],
                                'SalesTWN' => $amount['SalesTWN'],
                                'SalesTRP' => $amount['SalesTRP'],
                                'SalesQuad' => $amount['SalesQuad'],
                                'SalesQuint' => $amount['SalesQuint'],
                                'SalesCHT' => $amount['SalesCHT'],
                                'SalesCWB' => $amount['SalesCWB'],
                                'SalesCNB' => $amount['SalesCNB'],
                                'Note' => $row->ItemGroupObj->DefaultNote ?: null,
                            ];
                        } else if($itemType == 'Attraction') {
                            $amount = $this->priceAttractionService->salesPriceForDetail($default, $row, $companyItemContent, $companyItem);
                            $result[] = [
                                'Oid' => $row->Oid,
                                'Name' => $name,
                                'Description' => $row->ItemGroupObj->DefaultNote ?: $name,
                                'SalesAdult' => $amount['SalesAdult'],
                                'SalesChild' => $amount['SalesChild'],
                                'SalesInfant' => $amount['SalesInfant'],
                                'SalesSenior' => $amount['SalesSenior'],
                                'Note' => $row->ItemGroupObj->DefaultNote ?: null,
                            ];
        
                        }else if($itemType == 'Restaurant') {
                            $amount = $this->priceRestaurantService->salesPriceForDetail($default, $row, $companyItemContent, $companyItem);
                            $result[] = [
                                'Oid' => $row->Oid,
                                'Name' => $name,
                                'Description' => $row->ItemGroupObj->DefaultNote ?: $name,
                                'SalesAdult' => $amount['SalesAdult'],
                                'SalesChild' => $amount['SalesChild'],
                                'SalesInfant' => $amount['SalesInfant'],
                                'SalesSenior' => $amount['SalesSenior'],
                                'Note' => $row->ItemGroupObj->DefaultNote ?: null,
                            ];
                        }
                        
                    }
                    
                }        
            } else {
                foreach ($data as $row) {
                    $form = $request->has('form') ? $request->input('form') : 'sales';
                    $module = $request->has('Module') ? $request->input('Module') : null;
                    $price1=0;$price2=0;$price3=0;
                    if ($form == 'sales' && !$module) {
                        $amount = $row->SalesAmount;
                        $currency = $row->SalesCurrencyObj;
                    } else {
                        if ($module == 'purchaseorder') {
                            $dataPO = PurchaseOrder::where('Oid', $request->input('oid'))->first();
                            // dd($dataPO);
                            if ($dataPO) {
                                // dd($row->Oid.' '.$dataPO->Supplier1);
                                // $tmp = ItemBusinessPartner::where('Item', $row->Oid)->where('BusinessPartner', $dataPO->Supplier1)->first();
                                $tmp = DB::select("SELECT Price FROM mstitembusinesspartner p LEFT OUTER JOIN mstitembusinesspartnerdetail d ON p.Oid = d.ItemBusinessPartner WHERE p.BusinessPartner = '{$dataPO->Supplier1}' AND d.Item = '{$row->Oid}' LIMIT 1");
                                $price1 = $tmp ? $tmp[0]->Price : $row->PurchaseAmount;
                                $tmp = DB::select("SELECT Price FROM mstitembusinesspartner p LEFT OUTER JOIN mstitembusinesspartnerdetail d ON p.Oid = d.ItemBusinessPartner WHERE p.BusinessPartner = '{$dataPO->Supplier2}' AND d.Item = '{$row->Oid}' LIMIT 1");
                                $price2 = $tmp ? $tmp[0]->Price : $row->PurchaseAmount;
                                $tmp = DB::select("SELECT Price FROM mstitembusinesspartner p LEFT OUTER JOIN mstitembusinesspartnerdetail d ON p.Oid = d.ItemBusinessPartner WHERE p.BusinessPartner = '{$dataPO->Supplier3}' AND d.Item = '{$row->Oid}' LIMIT 1");
                                $price3 = $tmp ? $tmp[0]->Price : $row->PurchaseAmount;
                            }
                        }
                        $amount = $row->PurchaseAmount;
                        $currency = $row->PurchaseCurrencyObj;
                    }
                    $result[] = [
                        'Oid' => $row->Oid,
                        'Name' => $row->Name.' - '.$row->Code,
                        'Description' => $row->ItemGroupObj ? $row->ItemGroupObj->DefaultNote : null,
                        'ItemUnit' => getObj($row->ItemUnitObj),
                        'Currency' => getObj($currency),
                        'SalesCurrency' => getObj($row->SalesCurrencyObj),
                        'PurchaseCurrency' => getObj($row->PurchaseCurrencyObj),
                        'RequireNext' => $row->RequireNext,
                        'ItemContent' => $row->ItemContent,
                        'Amount' => $amount,
                        'Price' => $amount,
                        'Price1' => $price1,
                        'Price2' => $price2,
                        'Price3' => $price3,
                        'SalesAmount' => $row->SalesAmount,
                        'PurchaseAmount' => $row->PurchaseAmount,
                        'SalesAdult' => $row->SalesAdult,
                        'SalesChild' => $row->SalesChild,
                        'SalesInfant' => $row->SalesInfant,
                        'SalesSenior' => $row->SalesSenior,
                        'SalesSGL' => $row->SalesSGL,
                        'SalesTWN' => $row->SalesTWN,
                        'SalesTRP' => $row->SalesTRP,
                        'SalesQuad' => $row->SalesQuad,
                        'SalesQuint' => $row->SalesQuint,
                        'SalesCHT' => $row->SalesCHT,
                        'SalesCWB' => $row->SalesCWB,
                        'SalesCNB' => $row->SalesCNB,
                        'Note' => $row->ItemGroupObj ? $row->ItemGroupObj->DefaultNote : null,
                    ];
                }
            }
            return $result;            
        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
    
    public function country(Request $request)
    {
        $term = $request->term;
        $data = Country::whereNull('GCRecord');
        $data->where(function($query) use ($term) {
            $query->where('Code','LIKE','%'.$term.'%')
            ->orWhere('Name','LIKE','%'.$term.'%');
        });
        $data = $data->select('Oid','Code','Name')->orderBy('Name')->take(10)->get();
        return $data;
    }
    
    public function travelhotelroomtype(Request $request)
    {
        $term = $request->term;
        $data = TravelHotelRoomType::whereNull('GCRecord');
        $data->where(function($query) use ($term) {
            $query->where('Code','LIKE','%'.$term.'%')
            ->orWhere('Name','LIKE','%'.$term.'%');
        });
        $data = $data->select('Oid','Code','Name')->orderBy('Name')->take(10)->get();
        return $data;
    }

    public function travelflightnumber(Request $request)
    {
        $term = $request->term;
        $data = TravelFlightNumber::whereNull('GCRecord');
        $data->where(function($query) use ($term) {
            $query->where('Code','LIKE','%'.$term.'%')
            ->orWhere('Name','LIKE','%'.$term.'%');
        });
        $data = $data->select('Oid','Code','Name')->orderBy('Name')->take(10)->get();
        return $data;
    }    

    public function truckingaddress(Request $request)
    {
        $term = $request->term;
        $user = Auth::user();
        $data = DB::select("SELECT Oid,Name FROM trcaddress WHERE Name LIKE '%{$term}%'");
        return $data;
        
        $data = TruckingAddress::whereNull('GCRecord')->select(['Oid','Name']);
        $data->where(function($query) use ($term)
        {
            $query->where('Name','LIKE','%'.$term.'%')
            ->orWhere('Code','LIKE','%'.$term.'%');
        });
        $data = $data->orderBy('Name')->take(10)->get();       
        return $data;
    }    

    public function productionpriceprocess(Request $request)
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
