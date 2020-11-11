<?php

namespace App\Core\Ferry\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

    class FerryBusinessPartnerPort extends BaseModel 
    {
        use BelongsToCompany;
        protected $table = 'ferbusinesspartnerport';                

        public function BusinessPartnerObj() { return $this->belongsTo('App\Core\Master\Entities\BusinessPartner', 'BusinessPartner', 'Oid'); }
        public function PortObj() { return $this->belongsTo('App\Core\Ferry\Entities\Port', 'Port', 'Oid','Name'); }



    }
