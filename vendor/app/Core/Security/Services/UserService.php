<?php

namespace App\Core\Security\Services;

use App\Core\Security\Entities\User;
use App\Core\Master\Entities\BusinessPartner;
use App\Core\Master\Entities\Company;
use App\Core\POS\Services\InvitationService;
use App\Core\Security\Events\UserCreated;
use Illuminate\Support\Facades\DB;
use App\Core\Security\Exceptions\UserExistsException;
use Illuminate\Support\Facades\Mail;
use App\Core\Security\Mails\ResetCode;
use App\Core\Security\Exceptions\InvalidResetCodeException;
use App\Core\Base\Exceptions\UserFriendlyException;

class UserService 
{
    /** @var InvitationService $invitationService */
    private $invitationService;
    /**
     * Create service instance
     * 
     * @param InvitationService $invitationService
     * @return void
     */
    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    /**
     * Create user
     * 
     * @param array $param
     * @param string $invitationCode
     * @return User
     */
    public function create($param, $invitationCode = null)
    {
        DB::transaction(function () use ($param, $invitationCode) {

            $check = User::where('UserName', $param['UserName'])->count();
            if ($check != 0) throw new UserExistsException("User already exists");

            if (!isset($param['Company'])) $param['Company'] = config('app.company_id');
            $company = Company::find($param['Company']);
            if (!isset($param['Lang'])) $param['Lang'] = $company->Lang;

            if (!isset($param['Currency'])) $param['Currency'] = $company->Currency;
            $param['IsActive'] = true;
            $user = User::create($param);
            if (isset($invitationCode)) {
                $this->invitationService->createUserInvitation($user, $invitationCode);
            }
            event(new UserCreated($user));
            return $user;
        });
    }

    /**
     * Create customer user
     * 
     * @param array $param
     * @param string $invitationCode
     * @return User
     */
    public function createCustomer($param, $invitationCode = null)
    {
        if (!isset($param['Company'])) $param['Company'] = config('app.company_id');
        $company = Company::find($param['Company']);
        $param['BusinessPartner'] = $company->CustomerCash;
        $businessPartner = BusinessPartner::find($param['BusinessPartner']);
        if (!isset($param['Currency']) && !is_null($businessPartner)) $param['Currency'] = $businessPartner->SalesCurrency;

        return $this->create($param, $invitationCode);
    }

    public function sendResetCodeEmail($user)
    {
        $code = $this->generateResetCode();
        $user->ResetCode = $code;
        $user->save();
        Mail::to($user->UserName)->queue(new ResetCode($user));
    }

    public function resetPassword($resetCode, $newPassword, $oldPassword = null)
    {
        $user = User::where('ResetCode', $resetCode)->first();
        throw_if(is_null($user), new InvalidResetCodeException('Reset code is invalid'));
        if (!empty($oldPassword)) throw_unless($user->validatePassword($oldPassword), new UserFriendlyException('Failed to reset password'));
        $user->Password = $newPassword;
        $user->save();
    }

    protected function generateResetCode()
    {
        return str_random(config('core.security.reset_password.code.length'));
    }
}