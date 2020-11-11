<?php

namespace App\Core\POS\Services;

use App\Core\POS\Entities\PointOfSale;
use Illuminate\Support\Facades\DB;
use App\Core\Master\Entities\Currency;
use App\Core\Master\Entities\Company;
use App\Core\Security\Entities\User;
use App\Core\Internal\Entities\PointOfSaleType;
use App\Core\Ferry\Entities\FerryTransaction;
use App\Core\Master\Entities\Item;
use Illuminate\Support\Facades\Auth;
use App\Core\Internal\Entities\Status;
use App\Core\Master\Entities\PaymentMethod;
use App\Core\POS\Events\POSPaymentMethodSelected;
use App\Core\POS\Events\POSCreated;
use App\Core\POS\Events\POSHidden;
use App\Core\Master\Entities\BusinessPartner;

class POSService 
{
    /** @var POSStatusService $statusService */
    protected $statusService;
    /** @var POSWalletBalanceService $posWalletBalanceService */
    protected $posWalletBalanceService;
    /** @var POSPassengerService $posPassengerService */
    protected $posPassengerService;

    /**
     * @param POSStatusService $logService
     * @return void
     */
    public function __construct(
        POSStatusService $statusService,
        POSWalletBalanceService $posWalletBalanceService,
        POSPassengerService $posPassengerService
    )
    {
        $this->statusService = $statusService;
        $this->posWalletBalanceService = $posWalletBalanceService;
        $this->posPassengerService = $posPassengerService;
    }

    /**
     * Create a Point of Sale
     * 
     * @param array $param
     */
    public function create($param)
    {
        $pos;
        DB::transaction(function () use (&$pos, $param) {
            // User verification
            $user = Auth::user();
            if (isset($param['User'])) $user = User::find($param['User']);
            
            // Currency verification
            if (isset($param['Currency'])) $currency = Currency::find($param['Currency']);
            if (!isset($currency)) $currency = company()->CurrencyObj;
            $rate = $currency->getRate();

            // Convinience fee setup
            if ($currency->Code == 'IDR')
                $convenienceAmount = mt_rand(100,500);
            else
                $convenienceAmount = mt_rand(0.05,0.01);

            // // OrderSummaryDescription
            // switch(PointOfSaleType::where('Oid', $this->PointOfSaleType)->value('Code')) {
            //     case "deal":
            //     break;
            // }
            
            $params = array_merge([
                'Code' => date('mdHis').mt_rand(0, 9),
                'Date' => now()->toDateTimeString(),
                'Status' => Status::entry()->value('Oid'),
                'Customer' => isset($user) ? $user->BusinessPartner : BusinessPartner::cash()->value('Oid'),
                'Currency' =>  $currency->Oid,
                'CurrencyRate' => $rate->Oid,
                'RateAmount' => $rate->MidRate,
                'ConvenienceAmount' => $convenienceAmount,
                'ConvenienceAmountBase' => company()->CurrencyObj->convertRate($currency->Oid, $convenienceAmount, now()),
                'IsHide' => false,
                'IsGuest' => is_null($user),
                'User' => isset($user) ? $user->Oid : null,
                'ObjectType' => $this->getObjectType($param['PointOfSaleType'] ?? null),
                'ContactEmail' => isset($user) ? $user->UserName : null,
                'ContactPhone' => isset($user) ? $user->PhoneCode.$user->PhoneNo : null,
                'ContactName' => isset($iser) ? $user->Name ?? $user->UserName : null,
            ], array_diff_key($param, array_flip(['Details', 'PaymentMethod'])));
            
            $pos = PointOfSale::create($params);
            if (isset($param['Details'])) $this->createDetail($pos, $param['Details']);
            if (isset($param['PaymentMethod'])) $this->setPaymentMethod($pos, $param['PaymentMethod']);
            event(new POSCreated($pos));
        });
        return $pos;
    }

    public function upload($param)
    {
        $pos;
        DB::transaction(function () use (&$pos, $param) {
            // User verification
            if (isset($param['User'])) $user = User::find($param['User']);
            
            $params = array_merge([
                'IsHide' => false,
                'IsGuest' => is_null($user),
            ], array_diff_key($param, array_flip(['Details', 'PaymentMethod'])));
            
            $pos = PointOfSale::create($params);
            if (isset($param['Details'])) $this->uploadDetail($pos, $param['Details']);
            if (isset($param['PaymentMethod'])) {
                $this->setPaymentMethod($pos, $param['PaymentMethod']);
                $this->statusService->setPaid($pos);
            }
            event(new POSCreated($pos));
        });
        return $pos;
    }
    

    /**
     * Calculate pos amount
     * 
     * @param PointOfSale $pos
     * @return void
     */
    public function calculateAmount(PointOfSale &$pos)
    {
        $details = $pos->Details;
        if (count($details) != 0) {
            $subtotal = 0; $qty = 0;
            foreach ($details as $detail) {
                $detailtotal = $detail->Amount * $detail->Quantity;
                $detailtotal = $detailtotal - $detail->DiscountAmount - $detail->DiscountPercentageAmount;
                $subtotal+= $detailtotal;
                $qty += $detail->Quantity;
            }
            $pos->Quantity = $qty;
            $pos->SubtotalAmount = $subtotal;
        }
        $pos->TotalAmount = $pos->SubtotalAmount + $pos->ConvenienceAmount + $pos->AdditionalAmount - $pos->DiscountAmount - $pos->DiscountPercentageAmount;
        //$pos->TotalAmountDisplay = $pos->CurrencyObj->round($pos->TotalAmount / $pos->RateAmount);
        $pos->TotalAmountDisplay = $pos->TotalAmount;

        $baseCur = $pos->CompanyObj->CurrencyObj;
        if ($pos->Currency == $baseCur->Oid) {
            $pos->SubtotalAmountBase = $pos->SubtotalAmount;
            $pos->ConvenienceAmountBase = $pos->ConvenienceAmount;
            $pos->AdditionalAmountBase = $pos->AdditionalAmount;
            $pos->DiscountAmountBase = $pos->DiscountAmount;
            $pos->TotalAmountBase = $pos->TotalAmount;
        } else {
            $pos->SubtotalAmountBase = $baseCur->convertRate($pos->Currency, $pos->SubtotalAmount, $pos->Date);
            $pos->ConvenienceAmountBase = $baseCur->convertRate($pos->Currency, $pos->ConvenienceAmount, $pos->Date);
            $pos->AdditionalAmountBase = $baseCur->convertRate($pos->Currency, $pos->AdditionalAmount, $pos->Date);
            $pos->DiscountAmountBase = $baseCur->convertRate($pos->Currency, $pos->DiscountAmount, $pos->Date);
            $pos->TotalAmountBase = $baseCur->convertRate($pos->Currency, $pos->TotalAmount, $pos->Date);        
        }
        $pos->save();
    }

    /**
     * Set pos payment method
     * 
     * @param PaymentMethod|string $paymentMethod
     * @return void
     */
    public function setPaymentMethod(&$pos, $paymentMethod)
    {
        if (is_string($paymentMethod)) {
            $paymentMethod = PaymentMethod::findOrFail($paymentMethod);
        }
        $pos->PaymentMethodObj()->associate($paymentMethod);

        $vendor = $pos->SupplierObj;

        if (isset($vendor) && $vendor->IsFeeAdmission) {
            if (!empty($paymentMethod->FeeAmount)) {
                $pos->AdmissionAmount = $paymentMethod->FeeAmount;
            }
            if (!empty($paymentMethod->FeePercentage)) {
                $pos->AdmissionAmount = round(($pos->SubtotalAmount * $paymentMethod->FeePercentage) / 100, 0);
            }
            $this->calculateAmount($pos);
        }

        $this->statusService->setOrdered($pos);
        event(new POSPaymentMethodSelected($pos));

        if ($paymentMethod->Code == 'balance') {
            $this->posWalletBalanceService->create($pos);
            // if (empty($pos->PaymentCurrency)) $pos->PaymentCurrency = $pos->CompanyObj->Currency;
            $this->statusService->setPaid($pos);
        }
    }

    /**
     * Set pos expiry
     * @param PointOfSale $pos
     * @param int $hour
     * @return void
     */
    public function setExpiry(PointOfSale $pos, $hour = 1)
    {
        $pos->DateExpiry = now()->addHour($hour)->toDateTimeString();
        $pos->save();
    }

    
    /**
     * @param PointOfSale $pos
     */
    public function hide(PointOfSale $pos)
    {
        $pos->IsHide = true;
        $pos->save();
        event(new POSHidden($pos));
    }

    public function uploadDetail($pos, $params)
    {
        $details = $params;
        if (!isset($params[0])) {
            $details = [ $params ];
        }

        $currency = $pos->CurrencyObj;
        
        foreach ($details as $detail) {
            $user = $pos->UserObj;
            $item = Item::with('SalesCurrencyObj')->findOrFail($detail['Item']);
            $pos->DetailItems()->create([ 'ItemParent' => $item->ParentOid, 'Item' => $item->Oid ]);

            if (!isset($detail['ItemUnit'])) $detail['ItemUnit'] = $item->ItemUnit;
            $detail = $pos->Details()->create($detail);
        }
    }

     /**
     * Create detail
     * 
     * @param PointOfSale $pos
     * @param array $detail
     * @return void
     */
    public function createDetail($pos, $params)
    {
        $details = $params;
        if (!isset($params[0])) {
            $details = [ $params ];
        }

        $currency = $pos->CurrencyObj;
        
        foreach ($details as $detail) {
            // $rate = $currency->getRate();
            $user = $pos->UserObj;
            $item = Item::with('SalesCurrencyObj')->findOrFail($detail['Item']);

            $pos->DetailItems()->create([ 'ItemParent' => $item->ParentOid, 'Item' => $item->Oid ]);

            if (!isset($detail['ItemUnit'])) $detail['ItemUnit'] = $item->ItemUnit;
            if (!isset($detail['Amount'])) {
                $detail['Amount'] = $item->getSalesAmountDisplay($currency, $pos->UserObj);
                $detail['AmountBase'] = $item->getSalesAmountBase($pos->UserObj);
                $detail['AmountDisplay'] = $item->getSalesAmountDisplay($currency, $pos->UserObj);
            }

            $passengers = [];
            if (isset($detail['Passengers'])) {
                $passengers = $detail['Passengers'];
                unset($detail['Passengers']);
            }
            $detail = $pos->Details()->create($detail);
            if (!empty($passengers)) {
                foreach ($passengers as $passenger) $this->posPassengerService->create($detail, $passenger);
            }
        }
        
        $this->calculateAmount($pos);
    }

    /**
     * Get pointofsale object type
     * 
     * @param string $type
     */
    protected function getObjectType($type = null)
    {
        $objectType = null;
        $xpObjectType = PointOfSale::getXPObjectType();
        if ($xpObjectType != null) {
            $objectType = $xpObjectType->OID;
        }
        if (isset($type)) {
            $type = PointOfSaleType::find($type);
            if ($type != null && ($type->IsFerry || $type->IsAttraction)) {
                $xpObjectType = FerryTransaction::getXPObjectType();
                if ($xpObjectType != null) $objectType = $xpObjectType->OID;
            }
        }
        return $objectType;
    }
}