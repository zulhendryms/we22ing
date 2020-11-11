<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelAllotment extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvallotment';

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (!isset($model->Code)) $model->Code = random_int(123456789, 999999999);
        });
    }

    /**
     * Get the item of allotment
     */
    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");
    }

    /**
     * Get the transaction of allotment
     */
    public function TravelTransactionAllotments()
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelTransactionAllotment", "TravelAllotment", "Oid");
    }

    /**
     * Get the travel allotment cutoff
     */
    public function TravelAllotmentCutoffObj()
    {
        return $this->hasOne("App\Core\Travel\Entities\TravelAllotmentCutoff", "TravelAllotment", "Oid");
    }
}