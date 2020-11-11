<?php

namespace App\Core\Master\Services;

use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\ItemContent;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\Currency;

class ItemPriceOutboundService
{
    public function availableSalesPrices($data) {
        if ($data['SalesTWN'] > 0) return $data['SalesTWN'];
        else if($data['SalesSGL'] > 0) return $data['SalesSGL'];
        else if($data['SalesTRP'] > 0) return $data['SalesTRP'];
        else if($data['SalesQuad'] > 0) return $data['SalesQuad'];
        else if($data['SalesQuint'] > 0) return $data['SalesQuint'];
        else if($data['SalesCHT'] > 0) return $data['SalesCHT'];
        else if($data['SalesCWB'] > 0) return $data['SalesCWB'];
        else if($data['SalesCNB'] > 0) return $data['SalesCNB'];
        else return 0;
    }

    public function salesPriceForParent($default, $itemContent)
    {
        if (isset($itemContent)) if (!($itemContent instanceof ItemContent)) $itemContent = ItemContent::findOrFail($itemContent);
        
        //PURCHASE
        $companyItemContent = getPriceMethodItemContent($default, $itemContent);
        
        // AMBIL ITEM ATAU FEATURED ITEM
        $item = Item::where('ItemContent',$itemContent->Oid)->where('IsFeaturedItem',true)->first();
        if (!$item) $item = Item::where('ItemContent',$itemContent->Oid)->limit(1)->first();

        if (!$item) return [ //ITEMCONTENT YANG TDK ADA DETAIL
            'SalesSGL' => 0,
            'SalesTWN' => 0,
            'SalesTRP' => 0,
            'SalesQuad' => 0,
            'SalesQuint' => 0,
            'SalesCHT' => 0,
            'SalesCWB' => 0,
            'SalesCNB' => 0,
        ];

        return $this->salesPriceForDetail($default, $item, $companyItemContent);
    }
    
    public function salesPriceForDetail($default, $item, $companyItemContent)
    {
        $businessPartner = BusinessPartner::findOrFail($default->user ? $default->user->BusinessPartner : $default->company->CustomerCash);
        
        if (isset($item)) if (!($item instanceof Item)) $item = Item::findOrFail($item);
        
        
        // CALCULATE INITIAL COST PRICE (TO BASE CURRENCY)
        $cost = $this->subCalculateCostPrice($item);
        
        // CALCULATE SALES PRICE
        if (count($companyItemContent) == 0) { // OWNER OF ITEM; NO MULTI COMPANY
            $query = "SELECT Oid, SalesAddMethod Method, SalesAddAmount1 Amount_1, SalesAddAmount2 Amount_2
                FROM trvitempricebusinesspartner 
                WHERE BusinessPartnerGroup='{$businessPartner->BusinessPartnerGroup}' AND Company='{$default->company->Oid}' AND Item='{$item->Oid}'";
            $salesSegmentPerItem = DB::select($query);

            $query = "SELECT Oid, SalesAddMethod Method, SalesAddAmount1 Amount_1, SalesAddAmount2 Amount_2
                FROM trvpricebusinesspartner 
                WHERE BusinessPartnerGroup='{$businessPartner->BusinessPartnerGroup}' AND Company='{$default->company->Oid}' AND ItemType='{$item->ItemType}' AND IsHide=0";
            $salesSegmentPerItemType = DB::select($query);

            if ($salesSegmentPerItem) { //HARGA MARKET SEGMENT PER ITEM
                $salesSegmentPerItem = $salesSegmentPerItem[0];
                $result = $this->subConvertRate($item, $default->cur->Oid,[
                    'SalesSGL' => calcPriceMethod($salesSegmentPerItem, $cost['SalesSGL']),
                    'SalesTWN' => calcPriceMethod($salesSegmentPerItem, $cost['SalesTWN']),
                    'SalesTRP' => calcPriceMethod($salesSegmentPerItem, $cost['SalesTRP']),
                    'SalesQuad' => calcPriceMethod($salesSegmentPerItem, $cost['SalesQuad']),
                    'SalesQuint' => calcPriceMethod($salesSegmentPerItem, $cost['SalesQuint']),
                    'SalesCHT' => calcPriceMethod($salesSegmentPerItem, $cost['SalesCHT']),
                    'SalesCWB' => calcPriceMethod($salesSegmentPerItem, $cost['SalesCWB']),
                    'SalesCNB' => calcPriceMethod($salesSegmentPerItem, $cost['SalesCNB']),
                ]);
                return $result;

            } elseif ($salesSegmentPerItemType) { // HARGA MARKET SEGMENT PER ITEM TYPE
                $salesSegmentPerItemType = $salesSegmentPerItemType[0];
                $result = $this->subConvertRate($item, $default->cur->Oid,[
                    'SalesSGL' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesSGL']),
                    'SalesTWN' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesTWN']),
                    'SalesTRP' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesTRP']),
                    'SalesQuad' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesQuad']),
                    'SalesQuint' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesQuint']),
                    'SalesCHT' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesCHT']),
                    'SalesCWB' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesCWB']),
                    'SalesCNB' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesCNB']),
                ]);
                return $result;

            } elseif (!$item->IsUsingPriceMethod) { // USING CUSTOMIZE NOT GLOBAL
                $result = $this->subConvertRate($item, $default->cur->Oid,[
                    'SalesSGL' => $cost['SalesSGL'] + $item->SalesSGL,
                    'SalesTWN' => $cost['SalesTWN'] + $item->SalesTWN,
                    'SalesTRP' => $cost['SalesTRP'] + $item->SalesTRP,
                    'SalesQuad' => $cost['SalesQuad'] + $item->SalesQuad,
                    'SalesQuint' => $cost['SalesQuint'] + $item->SalesQuint,
                    'SalesCHT' => $cost['SalesCHT'] + $item->SalesCHT,
                    'SalesCWB' => $cost['SalesCWB'] + $item->SalesCWB,
                    'SalesCNB' => $cost['SalesCNB'] + $item->SalesCNB,
                ]);
                return $result;

            } else { // USING GLOBAL (ONLY 1 RECORD)
                $data = getPriceMethodItemType($default, $item->ItemTypeObj->Code);
                return $this->subCalculatePriceFromMultiCompany($item, $data, $default->cur->Oid); 
            }
        } else { // USING MULTI COMPANY
            return $this->subCalculatePriceFromMultiCompany($item, $companyItemContent, $default->cur->Oid); 
        }
    }

    private function subConvertRate($item, $currency, $price){ //FINAL SELL PRICE CONVERT FROM BASE CUR TO USER CUR
        $fromCur = $item->CompanyObj->CurrencyObj;
        $targetCur = $currency;
        return [
            'SalesSGL' => $fromCur->convertRate($targetCur, $price['SalesSGL'] ?: 0),
            'SalesTWN' => $fromCur->convertRate($targetCur, $price['SalesTWN'] ?: 0),
            'SalesTRP' => $fromCur->convertRate($targetCur, $price['SalesTRP'] ?: 0),
            'SalesQuad' => $fromCur->convertRate($targetCur, $price['SalesQuad'] ?: 0),
            'SalesQuint' => $fromCur->convertRate($targetCur, $price['SalesQuint'] ?: 0),
            'SalesCHT' => $fromCur->convertRate($targetCur, $price['SalesCHT'] ?: 0),
            'SalesCWB' => $fromCur->convertRate($targetCur, $price['SalesCWB'] ?: 0),
            'SalesCNB' => $fromCur->convertRate($targetCur, $price['SalesCNB'] ?: 0),
        ];
    }
    
    private function subCalculateCostPrice($item) { // CALCULATE COST PRICE FROM PURCHASE CURRENCY TO COMPANY CURRENCY (BASE CUR)
        $fromCur = $item->ItemContentObj ? $item->ItemContentObj->PurchaseCurrencyObj : $item->PurchaseCurrencyObj;
        $targetCur = $item->CompanyObj->CurrencyObj;
        if (!$fromCur) $fromCur = Currency::findOrFail(company()->Currency);
        return [
            'SalesSGL' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseSGL ?: 0),
            'SalesTWN' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseTWN ?: 0),
            'SalesTRP' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseTRP ?: 0),
            'SalesQuad' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseQuad ?: 0),
            'SalesQuint' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseQuint ?: 0),
            'SalesCHT' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseCHT ?: 0),
            'SalesCWB' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseCWB ?: 0),
            'SalesCNB' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseCNB ?: 0),
        ];
    }    

    private function subCalculatePriceFromMultiCompany($item, $data, $currency) { // CALCULATE FOR ALL MULTI COMPANY AND GLOBAL PRICE
        $cost = $this->subCalculateCostPrice($item);
        foreach ($data as $row) {
            $row->PurchasSGL = $cost['SalesSGL'];
            $row->PurchaseTWN = $cost['SalesTWN'];
            $row->PurchaseTRP = $cost['SalesTRP'];
            $row->PurchaseQuad = $cost['SalesQuad'];
            $row->PurchaseQuint = $cost['SalesQuint'];
            $row->PurchaseCHT = $cost['SalesCHT'];
            $row->PurchaseCWB = $cost['SalesCWB'];
            $row->PurchaseCNB = $cost['SalesCNB'];
            $row->AmountSGL = calcPriceMethod($row,$cost['SalesSGL'], false);
            $row->AmountTWN = calcPriceMethod($row,$cost['SalesTWN'], false);
            $row->AmountTRP = calcPriceMethod($row,$cost['SalesTRP'], false);
            $row->AmountQuad = calcPriceMethod($row,$cost['SalesQuad'], false);
            $row->AmountQuint = calcPriceMethod($row,$cost['SalesQuint'], false);
            $row->AmountCHT = calcPriceMethod($row,$cost['SalesCHT'], false);
            $row->AmountCWB = calcPriceMethod($row,$cost['SalesCWB'], false);
            $row->AmountCNB = calcPriceMethod($row,$cost['SalesCNB'], false);
            $row->SalesSGL = $cost['SalesSGL'] + $row->AmountSGL;
            $row->SalesTWN = $cost['SalesTWN'] + $row->AmountTWN;
            $row->SalesTRP = $cost['SalesTRP'] + $row->AmountTRP;
            $row->SalesQuad = $cost['SalesQuad'] + $row->AmountQuad;
            $row->SalesQuint = $cost['SalesQuint'] + $row->AmountQuint;
            $row->SalesCHT = $cost['SalesCHT'] + $row->AmountCHT;
            $row->SalesCWB = $cost['SalesCWB'] + $row->AmountCWB;
            $row->SalesCNB = $cost['SalesCNB'] + $row->AmountCNB;
            $cost['SalesSGL'] = $row->SalesSGL;
            $cost['SalesTWN'] = $row->SalesTWN;
            $cost['SalesTRP'] = $row->SalesTRP;
            $cost['SalesQuad'] = $row->SalesQuad;
            $cost['SalesQuint'] = $row->SalesQuint;
            $cost['SalesCHT'] = $row->SalesCHT;
            $cost['SalesCWB'] = $row->SalesCWB;
            $cost['SalesCNB'] = $row->SalesCNB;
        }
        $result = $this->subConvertRate($item, $currency, $cost);
        $result = array_merge($result,[
            'Details' => $data
        ]);
        return $result;
    }
}