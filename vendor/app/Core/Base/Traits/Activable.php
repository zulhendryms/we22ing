<?php
namespace App\Core\Base\Traits;

use App\Core\Base\Scopes\ActiveScope;

trait Activable {
    public static function bootActivable() 
    {
        static::addGlobalScope(new ActiveScope);
        static::creating(function ($model) {
            $model->IsActive = true;
        });
    }
}