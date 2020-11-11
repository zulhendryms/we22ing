<?php

namespace App\Core\Travel\Services;

use App\Core\POS\Services\POSService;
use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Item;
use App\Core\Master\Entities\Currency;
use App\Core\POS\Entities\PointOfSale;
use App\Core\Internal\Entities\PointOfSaleType;
use App\Core\Internal\Entities\Status;
use Carbon\Carbon;
use App\Core\POS\Exceptions\NoETicketException;
use App\Core\Travel\Exceptions\MinimumOrderException;
use App\Core\Travel\Exceptions\MaximumOrderException;

class TravelTransactionService 
{

    /** @var POSService $posService */
    protected $posService;
    /** @var TravelPassengerService $passengerService */
    protected $passengerService;
    /** @var TravelTransactionAllotmentService $allotmentService */
    protected $allotmentService;

    /**
     * @param POSService $posService
     * @return void
     */
    public function __construct(
        POSService $posService, 
        TravelPassengerService $passengerService,
        TravelTransactionAllotmentService $allotmentService
    )
    {
        $this->posService = $posService;
        $this->passengerService = $passengerService;
        $this->allotmentService = $allotmentService;
    }

    /**
     * Create a travel transaction
     * 
     * @param array $params
     */
    public function create($params)
    {
        $travelTransaction = null;
        DB::transaction(function () use (&$travelTransaction, $params) {
            $pos = $this->posService->create($this->createPOSParams($params));
            
            $travelTransaction = $pos->TravelTransactionObj()->create($this->createTravelTransactionParams($params));
            if (isset($params['TravelTransactionDetails'])) {
                $this->createDetail($pos, $params['TravelTransactionDetails']);
            }
        });
        return $travelTransaction;
    }

    protected function getParamDifferences()
    {
        return [
            'TransactionDate',
            'TransactionTime',
            'TransactionNote1',
            'TransactionNote2',
            'TransactionPassport',
            'POSItemService',
            'TravelTransactionDetails',
            'TravelType'
        ];
    }

    protected function createPOSParams($params)
    {
        $item = Item::findOrFail($params['POSItemService']);
        return array_merge(
            array_diff_key($params, array_flip($this->getParamDifferences())),
            [
                'ObjectType' => 78,
                // 'Supplier' => $item->PurchaseBusinessPartner,
                // 'APIType' => $item->APIType,
                'PointOfSaleType' => PointOfSaleType::where('Code', 'attraction')->value('Oid'),
            ]
        );
    }

    protected function createTravelTransactionParams($params)
    {
        $item = Item::findOrFail($params['POSItemService']);
        $params = array_intersect_key($params, array_flip($this->getParamDifferences()));
        unset($params['TravelTransactionDetails']);
        unset($params['POSItemService']);
        return $params;
    }

    public function calculateAmount(PointOfSale $pos)
    {
        $details = $pos->TravelTransactionDetails;
        $qtyRoom = 0; $qtyAdult = 0; $qtyChild = 0; $qtyInfant = 0;
        if (count($details) != 0) {
            $subtotal = 0; $qty = 0;
            foreach ($details as $detail) {
                $subtotal+= $detail->SalesTotal;
                $qty += $detail->Quantity;

                if ($detail->QtyDay * $detail->Qty > $qtyRoom) $qtyRoom = $detail->QtyDay * $detail->Qty;
                if ($detail->QtyAdult > $qtyAdult) $qtyAdult = $detail->QtyAdult;
                if ($detail->QtyChild > $qtyChild) $qtyChild = $detail->QtyChild;
                if ($detail->QtyInfant > $qtyInfant) $qtyInfant = $detail->QtyInfant;
            }
            $pos->Quantity = $qty;
            $pos->SubtotalAmount = $subtotal;
        }   

        $pos->TotalAmount = $pos->SubtotalAmount + $pos->ConvenienceAmount + $pos->AdditionalAmount  - $pos->DiscountAmount;
        $pos->TotalAmountDisplay = $pos->TotalAmount;
        
        $baseCur = $pos->CompanyObj->CurrencyObj;
        if ($pos->Currency == $baseCur->Oid) {
            $pos->SubtotalAmountBase = $pos->SubtotalAmount;
            $pos->ConvenienceAmountBase = $pos->ConvenienceAmount;
            $pos->AdditionalAmountBase = $pos->AdditionalAmount;
            $pos->DiscountAmountBase = $pos->DiscountAmount;
            $pos->TotalAmountBase = $pos->TotalAmount;
        } else {
            $pos->SubtotalAmountBase = $pos->CurrencyObj->convertRate($baseCur, $pos->SubtotalAmount, $pos->Date);
            $pos->ConvenienceAmountBase = $pos->CurrencyObj->convertRate($baseCur, $pos->ConvenienceAmount, $pos->Date);
            $pos->AdditionalAmountBase = $pos->CurrencyObj->convertRate($baseCur, $pos->AdditionalAmount, $pos->Date);
            $pos->DiscountAmountBase = $pos->CurrencyObj->convertRate($baseCur, $pos->DiscountAmount, $pos->Date);
            $pos->TotalAmountBase = $pos->CurrencyObj->convertRate($baseCur, $pos->TotalAmount, $pos->Date);        
        }
        
        $pos->save();

        //sementara tdk update qty dari detail ke parent
        // 'QtyAdult' => $qtyAdult,
        // 'QtyChild' => $qtyChild,
        // 'QtyInfant' => $qtyInfant,
        // 'QtyRoom' => $qtyRoom
        $pos->TravelTransactionObj()->update([
            'Amount' => $pos->TotalAmount,
            'AmountDisplay' => $pos->TotalAmountDisplay,
        ]);
    }

    public function createDetail($pos, $params)
    {
        $details = $params;
        if (!isset($params[0])) {
            $details = [ $params ];
        }
        $travelTransaction = $pos->TravelTransactionObj;
        $travelType = $travelTransaction->TravelTypeObj->Code;
        $currency = $pos->CurrencyObj;
        $rate = $currency->getRate();
        foreach ($details as $detail) {
            $allowMinusAllotment = $detail['AllowMinusAllotment'] ?? false;
            if (!isset($detail['Type'])) $detail['Type'] = 3;
            unset($detail['AllowMinusAllotment']);
            if (isset($detail['Item'])) {
                $item = Item::findOrFail($detail['Item']);
                $pos->DetailItems()->create([ 'ItemParent' => $item->ParentOid, 'Item' => $item->Oid ]);
                $detail = array_merge([
                    'ItemGroup' => $item->ItemGroup,
                    'BusinessPartner' => $item->PurchaseBusinessPartner,
                    'PurchaseCurrency' => $item->PurchaseCurrency,
                    'PurchaseRate' => $item->PurchaseCurrencyObj->getRate()->MidRate,
                    'APIType' => isset($item->ParentOid) ? $item->ParentObj->APIType : $item->APIType,
                    // 'PurchaseDate' => now()->toDateString(),
                ], $detail);

                if ($item->ItemGroupObj->ItemTypeObj->Code == 'Hotel') {
                    $hotel = $item->TravelItemHotelObj;
                    if (isset($detail['Qty'])) {
                        if (!empty($hotel->MinOrder) && $hotel->MinOrder > $detail['Qty']) throw new MinimumOrderException($hotel->MinOrder);
                        if (!empty($hotel->MaxOrder) && $hotel->MaxOrder < $detail['Qty']) throw new MaximumOrderException($hotel->MaxOrder);
                    }
                }

                if ($item->ItemGroupObj->ItemTypeObj->Code == 'Travel') {
                    $detail = array_merge([
                        'SalesAdult' => $item->getSalesAmountDisplayAdult($pos->CurrencyObj, $pos->UserObj, $travelType),
                        'SalesChild' => $item->getSalesAmountDisplayChild($pos->CurrencyObj, $pos->UserObj, $travelType),
                        'SalesInfant' => $item->getSalesAmountDisplayInfant($pos->CurrencyObj, $pos->UserObj, $travelType),
                        'PurchaseAdult' => $item->getPurchaseAmountAdult(),
                        'PurchaseChild' => $item->getPurchaseAmountChild(),
                        'PurchaseInfant' => $item->getPurchaseAmountInfant(),
                        'QtyDay' => 0,
                        'Qty' => 0
                    ], $detail);
                }

                if ($item->ItemGroupObj->ItemTypeObj->Code != 'Travel') {
                    $detail = array_merge([
                        'SalesWeekday' => $item->getSalesAmountDisplayWeekday($pos->CurrencyObj, $pos->UserObj, $travelType),
                        'SalesWeekend' => $item->getSalesAmountDisplayWeekend($pos->CurrencyObj, $pos->UserObj, $travelType),
                        'PurchaseWeekday' => $item->getPurchaseAmountWeekday(),
                        'PurchaseWeekend' => $item->getPurchaseAmountWeekend(),
                        'QtyAdult' => 0,
                        'QtyChild' => 0,
                        'QtyInfant' => 0,
                    ], $detail);
                }

                if (isset($detail['Type'])) {
                    // Include or Benefit or Non Item
                    if ($detail['Type'] ==  1 || $detail['Type'] == 2 || $detail['Type'] == 0) {
                        $detail['SalesAdult'] = 0;
                        $detail['SalesChild'] = 0;
                        $detail['SalesInfant'] = 0;
                        $detail['SalesWeekday'] = 0;
                        $detail['SalesWeekend'] = 0;
                    }
                    // Benefit or Non Item
                    if ($detail['Type'] == 2 || $detail['Type'] == 0) {
                        $detail['PurchaseCurrency'] = null;
                        $detail['PurchaseAdult'] = 0;
                        $detail['PurchaseChild'] = 0;
                        $detail['PurchaseInfant'] = 0;
                        $detail['PurchaseWeekday'] = 0;
                        $detail['PurchaseWeekend'] = 0;
                    }
                }
            } 
            
            if ($detail['Type'] == 0) {
                $detail['Status'] = Status::complete()->value('Oid');
            }
            
            // $detail['QtyWeekday'] = 0;
            // $detail['QtyWeekend'] = 0;
            // if (isset($detail['DateFrom']) && isset($detail['DateUntil'])) {
            //     $start = Carbon::parse($detail['DateFrom']);
            //     $end = Carbon::parse($detail['DateUntil']);
            //     while ($start->lt($end)) {
            //         if ($start->isWeekday()) $detail['QtyWeekday']++;
            //         if ($start->isWeekend()) $detail['QtyWeekend']++;
            //         $start->addDay(1);
            //     }
            // }
            if (!isset($detail['Date'])) $detail['Date'] = now();
            if (!isset($detail['DateFrom'])) $detail['DateFrom'] = now();
            if (!isset($detail['DateUntil'])) $detail['DateUntil'] = $detail['DateFrom'];

            $detail = array_merge([
                'Code' => $pos->Code.'-'.mt_rand(1000, 9999),
                'DateFrom' => $detail['DateFrom'],
                'DateUntil' => $detail['DateUntil'],
                'SalesCurrency' => $pos->Currency,
                'Status' => Status::entry()->value('Oid'),
                'Quantity' => ($detail['Qty'] * $detail['QtyDay']) + $detail['QtyAdult'] + $detail['QtyChild'] + $detail['QtyInfant'],
                'SalesRate' => $rate->MidRate,
                'SalesAmount' => 0,
            ], $detail);

            // if (isset($detail['Item'])) {
            //     $detail['SalesSubtotal'] = ($detail['SalesWeekday'] * $detail['QtyWeekday']) + 
            //     ($detail['SalesWeekend'] * $detail['QtyWeekend']) + 
            //     ($detail['SalesAdult'] * $detail['QtyAdult']) + 
            //     ($detail['SalesChild'] * $detail['QtyChild']) + 
            //     ($detail['SalesInfant'] * $detail['QtyInfant']);
            //     $detail['SalesTotal'] = $detail['SalesSubtotal'];
            //     $detail['SalesTotalBase'] = $rate->toSellPrice($detail['SalesTotal']);
            // }
            
            $passengers = [];
            if (isset($detail['Passengers'])) {
                $passengers = $detail['Passengers'];
                unset($detail['Passengers']);
            }
            $detail = $pos->TravelTransactionDetails()->create($detail);
            $this->calculateDetailDateQty($detail);
            $this->calculateDetailAmount($detail);
            if (!empty($passengers)) {
                foreach ($passengers as $passenger) $this->passengerService->create($detail, $passenger);
            }
            
            if (!is_null($detail->Item)) {
                $this->allotmentService->allowMinus($allowMinusAllotment)->assignTransactionAllotment($detail);
            }
        }
        
        $this->calculateAmount($pos);
    }

    public function calculateDetailDateQty(&$detail)
    {
        if ($detail->Type == 5) {
            $detail->Quantity = 1;
        } else if ($detail->Type == 0) {
            $detail->Quantity = 1;
        } else {
            $detail->QtyWeekday = 0;
            $detail->QtyWeekend = 0;
            $detail->QtyDay = 0;
            if (isset($detail->DateFrom) && isset($detail->DateUntil)) {
                $start = Carbon::parse($detail->DateFrom);
                $end = Carbon::parse($detail->DateUntil);
                while ($start->lt($end)) {
                    if ($start->isWeekday()) $detail->QtyWeekday++;
                    if ($start->isWeekend()) $detail->QtyWeekend++;
                    $start->addDay(1);
                    $detail->QtyDay++;
                }
            }
            $detail->Quantity = ($detail->Qty * $detail->QtyDay) + $detail->QtyAdult + $detail->QtyChild + $detail->QtyInfant;
        }
        $detail->save();
    }

    public function calculateDetailAmount(&$detail)
    {
        if (!is_null($detail->Item)) {
            // $currency = $detail->PointOfSaleObj->CurrencyObj;
            // $rate = $currency->getRate();
            $rate = $detail->SalesRate;
            if (empty($rate)) {
                $rate = $detail->PointOfSaleObj->CurrencyObj->getRate()->MidRate;
                $detail->SalesRate = $rate;
            }

            $cur = Currency::findOrFail($detail->SalesCurrency);

            $detail->SalesSubtotal = ($detail->SalesWeekday * ($detail->QtyWeekday * $detail->Qty)) + 
            ($detail->SalesWeekend * ($detail->QtyWeekend * $detail->Qty)) + 
            ($detail->SalesAdult * $detail->QtyAdult) + 
            ($detail->SalesChild * $detail->QtyChild) + 
            ($detail->SalesInfant * $detail->QtyInfant);
            $detail->SalesTotal = $detail->SalesSubtotal;
            $detail->SalesTotalBase = $cur->ToBaseAmount($detail->SalesTotal,$rate);

            $rate = $detail->PurchaseRate;
            if (empty($rate)) {
                $rate = $detail->ItemObj->PurchaseCurrencyObj->getRate()->MidRate;
                $detail->PurchaseRate = $rate;
            }

            $detail->PurchaseSubtotal = ($detail->PurchaseWeekday * ($detail->QtyWeekday * $detail->Qty)) + 
            ($detail->PurchaseWeekend * ($detail->QtyWeekend * $detail->Qty)) + 
            ($detail->PurchaseAdult * $detail->QtyAdult) + 
            ($detail->PurchaseChild * $detail->QtyChild) + 
            ($detail->PurchaseInfant * $detail->QtyInfant);
            $detail->PurchaseTotal = $detail->PurchaseSubtotal;
            $detail->PurchaseTotalBase = $cur->ToBaseAmount($detail->PurchaseTotal,$rate);

            $detail->save();
        }
    }

    public function deleteDetail($pos, $detail)
    {
        if (is_string($detail)) {
            $detail = $pos->TravelTransactionDetails()->find($detail);
        }
        $pos->DetailItems()->where('Item', $detail->Item)->delete(); // Remove detail items
        DB::delete("DELETE FROM trvtransactionpassenger 
        WHERE TravelTransactionDetail = ?", [ $detail->Oid ]); // Remove passengers
        $this->allotmentService->removeTransactionAllotment($detail); // Remove allotment
        $pos->TravelTransactionDetails()->where('Oid', $detail->Oid)->delete(); // Remove detail
        $this->calculateAmount($pos);
    }

    public function deleteDetails($pos)
    {
        foreach ($pos->TravelTransactionDetails as $detail) {
            $this->deleteDetail($detail);
        }
    }

    public function updateDetailQty($detail, $param)
    {
        $this->allotmentService->removeTransactionAllotment($detail);
        $item = $detail->ItemObj;
        if ($item->ItemGroupObj->ItemTypeObj->Code == 'Hotel') {
            $hotel = $item->TravelItemHotelObj;
            if (isset($param['Qty'])) {
                if (!empty($hotel->MinOrder) && $hotel->MinOrder > $param['Qty']) throw new MinimumOrderException($hotel->MinOrder);
                if (!empty($hotel->MaxOrder) && $hotel->MaxOrder < $param['Qty']) throw new MaximumOrderException($hotel->MaxOrder);
            }
        }
        $detail->update($param);
        $this->calculateDetailDateQty($detail);
        $this->calculateDetailAmount($detail);
        if (!is_null($detail->Item)) $this->allotmentService->allowMinus(true)->assignTransactionAllotment($detail);
        $this->calculateAmount($detail->PointOfSaleObj);
    }

    public function updateDetailDate($detail, $param)
    {
        $this->allotmentService->removeTransactionAllotment($detail);
        if (isset($param['DateFrom']) && isset($param['DateUntil'])) {
            $param['QtyDay'] = Carbon::parse($param['DateFrom'])->diffInDays(Carbon::parse($param['DateUntil']));
        }
        $detail->update($param);
        $this->calculateDetailDateQty($detail);
        $this->calculateDetailAmount($detail);
        if (!is_null($detail->Item)) $this->allotmentService->allowMinus(true)->assignTransactionAllotment($detail);
        $this->calculateAmount($detail->PointOfSaleObj);
    }

    public function setDetailToComplete($detail)
    {
        $item = $detail->ItemObj;
       if (!is_null($item)) throw_unless($this->checkDetailHasETicket($detail), new NoETicketException($detail));
        $detail->StatusObj()->associate(Status::complete()->first());
        $detail->save();

        event( new \App\Core\Travel\Events\TravelTransactionDetailCompleted($detail) );
    }

    
    public function setDetailToEntry($detail)
    {
        $detail->StatusObj()->associate(Status::entry()->first());
        $detail->save();
        $this->removeETickets($detail);
    }

    public function setDetailToCancel($detail)
    {
        $detail->StatusObj()->associate(Status::cancelled()->first());
        $detail->save();
        $this->allotmentService->removeTransactionAllotment($detail);
    }

    public function checkDetailHasETicket($detail)
    {
        if ($detail->APIType == 'manual_up') return true;
        if (!isset($detail->Item)) return true;
        if (!isset($detail->ItemObj->ParentOid)) return true;
        $pos = $detail->PointOfSaleObj;
        $item = $detail->ItemObj;
        if ($item->ParentObj->ETicketMergeType == 1) {
            return $pos->ETickets()->where('TravelTransactionDetail', $detail->Oid)->count() > 0;
        } else {
            return $pos->ETickets()->where('BusinessPartner', $item->ParentObj->PurchaseBusinessPartner)->count() > 0;
        }
    }

    private function removeETickets($detail)
    {
        $pos = $detail->PointOfSaleObj;
        $item = $detail->ItemObj;
        if ($item->ParentObj->ETicketMergeType == 1) {
            $pos->ETickets()->where('TravelTransactionDetail', $detail->Oid)->delete();
        } else {
            $pos->ETickets()->where('BusinessPartner', $item->ParentObj->PurchaseBusinessPartner)->delete();
        }
    }   
}
