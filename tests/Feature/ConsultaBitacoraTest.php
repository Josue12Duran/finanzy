<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConsultaBitacoraTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function administrador(): User
    {
        $administrador = User::factory()->create();
        $administrador->assignRole('Administrador');

        return $administrador;
    }

    private function registrar(?User $usuario, string $modulo, string $accion, string $descripcion, ?Carbon $momento = null): ActivityLog
    {
        if ($momento) {
            $this->travelTo($momento);
        }

        return ActivityLog::create([
            'user_id' => $usuario?->id,
            'action' => $accion,
            'module' => $modulo,
            'description' => $descripcion,
            'ip_address' => '127.0.0.1',
        ]);
    }

    public function test_muestra_los_registros_mas_recientes_primero_con_paginacion(): void
    {
        $admin = $this->administrador();

        for ($i = 1; $i <= 20; $i++) {
            $this->registrar($admin, 'usuarios', 'editar', 'Evento '.str_pad((string) $i, 2, '0', STR_PAD_LEFT), now()->addMinutes($i));
        }

        $this->actingAs($admin)
            ->get('/bitacora')
            ->assertOk()
            ->assertSee('Evento 20')
            ->assertDontSee('Evento 01')
            ->assertSee('Mostrando 1 a 15 de 20 registros');

        $this->actingAs($admin)
            ->get('/bitacora?page=2')
            ->assertOk()
            ->assertSee('Evento 01')
            ->assertDontSee('Evento 20');
    }

    public function test_filtra_por_usuario(): void
    {
        $admin = $this->administrador();
        $otro = User::factory()->create(['name' => 'Usuario Filtrado']);

        $this->registrar($admin, 'usuarios', 'crear', 'Acción del administrador');
        $this->registrar($otro, 'usuarios', 'crear', 'Acción del otro usuario');

        $this->actingAs($admin)
            ->get('/bitacora?user_id='.$otro->id)
            ->assertOk()
            ->assertSee('Acción del otro usuario')
            ->assertDontSee('Acción del administrador');
    }

    public function test_filtra_por_modulo(): void
    {
        $admin = $this->administrador();

        $this->registrar($admin, 'usuarios', 'crear', 'Evento de usuarios');
        $this->registrar($admin, 'auth', 'login_fallido', 'Evento de autenticación');

        $this->actingAs($admin)
            ->get('/bitacora?modulo=auth')
            ->assertOk()
            ->assertSee('Evento de autenticación')
            ->assertDontSee('Evento de usuarios');
    }

    public function test_filtra_por_rango_de_fechas_incluyendo_el_dia_final_completo(): void
    {
        $admin = $this->administrador();

        $this->registrar($admin, 'usuarios', 'crear', 'Antes del rango', Carbon::parse('2026-11-01 10:00:00'));
        $this->registrar($admin, 'usuarios', 'crear', 'Inicio del rango', Carbon::parse('2026-11-05 00:00:01'));
        $this->registrar($admin, 'usuarios', 'crear', 'Fin del rango', Carbon::parse('2026-11-07 23:59:59'));
        $this->registrar($admin, 'usuarios', 'crear', 'Despues del rango', Carbon::parse('2026-11-08 00:00:00'));
        $this->travelBack();

        $this->actingAs($admin)
            ->get('/bitacora?fecha_inicio=2026-11-05&fecha_fin=2026-11-07')
            ->assertOk()
            ->assertSee('Inicio del rango')
            ->assertSee('Fin del rango')
            ->assertDontSee('Antes del rango')
            ->assertDontSee('Despues del rango');
    }

    public function test_combina_varios_filtros(): void
    {
        $admin = $this->administrador();
        $otro = User::factory()->create();

        $this->registrar($admin, 'usuarios', 'crear', 'Coincide todo');
        $this->registrar($otro, 'usuarios', 'crear', 'Otro usuario mismo modulo');
        $this->registrar($admin, 'auth', 'login_fallido', 'Mismo usuario otro modulo');

        $this->actingAs($admin)
            ->get('/bitacora?user_id='.$admin->id.'&modulo=usuarios')
            ->assertOk()
            ->assertSee('Coincide todo')
            ->assertDontSee('Otro usuario mismo modulo')
            ->assertDontSee('Mismo usuario otro modulo');
    }

    public function test_rechaza_un_rango_de_fechas_invertido(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)
            ->from('/bitacora')
            ->get('/bitacora?fecha_inicio=2026-11-10&fecha_fin=2026-11-01')
            ->assertRedirect('/bitacora')
            ->assertSessionHasErrors('fecha_fin');
    }

    public function test_los_registros_sin_usuario_se_muestran_como_sin_usuario(): void
    {
        $admin = $this->administrador();

        $this->registrar(null, 'auth', 'login_fallido', 'Intento sin usuario asociado');

        $this->actingAs($admin)
            ->get('/bitacora')
            ->assertOk()
            ->assertSee('Sin usuario')
            ->assertSee('Intento sin usuario asociado');
    }
}
