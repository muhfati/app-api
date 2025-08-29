<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Permission;
use Spatie\Activitylog\LogOptions;

class MenuItem extends Model
{
    use LogsActivity, HasFactory, SoftDeletes;

    protected $table = 'menu_items';
    protected $fillable = [
        'menu_group_id',
        'name',
        'sw_name',
        'url',
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
    
    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'menu_item_permission', 
            'menu_item_id',      
            'permission_id'         
        );
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}