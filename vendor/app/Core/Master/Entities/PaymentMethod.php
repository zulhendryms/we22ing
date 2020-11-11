<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Master\Traits\PaymentMethodAvailable as Available;

class PaymentMethod extends BaseModel {
    use BelongsToCompany, Available;
    protected $table = 'mstpaymentmethod';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
    public function AccountObj() { return $this->belongsTo("App\Core\Accounting\Entities\Account", "Account", "Oid"); }
}