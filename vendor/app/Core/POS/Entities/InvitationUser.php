<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class InvitationUser extends BaseModel 
{
    protected $table = 'posinvitationuser';

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->DateCreated = now()->addHours(company_timezone())->toDateTimeString();
            $model->DateCreatedUTC = now()->toDateTimeString();
        });
        self::updating(function ($model) {
            if (isset($model->DateVerified)) $model->DateVerifiedUTC = \Carbon\Carbon::parse($model->DateVerified)->subHours(company_timezone())->toDateTimeString();
            if (isset($model->DateProcessed)) $model->DateProcessedUTC = \Carbon\Carbon::parse($model->DateProcessed)->subHours(company_timezone())->toDateTimeString();
        });
    }

    public function InvitorObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserInvitor', 'Oid'); }
    public function InviteeObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserInvitee', 'Oid'); }
    public function InvitationObj() { return $this->belongsTo('App\Core\POS\Entities\Invitation', 'POSInvitation', 'Oid'); }
}