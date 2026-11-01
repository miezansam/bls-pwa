<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Données réelles : valables dans tous les environnements, production comprise
        $this->call([
            RolesSeeder::class,
            TarifsOfficielsSeeder::class,
        ]);

        // Données fictives : jamais en production
        if (app()->environment('local')) {
            $this->call(DonneesDemoSeeder::class);
        }
    }
}
