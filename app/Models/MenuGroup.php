<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MenuGroup extends Model
{
    use LogsActivity, HasFactory, SoftDeletes;

    protected $table = 'menu_groups';
    protected $fillable = [
        'name',
        'sw_name',
        'icon',
        'sort_order',
        'created_by',
        'updated_by',
        'uuid'
    ];
    protected $dates = ['deleted_at'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['*']);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
    
}