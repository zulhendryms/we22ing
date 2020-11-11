<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class JournalType extends BaseModel {
    protected $table = 'sysjournaltype';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function scopeAuto() { return $this->where('Code', 'AUTO'); }
    public function scopeStin() { return $this->where('Code', 'STIN'); }
    public function scopeStout() { return $this->where('Code', 'STOUT'); }
    public function scopePl() { return $this->where('Code', 'PL'); }
    public function scopeOpen() { return $this->where('Code', 'OPEN'); }
    public function scopeCash() { return $this->where('Code', 'CASH'); }
    public function scopePinv() { return $this->where('Code', 'PINV'); }
    public function scopeCogs() { return $this->where('Code', 'COGS'); }
}