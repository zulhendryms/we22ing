<?php
namespace App\Core\Base\Traits;

use App\Core\Base\Scopes\CompanyScope;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany {

    public static function bootBelongsToCompany() 
    {
        static::addGlobalScope(new CompanyScope);
        static::creating(function ($model) {
            $user = Auth::user();
            if ($user) $company = $user->Company; else $company = config('app.company_id');
            if (!isset($model->Company)) $model->Company = $company;
        });
    }

    public function CompanyObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid");
    }
}
