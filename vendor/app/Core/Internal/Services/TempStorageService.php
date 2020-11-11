<?php

namespace App\Core\Internal\Services;

use App\Core\Internal\Exceptions\UnsupportedTempStorageDriverException;
use Illuminate\Http\Request;

class TempStorageService
{
    /** @var string $userTimezoneKey */
    protected $userTimezoneKey = 'user_timezone';

    /** @var string $userIdKey */
    protected $userIdKey = 'user_id';

    /** @var string $userDeviceKey */
    protected $userDeviceKey = 'user_device';

    /** @var string $userLanguageKey */
    protected $userLanguageKey = 'user_language';

    /** @var string $userCurrencyKey */
    protected $userCurrencyKey = 'user_currency';

    /** @var string $returnURLKey */
    protected $returnURLKey = 'return_url';

    /** @var array $supportedDriver */
    protected $supportedDriver = [ 'session' ];

    /** @var string $driver */
    protected $driver = 'session';

    /**
     * Set or Get driver of the storage
     * 
     * @param string|null $type
     * @return $string
     */
    public function driver($type = null)
    {
        if (!in_array($type, $this->supportedDriver)) {
            throw new UnsupportedTempStorageDriverException("Driver {$type} is not supported");
        }
        if (isset($type)) $this->driver = $type;
        return $driver;
    }

    
    /**
     * Check if storage is available
     * 
     * @return boolean
     */
    public function isAvailable()
    {
        return session()->isStarted();
    }

    /**
     * Set value to storage
     * 
     * @param string $key
     * @param string $value
     * @return void
     */
    public function set($key, $value)
    {
        session()->put($key, $value);
    }

     /**
     * Get value from storage
     * 
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        return session($key);
    }

    /**
     * Get and remove value from storage
     * 
     * @param string $key
     * @return mixed
     */
    public function getAndRemove($key)
    {
        $value = $this->get($key);
        $this->remove($key);
        return $value;
    }

    /**
     * Get value from storage
     * 
     * @param string $key
     * @return mixed
     */
    public function remove($key)
    {
        return session()->remove($key);
    }

    /**
     * Set user id
     * 
     * @param string $id
     * @return void
     */
    public function setUserId($id)
    {
        $this->set($this->userIdKey, $id);
    }

    /**
     * Get user id
     * 
     * @return string
     */
    public function getUserId()
    {
        return $this->get($this->userIdKey);
    }

    /**
     * Remove user id
     * 
     * @return void
     */
    public function removeUserId()
    {
        $this->remove($this->userIdKey);
    }

    /**
     * Set user currency
     * 
     * @param string $currency
     * @return void
     */
    public function setUserCurrency($currency)
    {
        $this->set($this->userCurrencyKey, $currency);
    }

    /**
     * Get user currency
     * 
     * @return string
     */
    public function getUserCurrency()
    {
        return $this->get($this->userCurrencyKey);
    }

    /**
     * Remove user currency
     * 
     * @return void
     */
    public function removeUserCurrency()
    {
        $this->remove($this->userCurrencyKey);
    }

    /**
     * Set user timezone
     * 
     * @param int $timezone
     * @return void
     */
    public function setUserTimezone($timezone)
    {
        $this->set($this->userTimezoneKey, $timezone);
    }

    /**
     * Get user timezone
     * 
     * @return string
     */
    public function getUserTimezone()
    {
        return $this->get($this->userTimezoneKey);
    }

    /**
     * Remove user timezone
     * 
     * @return void
     */
    public function removeUserTimezone()
    {
        $this->remove($this->userTimezoneKey);
    }

    /**
     * Set user language
     * 
     * @param int $language
     * @return void
     */
    public function setUserLanguage($language)
    {
        $this->set($this->userLanguageKey, $language);
    }

     /**
     * Remove user language
     * 
     * @return void
     */
    public function removeUserLanguage()
    {
        $this->remove($this->userLanguageKey);
    }

    /**
     * Get user language
     * 
     * @return string
     */
    public function getUserLanguage()
    {
        return $this->get($this->userLanguageKey);
    }

     /**
     * Set user device
     * 
     * @param string $device
     * @return void
     */
    public function setUserDevice($device)
    {
        $this->set($this->userDeviceKey, $device);
    }

    /**
     * Get user device
     * 
     * @return string
     */
    public function getUserDevice()
    {
        return $this->get($this->userDeviceKey);
    }

    /**
     * Remove user device
     * 
     * @return void
     */
    public function removeUserDevice()
    {
        $this->remove($this->userDeviceKey);
    }

     /**
     * Set return URL
     * 
     * @param string $url
     * @return void
     */
    public function setReturnURL($url)
    {
        $this->set($this->returnURLKey, $url);
    }

    /**
     * Get return URL
     * 
     * @return string
     */
    public function getReturnURL()
    {
        return $this->get($this->returnURLKey);
    }

    /**
     * Get and remove return URL
     * 
     * @return string
     */
    public function getAndRemoveReturnURL()
    {
        return $this->getAndRemove($this->returnURLKey);
    }

    /**
     * Remove user device
     * 
     * @return void
     */
    public function removeReturnURL()
    {
        $this->remove($this->returnURLKey);
    }

}