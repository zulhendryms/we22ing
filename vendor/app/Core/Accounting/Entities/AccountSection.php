<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class AccountSection extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'accaccountsection'; 

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function AccountTypeObj() { return $this->belongsTo("App\Core\Internal\Entities\AccountType", "AccountType", "Oid"); }
}