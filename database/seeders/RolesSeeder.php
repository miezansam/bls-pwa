<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleUtilisateur;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleUtilisateur::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }
}
