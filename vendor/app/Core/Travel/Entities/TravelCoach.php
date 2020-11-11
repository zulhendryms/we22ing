<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelCoach extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvcoach';

    public function ParkingLotCompanyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'ParkingLotCompany', 'Oid');
    }
    public function ParkingLotPaymentTermObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'ParkingLotPaymentTerm', 'Oid');
    }
    public function VPCCompanyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'VPCCompany', 'Oid');
    }
    public function VPCPaymentTermObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\PaymentTerm', 'VPCPaymentTerm', 'Oid');
    }
    public function InsuranceAgentObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'InsuranceAgent', 'Oid');
    }
}
