<?php

    namespace App\Core\Master\Entities;

    use App\Core\Base\Entities\BaseModel;
    use App\Core\Base\Traits\BelongsToCompany;

    class CostCenter extends BaseModel
    {
        use BelongsToCompany;
        protected $table = 'mstcostcenter';
        
    public function CompanyObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid"); }
        public function TruckingPrimeMoverObj()
        {
            return $this->belongsTo('App\Core\Trucking\Entities\TruckingPrimeMover', 'TruckingPrimeMover', 'Oid');
        }
        public function EmployeeObj()
        {
            return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid');
        }
        public function TruckingTrailerObj()
        {
            return $this->belongsTo('App\Core\Trucking\Entities\TruckingTrailer', 'TruckingTrailer', 'Oid');
        }
        public function CostCenterGroupObj()
        {
            return $this->belongsTo('App\Core\Master\Entities\CostCenterGroup', 'CostCenterGroup', 'Oid');
        }

        
        public function Logs()
        {
            return $this->hasMany('App\Core\Master\Entities\CostCenterLog', 'CostCenter', 'Oid');
        }
    }
