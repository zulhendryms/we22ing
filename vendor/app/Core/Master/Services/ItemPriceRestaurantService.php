<?php

namespace App\Core\Master\Services;

use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\ItemContent;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\Currency;

class ItemPriceRestaurantService
{
    public function availableSalesPrices($data) {
        if ($data['SalesAdult'] > 0) return $data['SalesAdult'];
        else if($data['SalesChild'] > 0) return $data['SalesChild'];
        else if($data['SalesInfant'] > 0) return $data['SalesInfant'];
        else if($data['SalesSenior'] > 0) return $data['SalesSenior'];
        else return 0;
    }

    public function salesPriceForParent($default, $itemContent)
    {
        if (isset($itemContent)) if (!($itemContent instanceof ItemContent)) $itemContent = ItemContent::findOrFail($itemContent);
        
        //PURCHASE
        $companyItemContent = getPriceMethodItemContent($default, $itemContent);
        $companyItem = getPriceMethodItem($default, $itemContent);

        //AMBIL ITEM ATAU FEATURED ITEM
        $item = Item::where('ItemContent',$itemContent->Oid)->where('IsFeaturedItem',true)->first();
        if (!$item) $item = Item::where('ItemContent',$itemContent->Oid)->limit(1)->first();

        if (!$item) return [ //ITEMCONTENT YANG TDK ADA DETAIL
            'SalesAdult' => 0,
            'SalesChild' => 0,
            'SalesInfant' => 0,
            'SalesSenior' => 0,
        ];

        return $this->salesPriceForDetail($default, $item, $companyItemContent, $companyItem);
        
    }

    public function salesPriceForDetail($default, $item, $companyItemContent, $companyItem)
    {
        $businessPartner = BusinessPartner::findOrFail($default->user ? $default->user->BusinessPartner : $default->company->CustomerCash);
        if (isset($item)) if (!($item instanceof Item)) $item = Item::findOrFail($item);
                
        // CALCULATE INITIAL COST PRICE & CONVERTION RATE
        $cost = $this->subCalculateCostPrice($item);
        
        // CALCULATE SALES PRICE_
        if (count($companyItemContent) == 0) { // OWNER OF ITEM; NO MULTI COMPANY            
            $query = "SELECT Oid, SalesAddMethod Method, SalesAddAmount1 Amount_1, SalesAddAmount2 Amount_2
                FROM trvitempricebusinesspartner 
                WHERE BusinessPartnerGroup='{$businessPartner->BusinessPartnerGroup}' AND Company='{$default->company->Oid}' AND Item='{$item->Oid}'";
            $salesSegmentPerItem = DB::select($query);

            $query = "SELECT Oid, SalesAddMethod Method, SalesAddAmount1 Amount_1, SalesAddAmount2 Amount_2
                FROM trvpricebusinesspartner 
                WHERE BusinessPartnerGroup='{$businessPartner->BusinessPartnerGroup}' AND Company='{$default->company->Oid}' AND ItemType='{$item->ItemType}' AND IsHide=0";
            $salesSegmentPerItemType = DB::select($query);

            if ($salesSegmentPerItem) { //MARKET SEGMENT
                $salesSegmentPerItem = $salesSegmentPerItem[0];
                $result = $this->subConvertRate($item, $default->cur->Oid,[
                    'SalesAdult' => calcPriceMethod($salesSegmentPerItem, $cost['SalesAdult']),
                    'SalesChild' => calcPriceMethod($salesSegmentPerItem, $cost['SalesChild']),
                    'SalesInfant' => calcPriceMethod($salesSegmentPerItem, $cost['SalesInfant']),
                    'SalesSenior' => calcPriceMethod($salesSegmentPerItem, $cost['SalesSenior']),
                ]);
                return $result;

            } elseif ($salesSegmentPerItemType) { // HARGA MARKET SEGMENT PER ITEM TYPE
                $salesSegmentPerItemType = $salesSegmentPerItemType[0];
                $result = $this->subConvertRate($item, $default->cur->Oid,[
                    'SalesAdult' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesAdult']),
                    'SalesChild' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesChild']),
                    'SalesInfant' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesInfant']),
                    'SalesSenior' => calcPriceMethod($salesSegmentPerItemType, $cost['SalesSenior']),
                ]);
                return $result;

            } elseif (!$item->IsUsingPriceMethod) { // USING CUSTOMIZE NOT GLOBAL
                $result = $this->subConvertRate($item, $default->cur->Oid,[
                    'SalesAdult' => $cost['SalesAdult'] + $item->SalesAdult,
                    'SalesChild' => $cost['SalesChild'] + $item->SalesChild,
                    'SalesInfant' => $cost['SalesInfant'] + $item->SalesInfant,
                    'SalesSenior' => $cost['SalesSenior'] + $item->SalesSenior,
                ]);
                return $result;
                
            } else { //USING GLOBAL PRICE (ONLY 1 RECORD)
                $data = getPriceMethodItemType($default, $item->ItemTypeObj->Code);
                return $this->subCalculatePriceFromMultiCompany($item, $data, $default->cur->Oid);
            }
        } else {
            if (count($companyItem) > 1) { //CUSTOM HARGA PER ITEM
                foreach ($companyItem as $row) if ($row->Item == $item->Oid) return [
                    'SalesAdult' => $row->SalesAdult,
                    'SalesChild' => $row->SalesChild,
                    'SalesInfant' => $row->SalesInfant,
                    'SalesSenior' => $row->SalesSenior,
                ];
            }
            $data = $this->subCalculatePriceFromMultiCompany($item, $companyItemContent, $default->cur->Oid);
            return $data;
        }
    }

    private function subConvertRate($item, $currency, $price){ //FINAL SELL PRICE CONVERT FROM BASE CUR TO USER CUR
        $fromCur = $item->CompanyObj->CurrencyObj;
        $targetCur = $currency;
        return [
            'SalesAdult' => $fromCur->convertRate($targetCur, $price['SalesAdult'] ?: 0),
            'SalesChild' => $fromCur->convertRate($targetCur, $price['SalesChild'] ?: 0),
            'SalesInfant' => $fromCur->convertRate($targetCur, $price['SalesInfant'] ?: 0),
            'SalesSenior' => $fromCur->convertRate($targetCur, $price['SalesSenior'] ?: 0),
        ];
    }

    private function subCalculateCostPrice($item) { // CALCULATE COST PRICE FROM PURCHASE CURRENCY TO COMPANY CURRENCY (BASE CUR)
        $fromCur = $item->ItemContent ? $item->ItemContentObj->PurchaseCurrencyObj : $item->PurchaseCurrencyObj;
        $targetCur = $item->CompanyObj->CurrencyObj;
        if (!$fromCur) $fromCur = Currency::findOrFail(company()->Currency);
        return [
            'SalesAdult' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseAdult ?: 0),
            'SalesChild' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseChild ?: 0),
            'SalesInfant' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseInfant ?: 0),
            'SalesSenior' => $fromCur->convertRate($targetCur->Oid,$item->PurchaseSenior ?: 0),
        ];
    }

    private function subCalculatePriceFromMultiCompany($item, $data, $currency) { // CALCULATE FOR ALL MULTI COMPANY AND GLOBAL PRICE
        $cost = $this->subCalculateCostPrice($item);
        foreach ($data as $row) {
            $row->PurchaseAdult = $cost['SalesAdult'];
            $row->PurchaseChild = $cost['SalesChild'];
            $row->PurchaseInfant = $cost['SalesInfant'];
            $row->PurchaseSenior = $cost['SalesSenior'];
            $row->AmountAdult = calcPriceMethod($row, $cost['SalesAdult'], false);
            $row->AmountChild = calcPriceMethod($row, $cost['SalesChild'], false);
            $row->AmountInfant = calcPriceMethod($row, $cost['SalesInfant'], false);
            $row->AmountSenior = calcPriceMethod($row, $cost['SalesSenior'], false);
            $row->SalesAdult = $cost['SalesAdult'] + $row->AmountAdult;
            $row->SalesChild = $cost['SalesChild'] + $row->AmountChild;
            $row->SalesInfant = $cost['SalesInfant'] + $row->AmountInfant;
            $row->SalesSenior = $cost['SalesSenior'] + $row->AmountSenior;
            $cost['SalesAdult'] = $row->SalesAdult;
            $cost['SalesChild'] = $row->SalesChild;
            $cost['SalesInfant'] = $row->SalesInfant;
            $cost['SalesSenior'] = $row->SalesSenior;
        }
        $result = $this->subConvertRate($item, $currency, $cost);
        $result = array_merge($result,[
            'Details' => $data
        ]);
        return $result;
    }

}