<?php

namespace App\Core\Travel\Controllers\Api;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\PointOfSale;
use App\Core\Master\Entities\Item;
use App\Core\Travel\Services\TravelTransactionService;
use App\Core\Internal\Entities\Status;

class CreateFromPackageController extends Controller
{

    /** @var TravelTransactionService $travelTransactionService */
    protected $travelTransactionService;

    public function __construct(TravelTransactionService $travelTransactionService)
    {
        $this->travelTransactionService = $travelTransactionService;
    }

    /**
     * @param Request $request
     * @param string $id
     */
    public function store(Request $request, $id)
    {
        DB::transaction(function () use ($request, $id) {
            $pos = PointOfSale::find($id);
            $user = $request->user();
            $level = $user->getSalesPriceLevel();
            $travelTransaction = $pos->TravelTransactionObj;
            if (is_null($travelTransaction)) return;
            $package = $travelTransaction->TravelPackageObj;
            if (is_null($package)) return;
            $params = [];

            // INSERT PARENT DARI WILLIAM
            $price = $package->PriceAges()->first();
            $priceSalesCurrency = $price->SalesCurrencyObj;
            $param = [
                'Type' => 5,
                'APIType' => 'manual_upload',
                'Seq' => '99',
                'Date' => $pos->Date,
                'Name' => $package->Name,
                'Qty' => $travelTransaction->QtyRoom,
                'QtyDay' => 0,
                'QtyAdult' => $travelTransaction->QtyAdult,
                'QtyChild' => $travelTransaction->QtyChild,
                'QtyInfant' => $travelTransaction->QtyInfant,
                'DateFrom' => $travelTransaction->DateFrom,
                'DateUntil' => $travelTransaction->DateUntil,
                'Item' => $package->Oid,
                'Quantity' => 1,
                // 'BusinessPartner' => $detail->BusinessPartner,
                'ItemGroup' => $package->ItemGroup,
                'SalesAdult' => $priceSalesCurrency->convertRate($pos->CurrencyObj, $price->{'SellGITAdult'.$level}),
                'SalesChild' => $priceSalesCurrency->convertRate($pos->CurrencyObj, $price->{'SellGITChild'.$level}),
                'SalesInfant' => $priceSalesCurrency->convertRate($pos->CurrencyObj, $price->{'SellGITInfant'.$level}),
                'SalesCurrency' => $price->SalesCurrency,
                'PurchaseCurrency' => $price->PurchaseCurrency,
                'Status' => Status::complete()->value('Oid')
            ];
            $param['AllowMinusAllotment'] = true;
            $params[] = $param;
            // INSERT PARENT DARI WILLIAM

            foreach ($package->Details as $detail) {
                $day = $detail->Day;
                // if (empty($day)) $day = 1;
                $dateFrom = Carbon::parse($travelTransaction->DateFrom)->addDays($day);
                if (!empty($detail->Quantity)) 
                    $dateUntil = Carbon::parse($dateFrom)->addDays($detail->Quantity);
                else 
                    $dateUntil = $dateFrom;
                $apiType = '';
                if (isset($detail->Item)) {
                    logger($detail->Item);
                    if ($detail->Type == 1 || $detail->Type == 2 || $detail->Type == 3) {
                        $item = Item::find($detail->Item);
                        logger($item->APIType);
                        $apiType = isset($item->ParentOid) ? $item->ParentObj->APIType : $item->APIType;
                        if ($apiType == 'auto') $apiType = 'manual_gen';
                    } elseif ($detail->Type == 5) {
                        $apiType = 'auto';
                    } else {
                        $apiType = '';
                    }
                }
                
                $param = [
                    'Type' => $detail->Type,
                    'APIType' => $apiType,
                    'Date' => $dateFrom,
                    'Name' => $detail->Name,
                    'Qty' => $travelTransaction->QtyRoom,
                    'QtyDay' => $detail->Quantity,
                    'QtyAdult' => $travelTransaction->QtyAdult,
                    'QtyChild' => $travelTransaction->QtyChild,
                    'QtyInfant' => $travelTransaction->QtyInfant,
                    'DateFrom' => $dateFrom,
                    'DateUntil' => $dateUntil,
                    'Item' => $detail->Item,
                    'BusinessPartner' => $detail->BusinessPartner,
                    'ItemGroup' => $detail->ItemGroup,
                ];

                if (!is_null($detail->ItemGroup)) {
                    if ($detail->ItemGroupObj->ItemTypeObj->Code != 'Travel') {
                        $param ['QtyAdult'] = 0;
                        $param ['QtyChild'] = 0;
                        $param ['QtyInfant'] = 0;
                    } else {
                        $param ['Qty'] = 0;
                        $param ['QtyDay'] = 0;
                    }
                }

                if (isset($detail->PriceAge) && $detail->ItemGroupObj->ItemTypeObj->Code == 'Travel') {
                    $price = $detail->PriceAgeObj;
                    $param = array_merge($param, [
                        // 'SalesAdult' => $detail->PriceAgeObj->{'SellFITAdult'.$level},                        
                        // 'SalesChild' => $detail->PriceAgeObj->{'SellFITChild'.$level},
                           // 'SalesInfant' => $detail->PriceAgeObj->{'SellFITInfant'.$level},
                        'SalesAdult' => $pos->CurrencyObj->convertRate($price->SalesCurrency, $price->{'SellGITAdult'.$level}, $pos->Date),
                        'SalesChild' => $pos->CurrencyObj->convertRate($price->SalesCurrency, $price->{'SellGITChild'.$level}, $pos->Date),
                        'SalesInfant' => $pos->CurrencyObj->convertRate($price->SalesCurrency, $price->{'SellGITInfant'.$level}, $pos->Date),
                        'PurchaseAdult' => $detail->PriceAgeObj->PurchaseAdult,
                        'PurchaseChild' => $detail->PriceAgeObj->PurchaseChild,
                        'PurchaseInfant' => $detail->PriceAgeObj->PurchaseInfant,
                        // 'SalesCurrency' => $detail->PriceAgeObj->SalesCurrency,
                        'SalesCurrency' => $pos->Currency,
                        'PurchaseCurrency' => $detail->PriceAgeObj->PurchaseCurrency,      
                        'PriceAge' => $detail->PriceAge                  
                    ]);
                }

                logger($detail->PriceDay);
                if (isset($detail->PriceDay) && $detail->ItemGroupObj->ItemTypeObj->Code != 'Travel') {
                    $price = $detail->PriceDayObj;
                    $param = array_merge($param, [
                        // 'SalesWeekday' => $detail->PriceDayObj->{'SellFITWeekday'.$level},
                        // 'SalesWeekend' => $detail->PriceDayObj->{'SellFITWeekend'.$level},
                        'SalesWeekday' => $pos->CurrencyObj->convertRate($price->SalesCurrency, $price->{'SellGITWeekday'.$level}, $pos->Date),
                        'SalesWeekend' => $pos->CurrencyObj->convertRate($price->SalesCurrency, $price->{'SellGITWeekend'.$level}, $pos->Date),
                        'PurchaseWeekday' => $detail->PriceDayObj->PurchaseWeekday,
                        'PurchaseWeekend' => $detail->PriceDayObj->PurchaseWeekend,
                        // 'SalesCurrency' => $detail->PriceDayObj->SalesCurrency,
                        'SalesCurrency' => $pos->Currency,
                        'PurchaseCurrency' => $detail->PriceDayObj->PurchaseCurrency,
                        'PriceDay' => $detail->PriceDay                  
                    ]);
                }
                $param['AllowMinusAllotment'] = true;
                $params[] = $param;
            }
            $this->travelTransactionService->createDetail($pos, $params);
        });
    }
}
