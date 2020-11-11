<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class ItemAllotment extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'mstitemallotment';

    public function ItemObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid');
    }
    public function TravelTransactionDetailObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelTransactionDetail', 'TravelTransactionDetail', 'Oid');
    }
    public function TravelTransactionObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelTransaction', 'TravelTransaction', 'Oid');
    }
}
