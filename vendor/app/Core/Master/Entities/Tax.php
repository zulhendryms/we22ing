<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class Tax extends BaseModel {
    use Activable, BelongsToCompany;
    protected $table = 'msttax';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function PurchaseAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "PurchaseAccount", "Oid"); }
    public function SalesAccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "SalesAccount", "Oid"); }
}