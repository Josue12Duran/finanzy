<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_crea_los_roles_administrador_y_empleado(): void
    {
        $this->seed(RoleSeeder::class);

        $this->assertDatabaseHas('roles', ['name' => 'Administrador', 'guard_name' => 'web']);
        $this->assertDatabaseHas('roles', ['name' => 'Empleado', 'guard_name' => 'web']);
    }

    public function test_el_seeder_de_roles_es_idempotente(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->assertSame(2, Role::count());
    }

    public function test_un_usuario_puede_tener_el_rol_administrador(): void
    {
        $this->seed(RoleSeeder::class);

        $usuario = User::factory()->create();
        $usuario->assignRole('Administrador');

        $this->assertTrue($usuario->hasRole('Administrador'));
        $this->assertFalse($usuario->hasRole('Empleado'));
    }

    public function test_el_navbar_muestra_la_etiqueta_del_rol_administrador(): void
    {
        $this->seed(RoleSeeder::class);

        $usuario = User::factory()->create();
        $usuario->assignRole('Administrador');

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Administrador');
    }

    public function test_el_navbar_muestra_la_etiqueta_del_rol_empleado(): void
    {
        $this->seed(RoleSeeder::class);

        $usuario = User::factory()->create();
        $usuario->assignRole('Empleado');

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Empleado');
    }
}
