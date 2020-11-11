<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class WalletBalance extends BaseModel {
    use BelongsToCompany;
    protected $table = 'poswalletbalance';
    const XP_TARGET_TYPE = 'Cloud_ERP.Module.BusinessObjects.POS.WalletBalance';

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (!isset($model->Code)) $model->Code = now()->format('ymdHis').' - '.str_random(3);
            if (!isset($model->Date)) $model->Date = now()->toDateTimeString();
        });
    }

    /**
     * Get the currency of walletbalance
     */
    public function CurrencyObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid");
    }

    /**
     * Get the ethcurrency of the POS
     */
    public function ETHCurrencyObj()
    {
        return $this->belongsTo("App\Core\Ethereum\Entities\ETHCurrency", "Currency", "Oid");
    }

    /**
     * Get the user of walletbalance
     */
    public function UserObj()
    {
        return $this->belongsTo("App\Core\Security\Entities\User", "User", "Oid");
    }

    /**
     * Get the status of walletbalance
     */
    public function StatusObj()
    {
        return $this->belongsTo("App\Core\Internal\Entities\Status", "Status", "Oid");
    }

    /**
     * Get the pos of walletbalance
     */
    public function PointOfSaleObj()
    {
        return $this->belongsTo("App\Core\POS\Entities\PointOfSale", "PointOfSale", "Oid");
    }

    public function BusinessPartnerObj() 
    { 
        return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); 
    }


    /**
     * Set Recipient value
     * @param string $value
     * @return void
     */
    public function setRecipientAttribute($value)
    {
        $this->attributes['Recipient'] = encrypt_salted($value);
    }

    /**
     * Get Recipient value
     * @param string $value
     * @return void
     */
    public function getRecipientAttribute($value)
    {
        return decrypt_salted($value);
    }
}