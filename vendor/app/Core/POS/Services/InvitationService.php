<?php

namespace App\Core\POS\Services;

use App\Core\Security\Entities\User;
use App\Core\POS\Entities\Invitation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Core\POS\Exceptions\InvitorNotFoundException;
use App\Core\POS\Exceptions\InvitationCriteriaException;
use App\Core\POS\Entities\InvitationUser;
use Illuminate\Support\Facades\DB;
use App\Core\Internal\Entities\Status;

class InvitationService 
{
    /**
     * Find Invitor by invitation code
     * 
     * @param string $code
     * @return void
     * @throws InvitorNotFoundException
     */
    public function findInvitor($code)
    {
        $user = User::where('InvitationCode', $code)->first();
        throw_if(is_null($user), new InvitorNotFoundException('Invitation code not found'));
        return $user;
    }

    /**
     * Create user invitation
     * 
     * @param User $user
     * @param string $code
     * 
     * @return void
     */
    public function createUserInvitation(User $user, $code)
    {
        $invitor = $this->findInvitor($code);
        
        $user->InvitorObj()->associate($invitor);
        $user->save();

        $invitation = Invitation::active()->first();
        if (is_null($invitation)) return;

        $invitation->InvitationUsers()->create([
            'UserInvitor' => $invitor->Oid, 
            'UserInvitee' => $user->Oid,
        ]);
    }

    /**
     * Create invitation code for user
     * 
     * @param int $length
     * @param User $user
     * @return string $code
     */
    public function createInvitationCode($length = 6, User $user = null)
    {
        $code = strtolower(str_random($length));
        if (!is_null($user)) {
            $user->InvitationCode = $code;
            $user->save();
        }
        return $code;
    }

    /**
     * Check user criteria
     * 
     * @param User $user
     * @return boolean
     */
    public function checkUserCriteria(User $user)
    {
        return $user->checkInvitationCriteria($user->InvitationUserObj);
    }

    /**
     * Verify user criteria
     * 
     * @param User $user
     * @return void
     */
    public function verifyUserInvitation(User $user)
    {
        $invitationUser = $user->InvitationUserObj;
        if (is_null($invitationUser)) return;
        if (isset($invitationUser->DateVerified)) return;

        $invitation = $user->InvitationObj;
        if (is_null($invitation)) return;

        if (!$user->checkInvitationCriteria($invitation)) return;

        $invitationUser = $user->InvitationUserObj;
        $invitationUser->DateVerified = now()->addHours(company_timezone())->toDateTimeString();
        $invitationUser->save();
    }

    /**
     * Process user invitation reward
     * 
     * @param User $user
     * @return void
     */
    public function processInvitation(InvitationUser $invitationUser)
    {
        $invitor = $invitationUser->InvitorObj;
        $invitee = $invitationUser->InviteeObj;
        $invitation = $invitationUser->InvitationObj;

        if (isset($invitationUser->DateProcessed)) return;

        //Requested by Jun 20180917 to skip Invitor's criteria
        //if (!$invitor->checkInvitationCriteria($invitation)) throw new InvitationCriteriaException("Invitor criteria not fulfilled");

        if (!$invitee->checkInvitationCriteria($invitation)) {
            throw new InvitationCriteriaException("Invitee criteria not fulfilled");
        }

        DB::transaction(function () use ($invitationUser, $invitee, $invitor, $invitation) {
            $invitationUser->DateProcessed = now()->addHour(company_timezone())->toDateTimeString();
            $invitationUser->save();

            $statusPosted = Status::entry()->value('Oid');

            $invitor->WalletBalance()->create([
                'Type' => 'Invitor',
                'Company' => $invitor->Company,
                'DebetAmount' => $invitation->AmountInvitor,
                'DebetBase' => $invitation->AmountBaseInvitor,
                'Currency' => $invitation->CurrencyInvitor,
                'Status' => $statusPosted
            ]);

            $invitee->WalletBalance()->create([
                'Type' => 'Invitee',
                'Company' => $invitee->Company,
                'DebetAmount' => $invitation->AmountInvitee,
                'DebetBase' => $invitation->AmountBaseInvitee,
                'Currency' => $invitation->CurrencyInvitee,
                'Status' => $statusPosted
            ]);
        });
    }
}