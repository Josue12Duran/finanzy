<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Crea los roles de la aplicación de forma idempotente.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['Administrador', 'Empleado'] as $nombre) {
            Role::findOrCreate($nombre, 'web');
        }
    }
}
