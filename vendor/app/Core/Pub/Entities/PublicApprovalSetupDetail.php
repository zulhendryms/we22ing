<?php

namespace App\Core\Pub\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PublicApprovalSetupDetail extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'pubapprovalsetupdetail';
            
public function StatusObj() { return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid'); }
public function UserApproval1Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserApproval1', 'Oid'); }
public function UserApproval2Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserApproval2', 'Oid'); }
public function UserApproval3Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserApproval3', 'Oid'); }
public function UserNotification1Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserNotification1', 'Oid'); }
public function UserNotification2Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserNotification2', 'Oid'); }
public function UserNotification3Obj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserNotification3', 'Oid'); }

        }