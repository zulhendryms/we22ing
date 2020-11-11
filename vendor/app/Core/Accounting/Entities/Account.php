<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Account extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'accaccount';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function AccountGroupObj() { return $this->belongsTo("App\Core\Accounting\Entities\AccountGroup", "AccountGroup", "Oid"); }
    public function AccountTypeObj() { return $this->belongsTo("App\Core\Internal\Entities\AccountType", "AccountType", "Oid"); }
    public function BankObj() { return $this->belongsTo('App\Core\Master\Entities\Bank', 'Bank', 'Oid'); }
    public function scopeCashBank() { 
        return $this->whereHas('AccountTypeObj', function ($query) {
            // $query->whereIn('Code', [ 'entry', 'ordered', 'verify' ]);
            $query->where('IsActive', '1');
        })->get();
    }
}