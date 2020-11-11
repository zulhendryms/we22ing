<?php

namespace App\Core\Accounting\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class GeneralJournal extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'accgeneraljournal';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Code.' '.$this->Date;
        }
        return parent::__get($key);
    }

    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function StatusObj() { return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid"); }
    public function Details() { return $this->hasMany("App\Core\Accounting\Entities\Journal", "GeneralJournal", "Oid"); }

    public function CurrencyRateObj() { return $this->belongsTo('App\Core\Master\Entities\CurrencyRate', 'CurrencyRate', 'Oid'); }
    public function ProjectObj() { return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid'); }
    public function WarehouseObj() { return $this->belongsTo('App\Core\Master\Entities\Warehouse', 'Warehouse', 'Oid'); }
    public function AccountFieldObj() { return $this->belongsTo('App\Core\Accounting\Entities\Account', 'AccountField', 'Oid'); }

}