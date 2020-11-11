<?php

namespace App\Core\Base\Entities;

use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Core\Base\Traits\HasGCRecord;
use App\Core\Base\Traits\HasAuthor;
use App\Core\Base\Traits\HasXPObjectType;

/**
 * @property string $Oid Object primary key
 */
abstract class BaseModel extends Model {

    use HasGCRecord, HasAuthor, HasXPObjectType;
    
    static $snakeAttributes = false;
    protected $primaryKey = 'Oid';
    public $incrementing = false;
    protected $guarded = [];
    protected $author = true;
    const CREATED_AT = 'CreatedAtUTC';
    const UPDATED_AT = 'UpdatedAtUTC';

    protected static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            if (!isset($model->Oid) && !$model->getIncrementing()) $model->Oid = Uuid::uuid4()->toString();
            if ($model->usesTimestamps()) {
                $model->CreatedAt = now()->addHours(company_timezone())->toDateTimeString();
                $model->UpdatedAt = now()->addHours(company_timezone())->toDateTimeString();
            }
        });
        self::updating(function ($model) {
            if ($model->usesTimestamps()) $model->UpdatedAt = now()->addHours(company_timezone())->toDateTimeString();
        });
    }
}