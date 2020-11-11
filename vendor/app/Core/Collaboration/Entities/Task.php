<?php

namespace App\Core\Collaboration\Entities;

use App\Core\Base\Entities\BaseModel;
use App\Core\Base\Traits\BelongsToCompany;

class Task extends BaseModel
{
    use BelongsToCompany;
    protected $table = 'coltask';

    public function UserFinalObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'UserFinal', 'Oid');
    }
    public function CompanyObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Company', 'Company', 'Oid');
    }
    public function CreatedByObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'CreatedBy', 'Oid');
    }
    public function UserObj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User', 'Oid');
    }
    public function User1Obj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User1', 'Oid');
    }
    public function ProjectObj()
    {
        return $this->belongsTo('App\Core\Master\Entities\Project', 'Project', 'Oid');
    }
    public function TaskReferenceObj()
    {
        return $this->belongsTo('App\Core\Collaboration\Entities\Task', 'TaskReference', 'Oid');
    }
    public function User2Obj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User2', 'Oid');
    }
    public function User3Obj()
    {
        return $this->belongsTo('App\Core\Security\Entities\User', 'User3', 'Oid');
    }



    public function Images()
    {
        return $this->hasMany('App\Core\Master\Entities\Image', 'PublicPost', 'Oid');
    }

    public function Files()
    {
        return $this->hasMany('App\Core\Pub\Entities\PublicFile', 'PublicPost', 'Oid');
    }

    public function Comments()
    {
        return $this->hasMany('App\Core\Pub\Entities\PublicComment', 'PublicPost', 'Oid');
    }
    public function Projects()
    {
        return $this->hasMany('App\Core\Collaboration\Entities\TaskProject', 'Task', 'Oid');
    }
    public function Logs()
    {
        return $this->hasMany('App\Core\Collaboration\Entities\TaskLog', 'Task', 'Oid');
    }
}
