<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        // User::factory(10)->create();

        $administrador = User::firstOrCreate(
            ['email' => 'administrador@finanzy.com'],
            [
                'name' => 'Finanzy',
                'password' => 'AdminFinanzy#0',
                'email_verified_at' => now(),
            ],
        );

        $administrador->assignRole('Administrador');
    }
}
