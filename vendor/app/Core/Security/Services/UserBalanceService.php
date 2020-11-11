<?php

namespace App\Core\Security\Services;

use App\Core\Security\Entities\User;
use Illuminate\Validation\UnauthorizedException;
use App\Core\Internal\Entities\Status;

class UserBalanceService 
{
    public function create(User $user, $amount)
    {
        if (is_null($user->BusinessPartner)) throw new UnauthorizedException("Business partner not found");

        $customer = $user;
        if ($user->BusinessPartnerObj->BusinessPartnerGroupObj->BusinessPartnerRoleObj->Code == 'Agent') {
            $customer = $user->BusinessPartnerObj;
        }

        return $customer->WalletBalances()->create([
            'Type' => 'Deposit',
            'Company' => $customer->Company,
            'DebetAmount' => $amount,
            'DebetBase' => $amount,
            'Currency' => $user->CompanyObj->Currency,
            'Status' => Status::entry()->value('Oid'),
            'BusinessPartner' => $customer instanceof BusinessPartner ? $customer->Oid : null,
            'User' => $customer instanceof User ? $customer->Oid : null,
        ]);
    }
}