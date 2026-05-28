<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    public const TYPE_SCREEN = 'screen';

    public const TYPE_ROUTE = 'route';

    protected $fillable = [
        'slug',
        'name',
        'type',
        'route_name',
        'screen_key',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission')->withTimestamps();
    }

    public function isScreenPermission(): bool
    {
        return $this->type === self::TYPE_SCREEN;
    }

    public function isRoutePermission(): bool
    {
        return $this->type === self::TYPE_ROUTE;
    }
}
