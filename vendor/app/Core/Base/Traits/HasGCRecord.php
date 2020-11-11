<?php
namespace App\Core\Base\Traits;

use App\Core\Base\Scopes\GCRecordScope;

trait HasGCRecord {
    
    public static function bootHasGCRecord() 
    {
        static::addGlobalScope(new GCRecordScope);
    }

    /**
     * Check if GCRecord is enabled
     * @return boolean
     */
    public function usesGCRecord()
    {
        return isset($this->gcrecord) ? $this->gcrecord : true;
    }
}