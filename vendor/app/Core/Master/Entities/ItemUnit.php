<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;

class ItemUnit extends BaseModel {
    // use Activable;
    use Activable, BelongsToCompany;
    protected $table = 'mstitemunit';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

}