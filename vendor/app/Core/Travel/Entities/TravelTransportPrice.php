<?php

    namespace App\Core\Travel\Entities;

    use App\Core\Base\Entities\BaseModel;
    use App\Core\Base\Traits\BelongsToCompany;

    class TravelTransportPrice extends BaseModel
    {
        use BelongsToCompany;
        protected $table = 'trvitemtransportprice';
        
        public function ItemObj()
        {
            return $this->belongsTo('App\Core\Master\Entities\Item', 'Item', 'Oid');
        }
        public function TravelTransportRouteObj()
        {
            return $this->belongsTo('App\Core\Travel\Entities\TravelTransportRoute', 'TravelTransportRoute', 'Oid');
        }
        public function CurrencyObj()
        {
            return $this->belongsTo('App\Core\Master\Entities\Currency', 'Currency', 'Oid');
        }
    }
