<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class AccountGroup extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'accaccountgroup'; //test

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function AccountSectionObj() { return $this->belongsTo("App\Core\Accounting\Entities\AccountSection", "AccountSection", "Oid"); }
}