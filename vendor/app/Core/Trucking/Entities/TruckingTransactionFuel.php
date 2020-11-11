<?php

namespace App\Core\Trucking\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class TruckingTransactionFuel extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'trctransactionfuel';
    public function DepartmentObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Department', 'Department', 'Oid');
    }
    public function CompanyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Company', 'Company', 'Oid');
    }
    public function TruckingPrimeMoverObj()
    {
        return $this->belongsTo('App\Core\Trucking\Entities\TruckingPrimeMover', 'TruckingPrimeMover', 'Oid');
    }
    public function UserObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');
    }
    public function UserProcessObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'UserProcess', 'Oid');
    }
    public function StatusObj()
    {
        return $this->belongsTo('App\Core\Internal\Entities\Status', 'Status', 'Oid');
    }
    public function Comments()
    {
        return $this->hasMany('App\Core\Pub\Entities\PublicComment', 'PublicPost', 'Oid');
    }
    public function Approvals()
    {
        return $this->hasMany('App\Core\Pub\Entities\PublicApproval', 'PublicPost', 'Oid');
    }
}
