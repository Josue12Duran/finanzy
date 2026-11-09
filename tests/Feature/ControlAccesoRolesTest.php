<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlAccesoRolesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function usuariocon(string $rol): User
    {
        $usuario = User::factory()->create();
        $usuario->assignRole($rol);

        return $usuario;
    }

    private const RUTAS_ADMIN = ['/usuarios', '/bitacora'];

    public function test_un_invitado_es_redirigido_al_login_en_rutas_administrativas(): void
    {
        foreach (self::RUTAS_ADMIN as $ruta) {
            $this->get($ruta)->assertRedirect('/login');
        }
    }

    public function test_un_empleado_recibe_403_en_rutas_administrativas(): void
    {
        $empleado = $this->usuariocon('Empleado');

        foreach (self::RUTAS_ADMIN as $ruta) {
            $this->actingAs($empleado)->get($ruta)->assertForbidden();
        }
    }

    public function test_un_usuario_sin_rol_recibe_403_en_rutas_administrativas(): void
    {
        $sinRol = User::factory()->create();

        foreach (self::RUTAS_ADMIN as $ruta) {
            $this->actingAs($sinRol)->get($ruta)->assertForbidden();
        }
    }

    public function test_un_administrador_accede_a_las_rutas_administrativas(): void
    {
        $administrador = $this->usuariocon('Administrador');

        foreach (self::RUTAS_ADMIN as $ruta) {
            $this->actingAs($administrador)->get($ruta)->assertOk();
        }
    }

    public function test_el_menu_muestra_usuarios_y_bitacora_solo_al_administrador(): void
    {
        $this->actingAs($this->usuariocon('Administrador'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Administración')
            ->assertSee('href="'.url('/usuarios').'"', false)
            ->assertSee('href="'.url('/bitacora').'"', false);
    }

    public function test_el_menu_oculta_usuarios_y_bitacora_al_empleado(): void
    {
        $this->actingAs($this->usuariocon('Empleado'))
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Administración')
            ->assertDontSee('href="'.url('/usuarios').'"', false)
            ->assertDontSee('href="'.url('/bitacora').'"', false);
    }

    public function test_el_menu_no_muestra_enlaces_administrativos_a_invitados(): void
    {
        $this->get('/demo')
            ->assertOk()
            ->assertDontSee('Administración')
            ->assertDontSee('href="'.url('/usuarios').'"', false)
            ->assertDontSee('href="'.url('/bitacora').'"', false);
    }
}
