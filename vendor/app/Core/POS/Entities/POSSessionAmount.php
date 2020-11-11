<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSSessionAmount extends BaseModel {
    use BelongsToCompany;
    protected $table = 'possessionamount';

    public function UserObj() { return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid"); }
    public function POSSessionObj() { return $this->belongsTo("App\Core\POS\Entities\POSSession", "POSSession", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function PaymentMethodObj() { return $this->belongsTo("App\Core\Master\Entities\PaymentMethod", "PaymentMethod", "Oid"); }
    public function POSSessionAmountTypeObj() { return $this->belongsTo("App\Core\POS\Entities\POSSessionAmountType", "POSSessionAmountType", "Oid"); }

}