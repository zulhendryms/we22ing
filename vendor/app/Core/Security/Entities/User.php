<?php

namespace App\Core\Security\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\HasApiTokens;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Master\Entities\Currency;
use Illuminate\Support\Facades\DB;
use App\Core\POS\Entities\PointOfSaleDetail;
use App\Core\Internal\Entities\Status;
use App\Core\Security\Traits\UserHasETHAddress as HasETHAddress;
use App\Core\Security\Traits\UserInvitation;
use App\Core\Security\Traits\UserAdministrative as Administrative;
use App\Core\POS\Traits\HasBalance;
use App\Core\Security\Traits\UserHasBalance;
use App\Core\Security\Traits\UserHasCards as HasCards;

/**
 * @property-read boolean $IsPhoneVerified
 */
class User extends BaseModel implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract 
{
    use Authenticatable, Authorizable, CanResetPassword;
    use HasApiTokens;
    use Notifiable;
    use Administrative;
    use HasBalance;
    use UserHasBalance;
    use HasETHAddress;
    use UserInvitation;
    use HasCards;
    // use BelongsToCompany;

    protected $table = 'user';
    protected $hidden = [
        'StoredPassword', 'StoredPassword2',
    ];

    const XP_TARGET_TYPE = 'Cloud_ERP.Module.BusinessObjects.Security.User';

    public function __get($key)
    {
        switch($key) {
            case "IsPhoneVerified":
                return isset($this->PhoneVerified);
            case "IsDateVerified":
                return isset($this->DateVerified);
            case "IsEmailVerified":
                return isset($this->EmailVerified);
            case "IsGAVerified":
                return isset($this->GAVerified);
        }
        return parent::__get($key);
    }

    public function getAuthPassword() { return $this->StoredPassword2; }
    public function findForPassport($username) 
    {
        return $this->where('UserName', $username)->first();
    }
    public function validateForPassportPasswordGrant($password)
    {
        return $this->validatePassword($password);
    }
    public function validatePassword($password)
    {
        return Hash::check($password, $this->StoredPassword2);
    }

    private function getcol($data) {
        switch ($data) {
            case 1: return '413573';
            case 2: return '1FCBA3';
            case 3: return 'F66280';
            case 4: return 'CB34A7';
            case 5: return '742CF3';
            default: return '0C85D5';
        }
    }

    public function returnUserObj($row, $userObj) {
        $obj = $row->{$userObj.'Obj'};
        unset($row->{$userObj.'Obj'});
        if (isset($obj)) {
            $color = ord(strtoupper(substr($obj->Name, 0,1)));
            $color = $this->getcol(($color % 5) + 1);
            $row->{$userObj.'Obj'} = (object) [
                'Oid' => $obj->Oid,
                'Name' => $obj->Name ?: $this->UserName,
                'Image' => $obj->Image, //'https://cdn.iconscout.com/icon/free/png-256/account-profile-avatar-man-circle-round-user-30452.png',
                'Color' => $color,
            ];
        }
        return $row;
    }

    public function UserProfileObj() {        
        $color = ord(strtoupper(substr($this->Name, 0,1)));
        $color = $this->getcol(($color % 5) + 1);
        $return = (object) [
            'Oid' => $this->Oid,
            'Name' => $this->Name ?: $this->UserName,
            'Image' => $this->Image, //'https://cdn.iconscout.com/icon/free/png-256/account-profile-avatar-man-circle-round-user-30452.png',
            'Color' => $color,
        ];
        return $return;
    }

    // /**
    //  * Get remember token value
    //  * 
    //  * @return null
    //  */
    // public function getRememberToken()
    // {
    //     return null; // not supported
    // }

    //  /**
    //  * Set remember token value
    //  * @param string $value
    //  * @return void
    //  */
    // public function setRememberToken($value)
    // {
    //     // not supported
    // }

    //  /**
    //  * Get remember token column name
    //  * @return null
    //  */
    // public function getRememberTokenName()
    // {
    //     return null; // not supported
    // }

    //  /**
    //  * Set model attribute
    //  * @return void
    //  */
    // public function setAttribute($key, $value)
    // {
    //     $isRememberTokenAttribute = $key == $this->getRememberTokenName();
    //     if (!$isRememberTokenAttribute) {
    //         parent::setAttribute($key, $value);
    //     }
    // }

     /**
     * Set Password value
     * @param string $value
     * @return void
     */
    public function setStoredPassword2Attribute($value)
    {
        $this->attributes['StoredPassword2'] = bcrypt($value);
    }

    /**
     * Get Password value
     * @param string $value
     * @return void
     */
    public function setPasswordAttribute($value)
    {
        $this->StoredPassword2 = $value;
    }

    public function EmployeeObj() { return $this->belongsTo('App\Core\Master\Entities\Employee', 'Employee', 'Oid'); }
    public function CompanyObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "Company", "Oid"); }
    public function CompanySourceObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "CompanySource", "Oid"); }
    public function CompanyCurrentObj() { return $this->belongsTo("App\Core\Master\Entities\Company", "CompanyCurrent", "Oid"); }
    public function BusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid"); }
    public function CurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "Currency", "Oid"); }
    public function CountryObj() { return $this->belongsTo("App\Core\Internal\Entities\Country", "Country", "Oid"); }
    public function CityObj() { return $this->belongsTo("App\Core\Master\Entities\City", "City", "Oid"); }
    public function POSSessions() { return $this->hasMany("App\Core\POS\Entities\POSSession", "User", "Oid"); }
    public function PointOfSales() { return $this->hasMany("App\Core\POS\Entities\PointOfSale", "User", "Oid"); }
    public function Passengers() { return $this->hasMany("App\Core\Ferry\Entities\UserPassenger", "User", "Oid"); }
    public function ETHWalletAddresses() { return $this->hasMany("App\Core\Ethereum\Entities\ETHWalletAddress", "User", "Oid"); }
    public function Devices() { return $this->hasMany("App\Core\Security\Entities\Device", "User", "Oid"); }
    public function WalletBalance() { return $this->hasMany("App\Core\POS\Entities\WalletBalance", "User", "Oid"); }
    public function Wishlists() { return $this->hasMany("App\Core\POS\Entities\Wishlist", "User", "Oid"); }
    public function RoleObj() { return $this->belongsTo("App\Core\Internal\Entities\Role", "Role", "Oid"); }
    // public function RoleMasterObj() { return $this->belongsTo("App\Core\Internal\Entities\RoleMaster", "Role", "Oid"); }
    public function WhitelistTokens() { return $this->belongsToMany("App\Core\Ethereum\Entities\ItemToken", "userwhitelistusers_ethitemtokenethitemtokens", "WhitelistUsers", "ETHItemTokens"); }

    public function getTotalPurchasedItem($id = null)
    {
        $cancelStatus = Status::cancelled()->value('Oid');

        return PointOfSaleDetail::whereHas('PointOfSaleObj', function ($query) use ($cancelStatus) {
            $query->where('User', $this->Oid)
            ->where('Status', '<>', $cancelStatus);
        })
        ->where('Item', $id)
        ->sum('pospointofsaledetail.Quantity');
    }
    
    /**
     * Get the sales price level
     * @param User $user
     * @return int|string
     */
    public function getSalesPriceLevel()
    {
        $level = '';
        $businessPartner = $this->BusinessPartnerObj;
        if (isset($businessPartner)) {
            if (!empty($businessPartner->SalesPriceLevel)) {
                $level = $businessPartner->SalesPriceLevel;
            }
        }
        return $level;
    }

                
public function TimezoneObj() { return $this->belongsTo('App\Core\Internal\Entities\Timezone', 'Timezone', 'Oid'); }
public function InvitorUserObj() { return $this->belongsTo('App\Core\Security\Entities\User', 'InvitorUser', 'Oid'); }
}