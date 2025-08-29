<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class AdminHierarchyLevel extends Model
{
    use LogsActivity, HasFactory, SoftDeletes;

    protected $table = 'admin_hierarchy_levels';
    protected $fillable = [
        'name',
        'code',
        'position',
        'created_by',
        'updated_by'
    ];
    protected $dates = ['deleted_at'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
    
}