<?php

namespace App\Core\POS\Controllers\Api;

use Illuminate\Http\Request;
use App\Laravel\Http\Controllers\Controller;
use App\Core\POS\Entities\InvitationUser;
use App\Core\POS\Services\InvitationService;
use App\Core\Security\Entities\User;

class InvitationController extends Controller 
{
    protected $invitationService;
    /**
     * @param InvitationService $invitationService
     */
    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;   
    }

    public function verify(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $this->invitationService->verifyUserInvitation($user);
    }

    public function process(Request $request, $id)
    {
        $invitation = InvitationUser::findOrFail($id);
        $this->invitationService->processInvitation($invitation);
    }
}