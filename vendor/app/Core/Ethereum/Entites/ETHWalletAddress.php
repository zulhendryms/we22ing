<?php

namespace App\Core\Ethereum\Entities;

use App\Core\Base\Entities\BaseModel;
use Carbon\Carbon;

class ETHWalletAddress extends BaseModel {
    protected $table = 'ethuserwalletaddress';
    protected $gcrecord = false;

    /**
     * Set Address value
     * @param string $value
     * @return void
     */
    public function setAddressAttribute($value)
    {
        $this->attributes['Address'] = encrypt_salted($value);
    }

    /**
     * Get Address value
     * @param string $value
     * @return void
     */
    public function getAddressAttribute($value)
    {
        return decrypt_salted($value);
    }

    /**
     * Get the currency of the address
     */
    public function CurrencyObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid");
    }
}