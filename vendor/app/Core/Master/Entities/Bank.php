<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Bank extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'mstbank';

    public function CountryObj()
    {
        return $this->belongsTo('App\Core\Internal\Entities\Country', 'Country', 'Oid');
    }
    public function CurrencyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid');
    }
    public function Users()
    {
        return $this->hasMany('App\Core\Master\Entities\BankUser', 'Bank', 'Oid');
    }
}
