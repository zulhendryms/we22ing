<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

/**
 * @property-read boolean $IsNonItem
 * @property-read boolean $IsInclude
 * @property-read boolean $IsBenefit
 * @property-read boolean $IsOptional
 * @property-read boolean $IsPurchase
 * @property-read boolean $IsSales
 */
class TravelPackageDetail extends BaseModel {
    use BelongsToCompany;
    protected $table = 'trvpackagedetail';

    public function __get($key)
    {
        switch ($key) {
            case "IsNonItem":
                return $this->Type == 0;
            case "IsInclude":
                return $this->Type == 1;
            case "IsBenefit":
                return $this->Type == 2;
            case "IsOptional":
                return $this->Type == 3;
            case "IsPurchase":
                return $this->IsInclude || $this->IsOptional;
            case "IsSales":
                return $this->IsOptional;
        } 
        return parent::__get($key);
    }
    
    /**
     * Get the item group of the detail
     */
    public function ItemGroupObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\ItemGroup", "ItemGroup", "Oid");
    }

    /**
     * Get the business partner of the detail
     */
    public function BusinessPartnerObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid");
    }

    /**
     * Get the item of the detail
     */
    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");
    }


    /**
     * Get the item price of the detail
     */
    public function TravelItemPriceObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelItemPrice", "TravelItemPrice", "Oid");
    }

    /**
     * Get the hotel price of the detail
     */
    public function TravelHotelPriceObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelHotelPrice", "TravelHotelPrice", "Oid");
    }

    /**
     * Get the transport price of the detail
     */
    public function TravelTransportPriceObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelTransportPrice", "TravelTransportPrice", "Oid");
    }
}