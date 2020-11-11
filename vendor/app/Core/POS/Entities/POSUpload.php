<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;

class POSUpload extends BaseModel {
    protected $table = 'posupload';
    protected $gcrecord = false;
    protected $author = false;
    public $timestamps = false;

    public function __get($key)
    {
        return parent::__get($key);
    }

}