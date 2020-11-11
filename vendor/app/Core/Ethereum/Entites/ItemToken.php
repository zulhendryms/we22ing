<?php

namespace App\Core\Ethereum\Entities;

use App\Core\Base\Entities\BaseModel;
use Carbon\Carbon;

class ItemToken extends BaseModel {
    protected $table = 'ethitemtoken';
    protected $gcrecord = false;

    public function __get($key)
    {
        switch ($key) {
            case "IsActive":
                if (empty($this->DateStart) || empty($this->DateEnd)) return false;
                return Carbon::parse($this->DateStart)->lte(now()->addHours(company_timezone())) && Carbon::parse($this->DateEnd)->gte(now()->addHours(company_timezone()));
            case "IsUpcoming":
                if (empty($this->DateStart)) return false;
                return Carbon::parse($this->DateStart)->gte(now()->addHours(company_timezone()));
            case "IsEnded":
                if (empty($this->DateEnd)) return false;
                return Carbon::parse($this->DateEnd)->lt(now()->addHours(company_timezone()));
        }
        return parent::__get($key);
    }
    
    public function WhitelistUsers()
    {
        return $this->belongsToMany("App\Core\Security\Entities\User", "userwhitelistusers_ethitemtokenethitemtokens", "ETHItemTokens", "WhitelistUsers");
    }
}