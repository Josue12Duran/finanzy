<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CopiaSeguridadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function usuarioConRol(string $rol): User
    {
        $usuario = User::factory()->create();
        $usuario->assignRole($rol);

        return $usuario;
    }

    public function test_solo_administradores_acceden_a_la_pantalla_y_descarga_de_respaldos(): void
    {
        $empleado = $this->usuarioConRol('Empleado');

        $this->get('/copias-seguridad')->assertRedirect('/login');
        $this->post('/copias-seguridad/descargar')->assertRedirect('/login');

        $this->actingAs($empleado)->get('/copias-seguridad')->assertForbidden();
        $this->actingAs($empleado)->post('/copias-seguridad/descargar')->assertForbidden();
        $this->actingAs($empleado)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('href="'.route('copias-seguridad.index').'"', false);
    }

    public function test_administrador_descarga_el_respaldo_y_la_accion_se_registra_en_bitacora(): void
    {
        $administrador = $this->usuarioConRol('Administrador');

        $this->actingAs($administrador)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('href="'.route('copias-seguridad.index').'"', false);

        $this->actingAs($administrador)
            ->get('/copias-seguridad')
            ->assertOk()
            ->assertSee('Generar y descargar copia');

        $rutaRespaldo = null;

        try {
            $respuesta = $this->actingAs($administrador)->post('/copias-seguridad/descargar');

            $respuesta->assertDownload();

            $rutaRespaldo = $respuesta->baseResponse->getFile()->getPathname();
            $contenido = file_get_contents($rutaRespaldo);

            $this->assertIsString($contenido);
            $this->assertStringContainsString('CREATE TABLE "users"', $contenido);
            $this->assertStringStartsWith(storage_path('app/private/backups').DIRECTORY_SEPARATOR, $rutaRespaldo);

            $baseRestaurada = new \PDO('sqlite::memory:');
            $baseRestaurada->exec($contenido);
            $consultaUsuario = $baseRestaurada->prepare('SELECT COUNT(*) FROM users WHERE id = ?');
            $consultaUsuario->execute([$administrador->id]);

            $this->assertSame(1, (int) $consultaUsuario->fetchColumn());

            $this->assertDatabaseHas('activity_logs', [
                'user_id' => $administrador->id,
                'action' => 'descargar',
                'module' => 'copias_seguridad',
            ]);
            $this->assertSame(
                1,
                ActivityLog::query()->where('user_id', $administrador->id)->where('module', 'copias_seguridad')->count(),
            );

            ob_start();
            $respuesta->baseResponse->sendContent();
            $contenidoDescargado = ob_get_clean();

            $this->assertSame($contenido, $contenidoDescargado);
            $this->assertFileDoesNotExist($rutaRespaldo);
        } finally {
            if ($rutaRespaldo !== null && File::exists($rutaRespaldo)) {
                File::delete($rutaRespaldo);
            }
        }
    }
}
