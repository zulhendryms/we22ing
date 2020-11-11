<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Email extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'mstemail';                
    
public function CashBankObj() { return $this->belongsTo('App\Core\Accounting\Entities\CashBank', 'CashBank', 'Oid'); }
public function PointOfSaleObj() { return $this->belongsTo('App\Core\POS\Entities\PointOfSale', 'PointOfSale', 'Oid'); }

    

}
