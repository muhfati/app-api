<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AdminHierarchy extends Model
{
    use LogsActivity, HasFactory, SoftDeletes;

    protected $table = 'admin_hierarchies';
    protected $fillable = [
        'name',
        'code',
        'iso_code',
        'label',
        'parent_id',
        'admin_hierarchy_level_id',
        'created_by',
        'updated_by',
        'uuid'
    ];
    protected $dates = ['deleted_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }
    
}