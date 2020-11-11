<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TravelItemTourPackage extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trvitemtourpackage';                
            
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }        
    public function Prices() { return $this->hasMany('App\Core\Travel\Entities\TravelItemTourPackagePrice', 'TravelItemTourPackage', 'Oid'); }
    public function Amounts() { return $this->hasMany('App\Core\Travel\Entities\TravelItemTourPackageOtherAmount', 'TravelItemTourPackage', 'Oid'); }
    public function Attractions() { return $this->hasMany('App\Core\Travel\Entities\TravelItemTourPackageAttraction', 'TravelItemTourPackage', 'Oid'); }
    public function Restaurants() { return $this->hasMany('App\Core\Travel\Entities\TravelItemTourPackageRestaurant', 'TravelItemTourPackage', 'Oid'); }
    public function Transports() { return $this->hasMany('App\Core\Travel\Entities\TravelItemTourPackageTransport', 'TravelItemTourPackage', 'Oid'); }
    public function Details() { return $this->hasMany('App\Core\Travel\Entities\TravelItemTourPackageItinerary', 'TravelItemTourPackage', 'Oid'); }
    public function Hotels() { return $this->hasMany('App\Core\Travel\Entities\TravelItemTourPackageHotel', 'TravelItemTourPackage', 'Oid'); }
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
    public function BusinessPartnerGroupObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartnerGroup', 'BusinessPartnerGroup', 'Oid'); }


}
