<?php

namespace App\Core\PointOfSale\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class POSETicketReturn extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'poseticketreturn';

    public function BusinessPartnerObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid');
    }
    public function UserObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');
    }
    public function PointOfSaleObj()
    {
        return $this->belongsTo('App\Core\POS\Entities\PointOfSale', 'PointOfSale', 'Oid');
    }
    public function StatusObj()
    {
        return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid');
    }
    public function Details()
    {
        return $this->hasMany('App\Core\PointOfSale\Entities\POSETicketReturnDetail', 'POSETicketReturn', 'Oid');
    }
}
