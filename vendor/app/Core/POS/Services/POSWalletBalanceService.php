<?php

namespace App\Core\POS\Services;

use Illuminate\Support\Facades\DB;
use App\Core\POS\Entities\PointOfSale;
use Illuminate\Validation\UnauthorizedException;
use App\Core\POS\Exceptions\BalanceNotEnoughException;
use App\Core\Internal\Entities\Status;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Security\Entities\User;

class POSWalletBalanceService 
{

    public function create(PointOfSale $pos, $amount = null)
    {
        if (!$amount) $amount = $pos->TotalAmount;
        $user = $pos->UserObj;
        if (is_null($user)) throw new UnauthorizedException("User not found");
        if (is_null($user->BusinessPartner)) {
            $customer = $user;
        } else {
            if ($user->BusinessPartnerObj->BusinessPartnerGroupObj->BusinessPartnerRoleObj->Code == 'Agent') {
                $customer = $user->BusinessPartnerObj;
            } else {
                $customer = $user;
            }
        }

        $currency = $pos->CurrencyObj;
        $company = $pos->CompanyObj;
        $companyCurrency = $company->CurrencyObj;

        $balance = 0;        

        $balance = $customer->getBalance($companyCurrency);
        $balance = $companyCurrency->convertRate($currency, $balance);
        if ($balance < $amount) throw new BalanceNotEnoughException("Balance is not enough");

        $amountBase = $currency->convertRate($companyCurrency, $amount);
        $customer->WalletBalances()->create([
            'PointOfSale' => $pos->Oid,
            'Type' => 'Trans-Deduction',
            'Company' => $customer->Company,
            'CreditAmount' => $amount,
            'CreditBase' => $amountBase,
            'Currency' => $pos->Currency,
            'Status' => Status::posted()->value('Oid'),
            'BusinessPartner' => $customer instanceof BusinessPartner ? $customer->Oid : null,
            'User' => $customer instanceof User ? $customer->Oid : null,
        ]);

        $pos->BalanceAmount = $amountBase;
        $pos->PaymentCurrency = $companyCurrency->Oid;
        $pos->save();
    }
}