<?php
namespace App\Core\Base\Traits;

use Illuminate\Support\Facades\Auth;

trait HasAuthor {
    
    public static function bootHasAuthor() 
    {
        self::creating(function ($model) {
            if ($model->hasAuthor() && Auth::check() && !isset($model->CreatedBy)) {
                $model->CreatedBy = Auth::user()->Oid;
                $model->UpdatedBy = Auth::user()->Oid;
            }
        });
        self::updating(function ($model) {
            if ($model->hasAuthor() && Auth::check()) $model->UpdatedBy = Auth::user()->Oid;
        });
    }

    public function hasAuthor()
    {
        return isset($this->author) ? $this->author : true;
    }
}