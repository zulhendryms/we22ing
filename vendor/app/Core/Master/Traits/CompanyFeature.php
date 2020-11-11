<?php

namespace App\Core\Master\Traits;

trait CompanyFeature
{
    /**
     * Check if company has feature
     * 
     * @param string $code
     * @return boolean
     */
    public function hasFeature($code)
    {
        if (empty($this->FeatureConfig)) return false;
        $config = json_decode($this->FeatureConfig, true);
        if (!isset($config['features'])) return false;
        return in_array($code, $config['features']);
    }

    /**
     * Check if company has plugin
     * 
     * @param string $code
     * @return boolean
     */
    public function hasPlugin($code)
    {
        if (empty($this->FeatureConfig)) return false;
        $config = json_decode($this->FeatureConfig, true);
        if (!isset($config['plugins'])) return false;
        return in_array($code, $config['plugins']);
    }
}