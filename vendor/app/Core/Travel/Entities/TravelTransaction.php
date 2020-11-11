<?php

namespace App\Core\Travel\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\POS\Traits\ExtendsPointOfSale;

class TravelTransaction extends BaseModel
{
    // use BelongsToCompany;
    use ExtendsPointOfSale;
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;

    protected $table = 'traveltransaction';

    public function PointOfSaleObj()
    {
        return $this->belongsTo("App\Core\POS\Entities\PointOfSale", "PointOfSale", "Oid");
    }

    public function TravelPackageObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelItemPackage", "TravelPackage", "Oid");
    }

    public function TravelItemTourPackageObj()
    {
        return $this->belongsTo('App\Core\Travel\Entities\TravelItemTourPackage', 'TravelItemTourPackage', 'Oid');
    }

    public function TravelTypeObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelType", "TravelType", "Oid");
    }

    public function EmployeeObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Employee", "TourGuide", "Oid");
    }

    public function TravelGuide1Obj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelGuide", "TravelGuide1", "Oid");
    }
    public function TravelGuide2Obj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelGuide", "TravelGuide2", "Oid");
    }
    public function JournalCommissions()
    {
        return $this->hasMany("App\Core\Accounting\Entities\Journal", "TravelTransactionCommission", "Oid");
    }
    public function APCommissions()
    {
        return $this->hasMany("App\Core\Accounting\Entities\APInvoice", "TravelCommission", "Oid");
    }
    public function ARCommissions()
    {
        return $this->hasMany("App\Core\Accounting\Entities\ARInvoice", "TravelCommission", "Oid");
    }
    public function UserProcessObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'UserProcess', 'Oid');
    }
    public function Details()
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelTransactionDetail", "TravelTransaction", "Oid");
    }
    public function Itineraries()
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelTransactionItinerary", "TravelTransaction", "Oid");
    }
    public function Flights()
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelTransactionFlight", "TravelTransaction", "Oid");
    }
    public function Passengers()
    {
        return $this->hasMany("App\Core\Travel\Entities\TravelTransactionPassenger", "TravelTransaction", "Oid");
    }
    public function Journals()
    {
        return $this->hasMany("App\Core\Accounting\Entities\Journal", "TravelTransaction", "Oid");
    }
}
