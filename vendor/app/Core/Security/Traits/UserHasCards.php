<?php
namespace App\Core\Security\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Trait for User and WalletBalance association
 */
trait UserHasCards 
{
    public function SavedCards()
    {
        return $this->hasMany("App\Core\POS\Entities\SavedCard", "User", "Oid");
    }
}