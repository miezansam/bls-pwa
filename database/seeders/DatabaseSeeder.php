<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        // Les zones et prix d'exemple ne doivent jamais arriver en production
        if (app()->environment(['local', 'testing'])) {
            $this->call(ZonesEtTarifsSeeder::class);
        }
    }
}
