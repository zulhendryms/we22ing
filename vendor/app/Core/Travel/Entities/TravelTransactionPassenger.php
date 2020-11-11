<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransactionPassenger extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvtransactionpassenger';
    public function NationalityObj() { return $this->belongsTo('App\Core\Internal\Entities\Country', 'Nationality', 'Oid'); }
}