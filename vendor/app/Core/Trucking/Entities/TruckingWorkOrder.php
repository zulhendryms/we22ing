<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingWorkOrder extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'trcworkorder';                
            
    public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
    public function CurrencyObj() { return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid'); }
    public function ContainerTypeAndSizeObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingContainerType', 'ContainerTypeAndSize', 'Oid'); }
    public function FromBusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'FromBusinessPartner', 'Oid'); }
    public function FromAddressObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingAddress', 'FromAddress', 'Oid'); }
    public function ToAddressObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingAddress', 'ToAddress', 'Oid'); }
    public function ToBusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'ToBusinessPartner', 'Oid'); }
    public function UserDriverObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'UserDriver', 'Oid'); }
    public function TruckingTrailerObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingTrailer', 'TruckingTrailer', 'Oid'); }
    public function TruckingPrimeMoverObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPrimeMover', 'TruckingPrimeMover', 'Oid'); }
    public function ToAddress1Obj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingAddress', 'ToAddress1', 'Oid'); }
    public function FromPortObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPort', 'FromPort', 'Oid'); }
    public function ToAddress2Obj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingAddress', 'ToAddress2', 'Oid'); }
    public function ToPortObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPort', 'ToPort', 'Oid'); }
    public function ToAddress3Obj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingAddress', 'ToAddress3', 'Oid'); }
    public function ToBusinessPartner1Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'ToBusinessPartner1', 'Oid'); }
    public function ToPort1Obj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPort', 'ToPort1', 'Oid'); }
    public function ToBusinessPartner2Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'ToBusinessPartner2', 'Oid'); }
    public function ToPort2Obj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingAddress', 'ToPort2', 'Oid'); }
    public function ToBusinessPartner3Obj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'ToBusinessPartner3', 'Oid'); }
    public function ToPort3Obj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPort', 'ToPort3', 'Oid'); }
    public function TruckingDriverSessionObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingDriverSession', 'TruckingDriverSession', 'Oid'); }
    public function TruckingDriverCodeObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingDriverCode', 'TruckingDriverCode', 'Oid'); }
    public function TruckingSalesCodeObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingSalesCode', 'TruckingSalesCode', 'Oid'); }
    public function TruckingWorkOrderReferenceObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingWorkOrder', 'TruckingWorkOrderReference', 'Oid'); }
    public function TruckingRouteObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingRoute', 'TruckingRoute', 'Oid'); }
    public function TruckingPortObj() { return $this->belongsTo('App\Core\Trucking\Entities\TruckingPort', 'TruckingPort', 'Oid'); }
    
                
    public function Logs() { return $this->hasMany('App\Core\Trucking\Entities\TruckingWorkOrderLog', 'TruckingWorkOrder', 'Oid'); }
    public function Images() { return $this->hasMany('App\Core\Trucking\Entities\TruckingWorkOrderImage', 'TruckingWorkOrder', 'Oid'); }

}
        