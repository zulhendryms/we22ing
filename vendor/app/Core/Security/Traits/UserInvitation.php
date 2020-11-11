<?php
namespace App\Core\Security\Traits;

use Illuminate\Support\Facades\DB;
use App\Core\POS\Entities\Invitation;

trait UserInvitation 
{

    public function generateInvitationCode($length = 6)
    {
        return strtolower(str_random($length));
    }

    public function InvitorObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', "InvitorUser", "Oid");
    }

    public function Invitees()
    {
        return $this->hasMany('App\Core\Security\Entities\User', "InvitorUser", "Oid");
    }

    public function getInvitationObjAttribute()
    {
        return $this->belongsToMany('App\Core\POS\Entities\Invitation', 'posinvitationuser', 'UserInvitee', 'POSInvitation')->first();
    }

    public function InvitationUserObj()
    {
        return $this->hasOne('App\Core\POS\Entities\InvitationUser', 'UserInvitee', 'Oid');
    }

    public function Invitations()
    {
        return $this->belongsToMany('App\Core\POS\Entities\Invitation', 'posinvitationuser', 'UserInvitor', 'POSInvitation');
    }

    public function checkInvitationCriteria(Invitation $invitation)
    {
        $emailVerified = true; $phoneVerified = true; $gaVerified = true; $dataVerified = true;
        if ($invitation->CriteriaVerifiedEmail) $emailVerified = $this->IsEmailVerified;
        if ($invitation->CriteriaVerifiedPhone) $phoneVerified = $this->IsPhoneVerified;
        if ($invitation->CriteriaVerifiedGA) $gaVerified = $this->IsGAVerified;
        if ($invitation->CriteriaVerifiedData) $dataVerified = $this->IsDateVerified;

        return $emailVerified && $phoneVerified && $gaVerified && $dataVerified;
    }

    public static function bootUserInvitation() 
    {
        static::creating(function ($model) {
            if (!isset($model->InvitationCode)) $model->InvitationCode = $model->generateInvitationCode();
        });
    }
}