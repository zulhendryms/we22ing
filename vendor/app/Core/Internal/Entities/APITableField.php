<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class APITableField extends BaseModel 
{
    protected $table = 'apitablefield';
    public function APITableObj() { return $this->belongsTo("App\Core\Internal\Entities\APITable", "APITable", "Oid"); }
    public function APITableComboObj() { return $this->belongsTo("App\Core\Internal\Entities\APITable", "APITableCombo", "Oid"); }
}