<?php

namespace App\Core\Internal\Traits;

use App\Core\Internal\Services\TempStorageService;

trait TempStorage
{
    protected $tempStorage;
    /**
     * @return TempStorageService
     */
    public function tempStorage()
    {
        if (is_null($this->tempStorage)) $this->tempStorage = new TempStorageService();
        return $this->tempStorage;
    }
}