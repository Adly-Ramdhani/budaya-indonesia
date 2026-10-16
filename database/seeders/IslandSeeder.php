<?php

namespace Database\Seeders;

use App\Models\Island;
use Illuminate\Database\Seeder;

class IslandSeeder extends Seeder
{
    public function run(): void
    {
        $islands = [
            'Sumatra',
            'Jawa',
            'Kalimantan',
            'Sulawesi',
            'Papua',
            'Bali dan Nusa Tenggara',
            'Maluku',
        ];

        foreach ($islands as $name) {
            Island::firstOrCreate(['name' => $name]);
        }
    }
}