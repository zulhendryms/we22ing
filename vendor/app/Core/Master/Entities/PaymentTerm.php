<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Master\Traits\PaymentMethodAvailable as Available;

class PaymentTerm extends BaseModel {
    use BelongsToCompany, Available;
    protected $table = 'mstpaymentterm';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }
}