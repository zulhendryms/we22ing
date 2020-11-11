<?php

namespace App\AdminApi\Master\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Laravel\Http\Controllers\Controller;
use App\Core\Master\Entities\ItemContent;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\CompanyItemContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Validator;

class ItemContentCompanyController extends Controller
{
    
    public function unsetCompanyTo(Request $request) {
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

    public function setCompanyTo(Request $request) {
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
    
    public function listCompanyForItem(Request $request)
    {
        $item = ItemContent::findOrFail($request->item);
        $query = "SELECT c.Oid, c.Code, c.Name, '{$item->Name}' Item,
            CASE WHEN i.Oid IS NULL THEN FALSE ELSE TRUE END AS IsActive, i.Oid CompanyItemContent
            FROM company c 
            LEFT OUTER JOIN companyitemcontent i ON i.ItemContent='{$item->Oid}' AND c.Oid = i.Company AND i.Item IS NULL
            WHERE c.Oid != '{$item->Company}'";
        return  DB::select($query);
    }

    public function listItemForCompany(Request $request)
    {
        $company = Auth::user()->Company;
        $criteria = '';
        if ($request->has('itemtype')) $criteria = $criteria." AND it.Oid = '{$request->query('itemtype')}'";
        if ($request->has('status')) {
            if($request->input('status') == 0) $criteria = $criteria." AND cic.Oid IS NULL"; else $criteria = $criteria." AND cic.Oid IS NOT NULL"; 
        }
        $query ="SELECT i.Oid, i.Code, i.Name, it.Code AS ItemType,
            CASE WHEN cic.Oid IS NULL THEN 'N' ELSE 'Y' END AS Status, 
            CASE WHEN cic.Oid IS NULL THEN 'N' ELSE IFNULL(cic.IsUsingPriceMethod, 'N') END AS UsingGlobalPrice, 
            CASE WHEN ics.Oid IS NULL THEN 'N' ELSE 'Y' END AS UsingGlobalContent, 
            ics.Oid AS ItemContentCustom,
            cic.Oid CompanyItemContent,
            cic.Company
            FROM mstitemcontent i
            LEFT OUTER JOIN mstitemgroup ig ON i.ItemGroup = ig.Oid
            LEFT OUTER JOIN sysitemtype it ON ig.ItemType = it.Oid
            LEFT OUTER JOIN companyitemcontent cic ON i.Oid = cic.ItemContent AND cic.Company = '".$company."'
            LEFT OUTER JOIN mstitemcontent ics ON ics.ItemContentSource = i.Oid AND ics.Company = '".$company."'
            WHERE i.GCRecord IS NULL AND Item IS NULL {$criteria} AND i.Company !='{$company}'";
        $data = DB::select($query);
        foreach($data as $row) {
            $row->Role = [
                "Set" => 1,
                "Unset" => 1,
                "SetPriceForDetail" => 1,
                "SetPriceForGlobal" => 1,
            ];
        }
        return $data;
    }

    public function listPriceCompanyItemType(Request $request)
    {
        $company = Auth::user()->Company;
        return DB::select("SELECT cit.Oid, cit.ItemType,cit.IsActive, cit.Company, cit.SalesAddMethod, cit.SalesAddAmount1, cit.SalesAddAmount2, cit.SalesAdd1Method,
        cit.SalesAdd1Amount1, cit.SalesAdd1Amount2, cit.SalesAdd2Method,cit.SalesAdd2Amount1,cit.SalesAdd2Amount2, cit.SalesAdd3Method, cit.SalesAdd3Amount1,
        cit.SalesAdd3Amount2, cit.SalesAdd4Method, cit.SalesAdd4Amount1, cit.SalesAdd4Amount2, cit.SalesAdd5Method, cit.SalesAdd5Amount1, cit.SalesAdd5Amount2
            FROM companyitemtype cit
            WHERE cit.ItemType = '{$request->input('itemtype')}' AND cit.Company = '{$company}'");       
    }

    public function setPriceCompanyItemType(Request $request) {
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF
        $company = Auth::user()->Company;

        try {            
            $query = "UPDATE companyitemtype cit
                SET
                    IsActive= ".qstrbol($input->IsActive).",  
                    SalesAddMethod= ".qstr($input->SalesAddMethod).", 
                    SalesAddAmount1= ".qstrno($input->SalesAddAmount1).",
                    SalesAddAmount2= ".qstrno($input->SalesAddAmount2).",
                    SalesAdd1Method= ".qstr($input->SalesAdd1Method).",
                    SalesAdd1Amount1= ".qstrno($input->SalesAdd1Amount1).",
                    SalesAdd1Amount2= ".qstrno($input->SalesAdd1Amount2).",              
                    SalesAdd2Method= ".qstr($input->SalesAdd2Method).",
                    SalesAdd2Amount1= ".qstrno($input->SalesAdd2Amount1).",
                    SalesAdd2Amount2= ".qstrno($input->SalesAdd2Amount2).",          
                    SalesAdd3Method= ".qstr($input->SalesAdd3Method).",
                    SalesAdd3Amount1= ".qstrno($input->SalesAdd3Amount1).",
                    SalesAdd3Amount2= ".qstrno($input->SalesAdd3Amount2).",              
                    SalesAdd4Method= ".qstr($input->SalesAdd4Method).",
                    SalesAdd4Amount1= ".qstrno($input->SalesAdd4Amount1).",
                    SalesAdd4Amount2= ".qstrno($input->SalesAdd4Amount2).",              
                    SalesAdd5Method= ".qstr($input->SalesAdd5Method).",
                    SalesAdd5Amount1= ".qstrno($input->SalesAdd5Amount1).",
                    SalesAdd5Amount2= ".qstrno($input->SalesAdd5Amount2)."
                WHERE cit.ItemType = '{$request->input('itemtype')}'
                AND cit.Company = '{$company}'";
            DB::update($query);           

            return response()->json(null, Response::HTTP_NO_CONTENT);

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function listPriceCompanyItemContent(Request $request)
    {
        return DB::select("SELECT cic.Oid, cic.ItemType, cic.Company, cic.SalesAddMethod, cic.SalesAddAmount1, cic.SalesAddAmount2, cic.SalesAdd1Method,
        cic.SalesAdd1Amount1, cic.SalesAdd1Amount2, cic.SalesAdd2Method,cic.SalesAdd2Amount1,cic.SalesAdd2Amount2, cic.SalesAdd3Method, cic.SalesAdd3Amount1,
        cic.SalesAdd3Amount2, cic.SalesAdd4Method, cic.SalesAdd4Amount1, cic.SalesAdd4Amount2, cic.SalesAdd5Method, cic.SalesAdd5Amount1, cic.SalesAdd5Amount2, cic.IsUsingPriceMethod
            FROM companyitemcontent cic
            WHERE cic.Oid = '{$request->input('companyitemcontent')}'");       
    }

    public function setPriceCompanyItemContent(Request $request) {
        $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

        try {            
            $query = "UPDATE companyitemcontent cic
                SET
                    IsUsingPriceMethod= {$input->IsUsingPriceMethod}, 
                    SalesAddMethod= ".qstr($input->SalesAddMethod).", 
                    SalesAddAmount1= ".qstrno($input->SalesAddAmount1).",
                    SalesAddAmount2= ".qstrno($input->SalesAddAmount2).",
                    SalesAdd1Method= ".qstr($input->SalesAdd1Method).",
                    SalesAdd1Amount1= ".qstrno($input->SalesAdd1Amount1).",
                    SalesAdd1Amount2= ".qstrno($input->SalesAdd1Amount2).",              
                    SalesAdd2Method= ".qstr($input->SalesAdd2Method).",
                    SalesAdd2Amount1= ".qstrno($input->SalesAdd2Amount1).",
                    SalesAdd2Amount2= ".qstrno($input->SalesAdd2Amount2).",          
                    SalesAdd3Method= ".qstr($input->SalesAdd3Method).",
                    SalesAdd3Amount1= ".qstrno($input->SalesAdd3Amount1).",
                    SalesAdd3Amount2= ".qstrno($input->SalesAdd3Amount2).",              
                    SalesAdd4Method= ".qstr($input->SalesAdd4Method).",
                    SalesAdd4Amount1= ".qstrno($input->SalesAdd4Amount1).",
                    SalesAdd4Amount2= ".qstrno($input->SalesAdd4Amount2).",              
                    SalesAdd5Method= ".qstr($input->SalesAdd5Method).",
                    SalesAdd5Amount1= ".qstrno($input->SalesAdd5Amount1).",
                    SalesAdd5Amount2= ".qstrno($input->SalesAdd5Amount2)."
                WHERE cic.Oid = '{$request->input('companyitemcontent')}'";
            DB::update($query);           

            return response()->json(null, Response::HTTP_NO_CONTENT);

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

    }

    public function getItemContentCustom(Request $request) {
        $data = DB::select("SELECT * FROM mstitemcontent WHERE Oid = '{$request->item}'");
        if ($data) {
            $data = $data[0];
            $tmp = DB::select("SELECT Oid,Name FROM mstbusinesspartner WHERE Oid = '{$data->PurchaseBusinessPartner}'");
            $data->PurchaseBusinessPartnerObj = $tmp[0];
            $tmp = DB::select("SELECT * FROM mstitem WHERE ItemContentSource = '{$data->ItemContentSource}'");
            $data->Details = $tmp[0];
        }
        return response()->json($data, Response::HTTP_OK);
    }

    public function duplicateItemContent(Request $request){
        $companyItemContent = DB::select("SELECT * FROM companyitemcontent WHERE Oid = '{$request->companyitemcontent}'");
        $companyItemContent = $companyItemContent[0];
        $data = DB::select("SELECT * FROM mstitemcontent WHERE Oid = '{$companyItemContent->ItemContent}'");
        $data = $data[0];
        $check = DB::select("SELECT * FROM mstitemcontent WHERE Company='{$companyItemContent->Company}' AND ItemContentSource='{$companyItemContent->ItemContent}'");
        if ($check) throw new \Exception('Data is already found');

        if ($data) {
            $disabled = array_merge(disabledFieldsForEdit(), ['Company','Oid','Code']);
            $arr = queryInsertFromFields2($data, $disabled);
            $arr = array_merge($arr, $this->arrInsertFieldItemContent($companyItemContent));
            $query = "INSERT INTO mstitemcontent (%s) SELECT %s FROM mstitemcontent WHERE Oid = '{$data->Oid}'";
            $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
            DB::insert($query);
        }
    }

    public function listItemDetailForCompany(Request $request)
    {
        $company = Auth::user()->Company;
        $query ="SELECT i.Oid, i.Code, i.Subtitle AS Name, 
            CASE WHEN cic.Oid IS NULL THEN TRUE ELSE FALSE END AS UsingGlobalPrice, 
            cic.Oid CompanyItemContent
            FROM mstitem i
            LEFT OUTER JOIN mstitemcontent ip On ip.Oid = i.ItemContent
            LEFT OUTER JOIN companyitemcontent cic ON i.Oid = cic.Item AND cic.Company = '".$request->comp."'
            WHERE i.GCRecord IS NULL AND ip.Oid = '{$request->item}' ORDER BY i.Subtitle";
        return DB::select($query);
    }

    public function getPriceDetailForCompany(Request $request)
    {
        // return DB::select("SELECT cic.Oid, cic.Item, cic.Company, cic.SalesAddMethod, cic.SalesAddAmount1, cic.SalesAddAmount2, cic.SalesAdd1Method,
        // cic.SalesAdd1Amount1, cic.SalesAdd1Amount2, cic.SalesAdd2Method,cic.SalesAdd2Amount1,cic.SalesAdd2Amount2, cic.SalesAdd3Method, cic.SalesAdd3Amount1,
        // cic.SalesAdd3Amount2, cic.SalesAdd4Method, cic.SalesAdd4Amount1, cic.SalesAdd4Amount2, cic.SalesAdd5Method, cic.SalesAdd5Amount1, cic.SalesAdd5Amount2, cic.IsUsingPriceMethod
        //     FROM companyitemcontent cic
        //     WHERE cic.Company='{$request->input('comp')}' AND cic.Item = '{$request->input('item')}'");     
        return DB::select("SELECT cic.Oid, cic.Item, cic.Company, cic.SalesAmount, cic.SalesAmount1, cic.SalesAmount2, cic.SalesAmount3,cic.SalesAmount4, cic.SalesAmount5
            FROM companyitemcontent cic
            WHERE cic.Company='{$request->input('comp')}' AND cic.Item = '{$request->input('item')}'");    
    }

    public function createDetailPrice(Request $request) {
        try {
            $input = json_decode(json_encode(object_to_array(json_decode($request->getContent()))))   ; //WILLIAM ZEF

            $user = Auth::user();
            $companyFrom = $user->Company;
            $companyTo = $request->comp;
            $item = Item::with('ItemGroupObj')->findOrFail($request->item);

            //CHECK #1 TIDAK BOLEH ADA DATA SEBELUMNYA
            $check = DB::select("SELECT * FROM companyitemcontent WHERE Company='{$companyTo}' AND Item='{$item->Oid}'");
            if ($check) {
                $check = $check[0];
                $query = "UPDATE companyitemcontent cic
                    SET
                        SalesAmount= ".qstr($input->SalesAmount).", 
                        SalesAmount1= ".qstr($input->SalesAmount1).", 
                        SalesAmount2= ".qstr($input->SalesAmount2).", 
                        SalesAmount3= ".qstr($input->SalesAmount3).", 
                        SalesAmount4= ".qstr($input->SalesAmount4).", 
                        SalesAmount5= ".qstr($input->SalesAmount5)."
                    WHERE cic.Company = '{$request->input('comp')}' AND cic.Item = '{$request->input('item')}'";
                DB::update($query); 
                $input->Oid = $check->Oid;
                $input->Item = $check->Item;
                $input->Company = $check->Company;
                return response()->json($input, Response::HTTP_CREATED);
                
            } else {                
                $arr = [
                    "Oid" => "UUID()",
                    "CreatedBy" => qstr($user->Oid),
                    "CreatedAt" => "NOW()",
                    "Company" => qstr($companyTo),
                    "IsActive" => 1,
                    "ItemContent" => qstr($item->ItemContent),
                    "ItemType" => qstr($item->ItemType),
                    "Item" => qstr($item->Oid),
                    "CompanySupplier" => qstr($companyFrom),
                    "BusinessPartnerCustomer" => "null", //TODO: HARUS DI-ISI
                    "SalesAmount" => qstrno($input->SalesAmount),
                    "SalesAmount1" => qstrno($input->SalesAmount1),
                    "SalesAmount2" => qstrno($input->SalesAmount2),
                    "SalesAmount3" => qstrno($input->SalesAmount3),
                    "SalesAmount4" => qstrno($input->SalesAmount4),
                    "SalesAmount5" => qstrno($input->SalesAmount5),
                    // "IsUsingPriceMethod" => 0,
                    // "SalesAddMethod" => qstr($input->SalesAddMethod), 
                    // "SalesAddAmount1" => qstrno($input->SalesAddAmount1),
                    // "SalesAddAmount2" => qstrno($input->SalesAddAmount2),
                    // "SalesAdd1Method" => qstr($input->SalesAdd1Method),
                    // "SalesAdd1Amount1" => qstrno($input->SalesAdd1Amount1),
                    // "SalesAdd1Amount2" => qstrno($input->SalesAdd1Amount2),              
                    // "SalesAdd2Method" => qstr($input->SalesAdd2Method),
                    // "SalesAdd2Amount1" => qstrno($input->SalesAdd2Amount1),
                    // "SalesAdd2Amount2" => qstrno($input->SalesAdd2Amount2),          
                    // "SalesAdd3Method" => qstr($input->SalesAdd3Method),
                    // "SalesAdd3Amount1" => qstrno($input->SalesAdd3Amount1),
                    // "SalesAdd3Amount2" => qstrno($input->SalesAdd3Amount2),              
                    // "SalesAdd4Method" => qstr($input->SalesAdd4Method),
                    // "SalesAdd4Amount1" => qstrno($input->SalesAdd4Amount1),
                    // "SalesAdd4Amount2" => qstrno($input->SalesAdd4Amount2),              
                    // "SalesAdd5Method" => qstr($input->SalesAdd5Method),
                    // "SalesAdd5Amount1" => qstrno($input->SalesAdd5Amount1),
                    // "SalesAdd5Amount2" => qstrno($input->SalesAdd5Amount2)
                ];
                $query = "INSERT INTO companyitemcontent (%s) SELECT %s";
                $query = sprintf($query, implode(',', array_keys($arr)), implode(',', $arr));
                DB::insert($query);
                return response()->json($arr, Response::HTTP_CREATED);
            }

        } catch (\Exception $e) {
            return response()->json(
                errjson($e),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }    

    public function deleteDetailPrice($Oid = null)
    {
        try {            
            DB::transaction(function () use ($Oid) {
                DB::delete("DELETE FROM companyitemcontent WHERE Oid='{$Oid}'");
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

    private function arrInsertFieldItemContent($companyItemContent) {
        return [
            "Oid" => "UUID()",
            "Company" => "'".$companyItemContent->Company."'",
            "ItemContentSource" => "'".($companyItemContent->ItemContent)."'",
        ];
    }
}
