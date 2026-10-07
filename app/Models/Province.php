<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    protected $fillable = ['name', 'island'];
}