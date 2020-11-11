<?php

namespace App\Core\POS\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;
use App\Core\Master\Entities\Item;

class ETicket extends BaseModel {
    use BelongsToCompany;
    protected $table = 'poseticket';

    public function __get($key)
    {
        switch ($key) {
            case "Description": {
                if (isset($this->BusinessPartner)) {
                    return $this->BusinessPartnerObj->Name;
                }
                if (isset($this->ItemObj)) {
                    return $this->ItemObj->Name;
                }
                if (isset($this->TravelTransactionDetailObj)) {
                    return $this->TravelTransactionDetailObj->ItemObj->Name;
                }
            }
        }
        return parent::__get($key);
    }

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $pos = $model->PointOfSaleObj;
            if (!isset($model->FileName) && empty($model->URL)) {
                $name = $model->Oid;
                if (isset($pos)) $name .= "_{$pos->Oid}_{$pos->Code}";
                $name .= "_".str_random(16);
                $model->FileName = "{$name}.pdf";
            }
            if (!isset($model->Key)) {
                $model->Key = $model->generateKey();
            }
            if (!isset($model->URL)) {
                $model->URL = $model->getEncryptedURL();
            }
            if (!isset($model->Code)) {
                $code = '';
                if (isset($model->Item)) {
                    $item = Item::find($model->Item);
                    if (!is_null($item->PurchaseBusinessPartner)) {
                        $code .= $item->PurchaseBusinessPartnerObj->CodePrefix;
                    }
                    if($code == '') $code = $item->PurchaseBusinessPartner ? $item->PurchaseBusinessPartnerObj->Code : 'ETK';
                    if (!is_null($item->ParentOid)) {
                        $code .= $item->ItemContentObj->Code;
                    } else {
                        $code .= $item->Code;
                    }
                    $code .= str_pad(static::where('Item', $model->Item)->count() + 1, 4, '0', STR_PAD_LEFT);
                    if (!is_null($item->PurchaseBusinessPartner)) {
                        $code .= $item->PurchaseBusinessPartnerObj->CodeSuffix;
                    }
                } else {
                    if (isset($pos)) {
                        $code = $pos->Code;
                    } else {
                        $code .= str_random(3);
                    }
                    $code .= str_pad(static::where('Item', $model->Item)->count() + 1, 4, '0', STR_PAD_LEFT);
                }
                $model->Code = strtoupper($code);
            }
        });
    }

    /**
     * Generate new key
     * 
     * @return string
     */
    public function generateKey()
    {
        $key = $this->Oid;
        $pos = $this->PointOfSaleObj;
        if (isset($pos)) $key .="_{$pos->Oid}_{$pos->Code}";
        return encrypt($key);
    }

    /**
     * Update eticket key
     * 
     * @return void
     */
    public function updateKey()
    {
        $this->Key = $this->generateKey();
        if (isset($this->PointOfSaleObj)) $this->URL = $this->getEncryptedURL(); 
        $this->save();
    }

    /**
     * Get encrypted url
     * 
     * @return void
     */
    public function getEncryptedURL()
    {
        return route('Core\POS::eticket', [ 'key' => $this->Key ]);
    }

    /**
     * Get the POS of the ticket
     */
    public function PointOfSaleObj()
    {
        return $this->belongsTo("App\Core\POS\Entities\PointOfSale", "PointOfSale", "Oid");
    }

    public function BusinessPartnerObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\BusinessPartner", "BusinessPartner", "Oid");
    }

    public function TravelTransactionDetailObj()
    {
        return $this->belongsTo("App\Core\Travel\Entities\TravelTransactionDetail", "TravelTransactionDetail", "Oid");
    }

    /**
     * Scope available eticket
     */
    public function scopeAvailable($query)
    {
        $query->whereNull('PointOfSale')
        ->where(function ($q) {
            $q->whereNull('DateValidFrom')
            ->orWhere('DateValidFrom', '<=', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->where(function ($q1) {
            $q1->whereNull('DateExpiry')
            ->orWhere('DateExpiry', '>', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->orderByRaw("IFNULL(DateExpiry,'3000-01-01'), Code");
    }
    public function scopeAvailableAdult($query)
    {
        $query->whereNull('PointOfSale')->where('Type','Adult')
        ->where(function ($q) {
            $q->whereNull('DateValidFrom')
            ->orWhere('DateValidFrom', '<=', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->where(function ($q1) {
            $q1->whereNull('DateExpiry')
            ->orWhere('DateExpiry', '>', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->orderByRaw("IFNULL(DateExpiry,'3000-01-01'), Code");
    }
    public function scopeAvailableChild($query)
    {
        $query->whereNull('PointOfSale')->where('Type','Child')
        ->where(function ($q) {
            $q->whereNull('DateValidFrom')
            ->orWhere('DateValidFrom', '<=', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->where(function ($q1) {
            $q1->whereNull('DateExpiry')
            ->orWhere('DateExpiry', '>', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->orderByRaw("IFNULL(DateExpiry,'3000-01-01'), Code");
    }
    public function scopeAvailableInfant($query)
    {
        $query->whereNull('PointOfSale')->where('Type','Infant')
        ->where(function ($q) {
            $q->whereNull('DateValidFrom')
            ->orWhere('DateValidFrom', '<=', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->where(function ($q1) {
            $q1->whereNull('DateExpiry')
            ->orWhere('DateExpiry', '>', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->orderByRaw("IFNULL(DateExpiry,'3000-01-01'), Code");
    }

    public function scopeAvailableSenior($query)
    {
        $query->whereNull('PointOfSale')->where('Type','Senior')
        ->where(function ($q) {
            $q->whereNull('DateValidFrom')
            ->orWhere('DateValidFrom', '<=', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->where(function ($q1) {
            $q1->whereNull('DateExpiry')
            ->orWhere('DateExpiry', '>', now()->addHours(company_timezone())->toDateTimeString());
        })
        ->orderByRaw("IFNULL(DateExpiry,'3000-01-01'), Code");
    }

    public function ETicketUploadObj()
    {
        return $this->belongsTo("App\Core\POS\Entities\POSETicketUpload", "ETicketUpload", "Oid");
    }
    
    public function ItemObj()
    {
        return $this->belongsTo("App\Core\Master\Entities\Item", "Item", "Oid");
    }

    public function PurchaseInvoiceObj()
    {
        return $this->belongsTo("App\Core\Trading\Entities\PurchaseInvoice", "PurchaseInvoice", "Oid");
    }
}