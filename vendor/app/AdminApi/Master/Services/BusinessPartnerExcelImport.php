<?php

namespace App\AdminApi\Master\Services;

use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\BusinessPartnerGroup;
use App\Core\Master\Entities\City;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\Item;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;

class BusinessPartnerExcelImport implements OnEachRow
{
    public function onRow(Row $row)
    {
        $rowIndex = $row->getIndex();
        $row      = $row->toArray();

        if ($rowIndex === 1) {
            return null;
        }

        $company = Auth::user()->CompanyObj;
        $businessPartnerGroup = BusinessPartnerGroup::where('Name', $row[2])->first();
        $purchaseCurrency = Currency::where('Code', $row[3])->first();
        $salesCurrency = Currency::where('Code', $row[4])->first();
        $city = City::where('Name', $row[1])->first();

        $return = BusinessPartner::firstOrCreate([
            'IsActive' => true,
            'Company' => $company->Oid,
            'Code' => $row[0] ?? now()->format('ymdHis').'-'.str_random(3),
            'Name' => null, // ??? [zfx] CHECK LATER
            'City' => $city->Oid ?? $company->City,
            'BusinessPartnerGroup' => $businessPartnerGroup->Oid,
            'BusinessPartnerAccountGroup' => $businessPartnerGroup->BusinessPartnerAccountGroup,
            'IsPurchase' => true,
            'PurchaseCurrency' => $purchaseCurrency->Oid ?? $company->Currency,
            'PurchaseTax' => $businessPartnerGroup->BusinessPartnerAccountGroupObj->PurchaseTax,
            'PurchaseTerm' => $businessPartnerGroup->BusinessPartnerAccountGroupObj->PurchaseTerm,
            'IsSales' => false,
            'SalesCurrency' => $salesCurrency->Oid ?? $company->Currency,
            'SalesTax' => $businessPartnerGroup->BusinessPartnerAccountGroupObj->SalesTax,
            'SalesTerm' => $businessPartnerGroup->BusinessPartnerAccountGroupObj->SalesTerm,
            'AgentCurrency' => $company->Currency,
            'AgentAccount' => $businessPartnerGroup->BusinessPartnerAccountGroupObj->SalesInvoice,
        ]);
    
    }
}