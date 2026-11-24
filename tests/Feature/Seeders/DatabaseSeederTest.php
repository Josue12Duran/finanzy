<?php

namespace Tests\Feature\Seeders;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_repetir_el_seeder_conserva_un_solo_administrador_y_su_asignacion_de_rol(): void
    {
        $this->freezeTime();
        $this->seed(DatabaseSeeder::class);
        $administrador = User::where('email', 'administrador@finanzy.com')->firstOrFail();
        $datosOriginales = $administrador->getRawOriginal();
        $this->travel(1)->day();

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $administrador->id,
            'email' => 'administrador@finanzy.com',
            'name' => 'Finanzy',
            'activo' => true,
        ]);
        $this->assertSame($datosOriginales, $administrador->fresh()->getRawOriginal());
        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseHas('roles', ['name' => 'Administrador', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Empleado', 'guard_name' => 'web']);
        $this->assertDatabaseCount('model_has_roles', 1);
        $this->assertSame(['Administrador'], $administrador->fresh()->getRoleNames()->all());
    }

    public function test_el_seeder_completa_el_rol_sin_sobrescribir_usuarios_existentes(): void
    {
        $this->freezeTime();
        $this->seed(RoleSeeder::class);
        $administrador = User::factory()->unverified()->create([
            'email' => 'administrador@finanzy.com',
            'name' => 'Administrador personalizado',
            'password' => 'ClavePersonalizadaDePrueba#5',
            'activo' => false,
        ]);
        $administrador->assignRole('Empleado');
        $otroUsuario = User::factory()->create();
        $datosAdministrador = $administrador->refresh()->getRawOriginal();
        $datosOtroUsuario = $otroUsuario->refresh()->getRawOriginal();
        $this->travel(1)->day();

        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertSame($datosAdministrador, $administrador->fresh()->getRawOriginal());
        $this->assertSame($datosOtroUsuario, $otroUsuario->fresh()->getRawOriginal());
        $this->assertDatabaseCount('roles', 2);
        $this->assertDatabaseCount('model_has_roles', 2);
        $this->assertSame(
            ['Administrador', 'Empleado'],
            $administrador->fresh()->getRoleNames()->sort()->values()->all(),
        );
        $this->assertSame([], $otroUsuario->fresh()->getRoleNames()->all());
    }
}
