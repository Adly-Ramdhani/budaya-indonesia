<?php

namespace App\Models;

use App\Models\Food;
use App\Models\TraditionalWeapon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    public const ISLANDS = [
        'Sumatra',
        'Jawa',
        'Bali',
        'Nusa Tenggara',
        'Kalimantan',
        'Sulawesi',
        'Maluku',
        'Papua',
    ];

    protected $fillable = ['name', 'island_id'];
    public function island(): BelongsTo
    {
        return $this->belongsTo(Island::class);
    }

     public function foods(): HasMany
    {
        return $this->hasMany(Food::class);
    }

    public function traditionalHouses(): HasMany
    {
        return $this->hasMany(TraditionalHouse::class);
    }

    public function traditionalAttires(): HasMany
    {
        return $this->hasMany(TraditionalAttire::class);
    }

     public function traditionalWeapons(): HasMany
    {
        return $this->hasMany(TraditionalWeapon::class);
    }
}