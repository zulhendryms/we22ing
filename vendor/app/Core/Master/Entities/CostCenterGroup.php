<?php

    namespace App\Core\Master\Entities;

    use App\Core\Base\Entities\BaseModel;
    use App\Core\Base\Traits\BelongsToCompany;

    class CostCenterGroup extends BaseModel
    {
        use BelongsToCompany;
        protected $table = 'mstcostcentergroup';
        

        
    public function CompanyObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid"); }
        public function Details()
        {
            return $this->hasMany('App\Core\Master\Entities\CostCenter', 'CostCenterGroup', 'Oid');
        }
    }
