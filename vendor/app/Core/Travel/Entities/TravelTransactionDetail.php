<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelTransactionDetail extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trvtransactiondetail';

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (!isset($model->Code)) $model->Code = now()->format('ymdHis') . '-' . str_random(3);
        });
    }

    public function PointOfSaleObj()
    {
        return $this->belongsTo('App\Core\POS\Entities\PointOfSale', 'TravelTransaction', 'Oid');
    }
    public function TravelHotelRoomCategoryObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelHotelRoomCategory', 'TravelHotelRoomCategory', 'Oid');
    }

    public function TravelHotelRoomTypeObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelHotelRoomType', 'TravelHotelRoomType', 'Oid');
    }
    public function UserObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');
    }

    public function TravelTransactionObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelTransaction', 'TravelTransaction', 'Oid');
    }

    public function BusinessPartnerObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid');
    }

    public function ItemContentObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\ItemContent', 'ItemContent', 'Oid');
    }

    public function ItemObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid');
    }
    public function PurchaseCurrencyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Currency', 'PurchaseCurrency', 'Oid');
    }
    public function ItemHotelObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelItemHotel', 'Item', 'Oid');
    }

    public function StatusObj()
    {
        return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid');
    }

    public function Passengers()
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelPassenger", "TravelTransactionDetail", "Oid");
    }
    public function TravelTransportRouteObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelTransportRoute', 'TravelTransportRoute', 'Oid');
    }
}
