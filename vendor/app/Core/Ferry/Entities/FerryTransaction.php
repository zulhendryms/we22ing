<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\POS\Traits\ExtendsPointOfSale;

class FerryTransaction extends BaseModel {

    use ExtendsPointOfSale;

    protected $table = 'fertransaction';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;
    const XP_TARGET_TYPE = 'Cloud_ERP.Module.BusinessObjects.Ferry.FerTransaction';

    /**
     * Get the business partner port of the transaction
     */
    public function BusinessPartnerPortObj()
    {
        return $this->belongsTo("App\Core\Ferry\Entities\BusinessPartnerPort", "BusinessPartnerPort", "Oid");
    }

    /**
     * Get the schedule of the transaction
     */
    public function PortScheduleObj()
    {
        return $this->belongsTo("App\Core\Ferry\Entities\FerrySchedule", "PortSchedule", "Oid");
    }

    /**
     * Get the return schedule of the transaction
     */
    public function PortScheduleReturnObj()
    {
        return $this->belongsTo("App\Core\Ferry\Entities\FerrySchedule", "PortScheduleReturn", "Oid");
    }

    /**
     * Get the item of the transaction
     */
    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "FerItemAttraction", "Oid");
    }

    /**
     * Get the passengers of the POS
     */
    public function Passengers()
    {
        return $this->hasMany("App\Core\Ferry\Entities\FerryPassenger", "FerTransaction", "Oid");
    }
}