<?php

namespace App\Core\Trading\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class SalesInvoiceDetailTravel extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trdsalesinvoicedetailtravel';

public function SalesInvoiceObj() { return $this->belongsTo('App\Core\Trading\Entities\SalesInvoice', 'SalesInvoice', 'Oid'); }
public function CostCenterObj() { return $this->belongsTo('App\Core\Master\Entities\CostCenter', 'CostCenter', 'Oid'); }

}