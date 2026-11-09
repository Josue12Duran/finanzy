<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_evento_con_usuario_en_sesion_e_ip_del_cliente(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $this->app->instance('request', Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '10.1.2.3']));

        $registro = AuditLogger::log('crear', 'usuarios', 'Creó un usuario de prueba');

        $this->assertDatabaseHas('activity_logs', [
            'id' => $registro->id,
            'user_id' => $usuario->id,
            'action' => 'crear',
            'module' => 'usuarios',
            'description' => 'Creó un usuario de prueba',
            'ip_address' => '10.1.2.3',
        ]);
        $this->assertNotNull($registro->created_at);
        $this->assertTrue($registro->user->is($usuario));
    }

    public function test_user_id_queda_nulo_sin_sesion_y_la_descripcion_es_opcional(): void
    {
        $registro = AuditLogger::log('login_fallido', 'auth');

        $this->assertNull($registro->user_id);
        $this->assertNull($registro->description);
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_permite_indicar_el_usuario_explicitamente(): void
    {
        $usuario = User::factory()->create();

        $registro = AuditLogger::log('editar', 'usuarios', null, $usuario->id);

        $this->assertSame($usuario->id, $registro->user_id);
    }

    public function test_acciones_y_modulos_largos_se_truncan_a_100_caracteres(): void
    {
        $largo = str_repeat('a', 150);

        $registro = AuditLogger::log($largo, $largo);

        $this->assertSame(100, mb_strlen($registro->action));
        $this->assertSame(100, mb_strlen($registro->module));
    }

    public function test_el_modelo_se_relaciona_con_el_usuario_y_no_tiene_updated_at(): void
    {
        $usuario = User::factory()->create();
        $registro = ActivityLog::create([
            'user_id' => $usuario->id,
            'action' => 'ver',
            'module' => 'bitacora',
        ]);

        $this->assertTrue($registro->user->is($usuario));
        $this->assertNull(ActivityLog::UPDATED_AT);
    }
}
