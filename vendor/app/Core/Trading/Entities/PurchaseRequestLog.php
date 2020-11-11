<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class PurchaseRequestLog extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trdpurchaserequestlog';

    public function PurchaseRequestObj()
    {
        return $this->belongsTo('App\Core\Trading\Entities\PurchaseRequest', 'PurchaseRequest', 'Oid');
    }
    public function NextUserObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'NextUser', 'Oid');
    }
    public function UserObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');
    }

    public function PurchaseRequestVersionObj()
    {
        return $this->belongsTo('App\Core\Trading\Entities\PurchaseRequestVersion', 'PurchaseRequestVersion', 'Oid');
    }
}
