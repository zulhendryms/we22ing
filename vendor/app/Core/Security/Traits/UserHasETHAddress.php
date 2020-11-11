<?php
namespace App\Core\Security\Traits;

use Illuminate\Support\Facades\DB;
use App\Core\External\Services\Web3Service;

trait UserHasETHAddress 
{

    /**
     * Set ETHAddress value
     * @param string $value
     * @return void
     */
    public function setETHAddressAttribute($value)
    {
        $this->attributes['ETHAddress'] = encrypt_salted($value);
    }

    /**
     * Get ETHAddress value
     * @param string $value
     * @return void
     */
    public function getETHAddressAttribute($value)
    {
       return decrypt_salted($value);
    }

    /**
     * Set ETHAddress secret value
     * @param string $value
     * @return void
     */
    public function setETHAddressSecretAttribute($value)
    {
        $this->attributes['ETHAddressSecret'] = encrypt_salted($value);
    }

    /**
     * Get ETHAddress secret value
     * @param string $value
     * @return void
     */
    public function getETHAddressSecretAttribute($value)
    {
       return decrypt_salted($value);
    }

    public function createETHAddress()
    {
        $result = (new Web3Service())->createEthAddress();
        $this->ETHAddress = $result->address;
        $this->ETHAddressSecret = $result->privateKey;

        app()->make('\App\Core\Internal\Services\AuditService')->create($this, [
            'Type' => 'CreateETHAddress',
            'Description' => 'Create ETH Address',
            'NewValue' => json_encode([
                'address_enc' => encrypt_salted($result->address),
                'privateKey_enc' => encrypt_salted($result->privateKey),
            ]),
            'User' => $this
        ]);

        $this->save();
    }
}