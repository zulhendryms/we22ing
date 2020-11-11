<?php

namespace App\Core\Internal\Entities;

use App\Core\Base\Entities\BaseModel;

class Status extends BaseModel {
    protected $table = 'sysstatus';

    public function __get($property) 
    {
        switch($property) {
            case "Title": return $this->Name.' - '.$this->Code;
            case 'IsPaid': return $this->IsStatusPaid;
            case 'IsPosted': return $this->IsPostedJournal;
            case 'IsEntry': return $this->Code == 'entry';
            case 'IsQuoted': return $this->Code == 'quoted';
            case 'IsOrdered': return $this->Code == 'ordered';
            case 'IsVerifying': return $this->Code == 'verify';
            case 'IsCancelled': return $this->Code == 'cancel';
            case 'IsCompleted': return $this->Code == 'complete';
            case 'IsExpired': return $this->Code == 'expired';
        }
        return parent::__get($property);
    }
    
    public function scopeEntry() { return $this->where('Code', 'entry'); }
    public function scopeQuoted() { return $this->where('Code', 'quoted'); }
    public function scopeOrdered() { return $this->where('Code', 'ordered'); }
    public function scopeVerifying() { return $this->where('Code', 'verify'); }
    public function scopePosted() { return $this->where('Code', 'posted'); }
    public function scopeExpired() { return $this->where('Code', 'expired'); }
    public function scopeCancelled() { return $this->where('Code', 'cancel'); }
    public function scopePaid() { return $this->where('Code', 'paid'); }
    public function scopeComplete() { return $this->where('Code', 'complete'); }
    public function ezbName()
    {
        if ($this->IsEntry) return 'select_payment';
        if ($this->IsOrdered) return 'waiting_for_payment';
        if ($this->IsVerifying) return 'verifying';
        if ($this->IsPaid) return 'paid';
        if ($this->IsCancelled) return 'cancel';
        if ($this->IsExpired) return 'expired';
    }
}