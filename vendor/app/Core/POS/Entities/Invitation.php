<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Invitation extends BaseModel 
{
    use BelongsToCompany;
    protected $table = 'posinvitation';

    /**
     * Scope a query to return Entry.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive() 
    {
        $now = now()->addHour(company_timezone())->toDateTimeString();
        return $this->where('DateStart', '<=', $now)
        ->where('DateEnd', '>=', $now);
    }

    public function InvitationUsers() { return $this->hasMany('App\Core\POS\Entities\InvitationUser', 'POSInvitation', 'Oid'); }
}