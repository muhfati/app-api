<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AdminHierarchyLevel extends Model
{
    use LogsActivity, HasFactory, SoftDeletes;

    protected $table = 'admin_hierarchy_levels';
    protected $fillable = [
        'name',
        'code',
        'position',
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