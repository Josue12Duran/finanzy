<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GestionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'ClaveSegura123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function usuarioCon(string $rol, array $atributos = []): User
    {
        $usuario = User::factory()->create($atributos);
        $usuario->assignRole($rol);

        return $usuario;
    }

    private function datosValidos(array $sobrescribir = []): array
    {
        return array_merge([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.com',
            'password' => self::CLAVE,
            'password_confirmation' => self::CLAVE,
            'rol' => 'Empleado',
        ], $sobrescribir);
    }

    public function test_solo_el_administrador_accede_a_la_gestion_de_usuarios(): void
    {
        $empleado = $this->usuarioCon('Empleado');
        $objetivo = User::factory()->create();

        $this->actingAs($empleado)->get('/usuarios')->assertForbidden();
        $this->actingAs($empleado)->get('/usuarios/create')->assertForbidden();
        $this->actingAs($empleado)->post('/usuarios', $this->datosValidos())->assertForbidden();
        $this->actingAs($empleado)->get("/usuarios/{$objetivo->id}/edit")->assertForbidden();
        $this->actingAs($empleado)->put("/usuarios/{$objetivo->id}", $this->datosValidos())->assertForbidden();
        $this->actingAs($empleado)->patch("/usuarios/{$objetivo->id}/estado")->assertForbidden();

        $this->assertTrue($objetivo->fresh()->activo);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_index_lista_usuarios_paginados_con_rol_y_estado(): void
    {
        $administrador = $this->usuarioCon('Administrador', ['name' => 'Admin Principal']);
        User::factory(12)->create()->each(fn (User $u) => $u->assignRole('Empleado'));
        User::factory()->create(['name' => 'Inactivo Demo', 'activo' => false])->assignRole('Empleado');

        $this->actingAs($administrador)
            ->get('/usuarios')
            ->assertOk()
            ->assertViewHas('usuarios', fn ($usuarios) => $usuarios->count() === 10 && $usuarios->total() === 14)
            ->assertSee('Administrador')
            ->assertSee('Empleado');
    }

    public function test_index_muestra_estado_de_cada_usuario(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $inactivo = User::factory()->create(['name' => 'Inactivo Demo', 'activo' => false]);
        $inactivo->assignRole('Empleado');

        $this->actingAs($administrador)
            ->get('/usuarios')
            ->assertOk()
            ->assertSee('Inactivo Demo')
            ->assertSee('Inactivo')
            ->assertSee('Activo')
            ->assertSee("modalEstado-{$inactivo->id}")
            ->assertDontSee('confirm(', false);
    }

    public function test_crear_usuario_hashea_la_contrasena_asigna_rol_y_registra_bitacora(): void
    {
        $administrador = $this->usuarioCon('Administrador');

        $this->actingAs($administrador)
            ->post('/usuarios', $this->datosValidos())
            ->assertRedirect('/usuarios')
            ->assertSessionHas('success');

        $nuevo = User::where('email', 'ana@example.com')->firstOrFail();

        $this->assertSame('Ana Pérez', $nuevo->name);
        $this->assertTrue($nuevo->activo);
        $this->assertTrue($nuevo->hasRole('Empleado'));
        $this->assertTrue(Hash::check(self::CLAVE, $nuevo->password), 'La contraseña debe guardarse con hash válido.');
        $this->assertStringNotContainsString(self::CLAVE, $nuevo->password);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $administrador->id,
            'action' => 'crear',
            'module' => 'usuarios',
            'description' => 'Creó al usuario ana@example.com con rol Empleado',
        ]);
    }

    public function test_crear_usuario_con_rol_administrador(): void
    {
        $administrador = $this->usuarioCon('Administrador');

        $this->actingAs($administrador)
            ->post('/usuarios', $this->datosValidos(['rol' => 'Administrador', 'email' => 'otro@example.com']))
            ->assertRedirect('/usuarios');

        $this->assertTrue(User::where('email', 'otro@example.com')->firstOrFail()->hasRole('Administrador'));
    }

    public function test_crear_usuario_valida_los_datos(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        User::factory()->create(['email' => 'repetido@example.com']);

        $this->actingAs($administrador)
            ->from('/usuarios/create')
            ->post('/usuarios', $this->datosValidos([
                'email' => 'repetido@example.com',
                'password_confirmation' => 'otra-distinta',
                'rol' => 'Gerente',
            ]))
            ->assertRedirect('/usuarios/create')
            ->assertSessionHasErrors(['email', 'password', 'rol']);

        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_editar_actualiza_datos_y_rol_y_registra_bitacora(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $usuario = $this->usuarioCon('Empleado', ['email' => 'viejo@example.com']);

        $this->actingAs($administrador)
            ->put("/usuarios/{$usuario->id}", $this->datosValidos([
                'name' => 'Nombre Nuevo',
                'email' => 'nuevo@example.com',
                'rol' => 'Administrador',
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect('/usuarios');

        $usuario->refresh();

        $this->assertSame('Nombre Nuevo', $usuario->name);
        $this->assertSame('nuevo@example.com', $usuario->email);
        $this->assertTrue($usuario->hasRole('Administrador'));
        $this->assertFalse($usuario->hasRole('Empleado'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $administrador->id,
            'action' => 'editar',
            'module' => 'usuarios',
            'description' => 'Editó al usuario nuevo@example.com; rol: Empleado → Administrador',
        ]);
    }

    public function test_editar_sin_contrasena_conserva_la_actual(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $usuario = $this->usuarioCon('Empleado', ['password' => self::CLAVE]);
        $hashAnterior = $usuario->password;

        $this->actingAs($administrador)
            ->put("/usuarios/{$usuario->id}", $this->datosValidos([
                'email' => $usuario->email,
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertRedirect('/usuarios');

        $this->assertSame($hashAnterior, $usuario->fresh()->password);
    }

    public function test_editar_con_nueva_contrasena_la_reemplaza_con_hash(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $usuario = $this->usuarioCon('Empleado');

        $this->actingAs($administrador)
            ->put("/usuarios/{$usuario->id}", $this->datosValidos([
                'email' => $usuario->email,
                'password' => 'NuevaClave456',
                'password_confirmation' => 'NuevaClave456',
            ]))
            ->assertRedirect('/usuarios');

        $this->assertTrue(Hash::check('NuevaClave456', $usuario->fresh()->password));
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'editar',
            'description' => "Editó al usuario {$usuario->email}; cambió la contraseña",
        ]);
    }

    public function test_editar_permite_conservar_el_mismo_correo(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $usuario = $this->usuarioCon('Empleado', ['email' => 'propio@example.com']);

        $this->actingAs($administrador)
            ->put("/usuarios/{$usuario->id}", $this->datosValidos(['email' => 'propio@example.com', 'password' => '', 'password_confirmation' => '']))
            ->assertSessionHasNoErrors();
    }

    public function test_administrador_no_puede_quitarse_su_propio_rol(): void
    {
        $administrador = $this->usuarioCon('Administrador');

        $this->actingAs($administrador)
            ->from("/usuarios/{$administrador->id}/edit")
            ->put("/usuarios/{$administrador->id}", $this->datosValidos([
                'email' => $administrador->email,
                'rol' => 'Empleado',
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasErrors('rol');

        $this->assertTrue($administrador->fresh()->hasRole('Administrador'));
    }

    public function test_toggle_desactiva_y_reactiva_usuario_registrando_bitacora(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $usuario = $this->usuarioCon('Empleado');

        $this->actingAs($administrador)
            ->patch("/usuarios/{$usuario->id}/estado")
            ->assertRedirect();

        $this->assertFalse($usuario->fresh()->activo);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $administrador->id,
            'action' => 'desactivar',
            'module' => 'usuarios',
            'description' => "Desactivó al usuario {$usuario->email}",
        ]);

        $this->actingAs($administrador)
            ->patch("/usuarios/{$usuario->id}/estado")
            ->assertRedirect();

        $this->assertTrue($usuario->fresh()->activo);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $administrador->id,
            'action' => 'activar',
            'module' => 'usuarios',
            'description' => "Activó al usuario {$usuario->email}",
        ]);
        $this->assertDatabaseCount('activity_logs', 2);
    }

    public function test_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $administrador = $this->usuarioCon('Administrador');

        $this->actingAs($administrador)
            ->patch("/usuarios/{$administrador->id}/estado")
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertTrue($administrador->fresh()->activo);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE, 'activo' => false]);

        $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => self::CLAVE,
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Tu cuenta está desactivada. Contacta a un administrador.']);

        $this->assertGuest();
    }

    public function test_usuario_desactivado_con_sesion_abierta_es_expulsado(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE]);

        $this->post('/login', ['email' => $usuario->email, 'password' => self::CLAVE])
            ->assertRedirect('/dashboard');

        User::whereKey($usuario->id)->update(['activo' => false]);
        $this->app['auth']->forgetGuards();

        $this->get('/dashboard')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_formulario_de_edicion_carga_los_datos_del_usuario(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $usuario = $this->usuarioCon('Empleado', ['name' => 'Carlos Ruiz', 'email' => 'carlos@example.com']);

        $this->actingAs($administrador)
            ->get("/usuarios/{$usuario->id}/edit")
            ->assertOk()
            ->assertSee('Carlos Ruiz')
            ->assertSee('carlos@example.com');
    }

    public function test_bitacora_es_la_fuente_de_los_registros_de_usuarios(): void
    {
        $administrador = $this->usuarioCon('Administrador');

        $this->actingAs($administrador)->post('/usuarios', $this->datosValidos());

        $this->assertSame(1, ActivityLog::where('module', 'usuarios')->count());
    }
}
