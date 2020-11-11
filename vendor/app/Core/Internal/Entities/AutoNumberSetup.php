<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class AutoNumberSetup extends BaseModel {
    protected $table = 'sysautonumbersetup';

    public function __get($key)
    {
        switch($key) {
            case "IsDefault":
                return $this->Type == 0;
            case "IsPrefixSuffix":
                return $this->Type == 1;
            case "IsDirect":
                return $this->Type == 2;
        }
        return parent::__get($key);
    }
}