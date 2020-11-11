<?php

namespace App\Core\Master\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\Activable;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Master\Traits\ItemSalesAmount;
use App\Core\Master\Traits\ItemSalesAmountByAge;
use App\Core\Master\Traits\ItemSalesAmountByDay;
use App\Core\Master\Traits\ItemPurchaseAmountByAge;
use App\Core\Master\Traits\ItemPurchaseAmountByDay;

class ItemContent extends BaseModel {
    use Activable, BelongsToCompany, ItemSalesAmount, ItemSalesAmountByAge, ItemSalesAmountByDay, ItemPurchaseAmountByAge, ItemPurchaseAmountByDay;
    use \App\Core\Travel\Traits\HasTravelAllotment;
    use \App\Core\Master\Traits\ItemTicket;
    
    protected $table = 'mstitemcontent';

    public function __get($key)
    {
        switch($key) {
            case "Title": return $this->Name.' - '.$this->Code;
        }
        return parent::__get($key);
    }

    public function CityObj() { return $this->belongsTo("App\Core\Master\Entities\City", "City", "Oid"); }
    public function ItemGroupObj() { return $this->belongsTo("App\Core\Master\Entities\ItemGroup", "ItemGroup", "Oid"); }
    public function ItemTypeObj() { return $this->belongsTo("App\Core\Internal\Entities\ItemType", "ItemType", "Oid"); }
    public function PurchaseBusinessPartnerObj() { return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "PurchaseBusinessPartner", "Oid"); }
    public function ItemAccountGroupObj() { return $this->belongsTo("App\Core\Master\Entities\ItemAccountGroup", "ItemAccountGroup", "Oid"); }
    public function PurchaseCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "PurchaseCurrency", "Oid"); }
    public function SalesCurrencyObj() { return $this->belongsTo("App\Core\Master\Entities\Currency", "SalesCurrency", "Oid"); }
    public function ETHCurrencyObj() { return $this->belongsTo("App\Core\Ethereum\Entities\ETHCurrency", "SalesCurrency", "Oid"); }

    public function SourceObj() { return $this->belongsTo("App\Core\Master\Entities\ItemContent", "ItemContentSource", "Oid"); }
    public function ItemStockReplacementObj() { return $this->belongsTo("App\Core\Master\Entities\Item", "ItemStockReplacement", "Oid"); }

    public function ItemPriceMethodObj() { return $this->hasOne("App\Core\Master\Entities\ItemPriceMethod", "Oid", "ItemPriceMethod"); }
    public function ItemAttractionObj() { return $this->hasOne("App\Core\Ferry\Entities\ItemAttraction", "Oid", "Oid"); }
    public function ItemTokenObj() {  return $this->hasOne("App\Core\Ethereum\Entities\ItemToken", "Oid", "Oid");  }
    public function TravelItemHotelObj() { return $this->hasOne("App\Core\Travel\Entities\TravelItemHotel", "Oid", "Oid"); }
    public function TravelItemObj() { return $this->hasOne("App\Core\Travel\Entities\TravelItem", "Oid", "Oid"); }
    public function TravelItemTransportObj() { return $this->hasOne("App\Core\Travel\Entities\TravelItemTransport", "Oid", "Oid"); }
    // public function ItemDetailObj() { return $this->hasOne("App\Core\Ferry\Entities\ItemDetail", "Oid", "Oid"); }
    
    public function PointOfSales() { return $this->belongsToMany("App\Core\POS\Entities\PointOfSale", "pospointofsaledetail", "Item", "PointOfSale"); }
    public function ItemGroups() { return $this->belongsToMany("App\Core\Master\Entities\ItemGroup", "mstitemitems_mstitemgroupgroups", "Items", "Groups"); }
    public function Collections() { return $this->belongsToMany("App\Core\POS\Entities\Collection", "poscollectioncollections_mstitemitems", "Items", "Collections"); }
    public function FeatureInfoDetailItems() { return $this->hasMany("App\Core\POS\Entities\FeatureInfoItem", "Item","Oid"); }
    public function DetailLinks() { return $this->hasMany("App\Core\Master\Entities\ItemDetailLink", "Parent", "Oid"); }
    public function ProductionItemObj() { return $this->hasOne("App\Core\Production\Entities\ProductionItem", "Oid", "Oid"); }
    public function ProductionItemGlassObj() { return $this->hasOne("App\Core\Production\Entities\ProductionItemGlass", "Oid", "Oid"); }
    public function ItemProcess() { return $this->hasMany("App\Core\Production\Entities\ProductionItemProcess", "Item","Oid"); }
    public function ItemECommerces() { return $this->hasMany("App\Core\Master\Entities\ItemECommerce", "Item","Oid"); }
    public function ItemCountries() { return $this->hasMany("App\Core\Master\Entities\ItemCountry", "Item","Oid"); }
    public function TravelItemOutboundObj() { return $this->hasOne("App\Core\Travel\Entities\TravelItemOutbound", "Oid", "Oid"); }
    public function Dates() { return $this->hasMany("App\Core\Travel\Entities\TravelItemDate", "Item","Oid"); }

    public function SalesAddMethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAddMethod", "Oid"); }
    public function SalesAdd1MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd1Method", "Oid"); }
    public function SalesAdd2MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd2Method", "Oid"); }
    public function SalesAdd3MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd3Method", "Oid"); }
    public function SalesAdd4MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd4Method", "Oid"); }
    public function SalesAdd5MethodObj() { return $this->belongsTo("App\Core\Internal\Entities\PriceMethod", "SalesAdd5Method", "Oid"); }
    public function Details() { return $this->hasMany('App\Core\Master\Entities\Item', 'ItemContent', 'Oid'); }
    public function AddOns() { return $this->hasMany('App\Core\Master\Entities\ItemAddOn', 'ItemContent', 'Oid'); }
    public function FeatureInfos() { return $this->hasMany('App\Core\POS\Entities\FeatureInfoItem', 'ItemContent', 'Oid'); }
}