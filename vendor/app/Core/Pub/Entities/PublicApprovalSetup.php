<?php

namespace App\Core\Pub\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PublicApprovalSetup extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'pubapprovalsetup';



    public function Details()
    {
        return $this->hasMany('App\Core\Pub\Entities\PublicApprovalSetupDetail', 'PublicApprovalSetup', 'Oid');
    }
}
