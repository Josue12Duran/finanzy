<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SendResetLinkRequest;
use App\Http\Requests\StoreDemoRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\FiltrarBitacoraRequest;
use App\Http\Requests\FiltrarUsuariosRequest;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Auditoría de seguridad (Hito 8): acceso por rol, cuentas desactivadas,
 * protección CSRF, hashing de contraseñas y validación de entradas.
 */
class AuditoriaSeguridadTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'ClaveSegura#2026';

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

    /**
     * Devuelve el número de formularios no GET de la página y los que no incluyen _token.
     *
     * @return array{total: int, sin_token: array<int, string>}
     */
    private function auditarCsrfEnHtml(string $html): array
    {
        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $total = 0;
        $sinToken = [];

        foreach ($xpath->query('//form') as $formulario) {
            $metodo = strtoupper($formulario->getAttribute('method') ?: 'GET');

            if ($metodo === 'GET') {
                continue;
            }

            $total++;

            if ($xpath->query('.//input[@name="_token"]', $formulario)->length === 0) {
                $sinToken[] = $formulario->getAttribute('action');
            }
        }

        return ['total' => $total, 'sin_token' => $sinToken];
    }

    // ------------------------------------------------------------------
    // 1. Pruebas de acceso
    // ------------------------------------------------------------------

    public function test_invitado_es_redirigido_al_login_en_todas_las_rutas_protegidas(): void
    {
        $objetivo = $this->usuarioCon('Empleado');

        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/usuarios')->assertRedirect('/login');
        $this->get('/usuarios/create')->assertRedirect('/login');
        $this->get("/usuarios/{$objetivo->id}/edit")->assertRedirect('/login');
        $this->get('/bitacora')->assertRedirect('/login');

        $this->post('/usuarios', [])->assertRedirect('/login');
        $this->put("/usuarios/{$objetivo->id}", [])->assertRedirect('/login');
        $this->patch("/usuarios/{$objetivo->id}/estado")->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_empleado_recibe_403_al_acceder_por_url_a_usuarios_y_bitacora(): void
    {
        $empleado = $this->usuarioCon('Empleado');
        $objetivo = $this->usuarioCon('Empleado');

        $this->actingAs($empleado);

        $this->get('/usuarios')->assertForbidden();
        $this->get('/usuarios/create')->assertForbidden();
        $this->get("/usuarios/{$objetivo->id}/edit")->assertForbidden();
        $this->get('/bitacora')->assertForbidden();
    }

    public function test_empleado_no_puede_ejecutar_acciones_de_usuarios_aunque_conozca_la_ruta(): void
    {
        $empleado = $this->usuarioCon('Empleado');
        $objetivo = $this->usuarioCon('Empleado', ['activo' => true]);

        $this->actingAs($empleado);

        $this->post('/usuarios', [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => self::CLAVE,
            'password_confirmation' => self::CLAVE,
            'rol' => 'Administrador',
        ])->assertForbidden();

        $this->put("/usuarios/{$objetivo->id}", [
            'name' => 'Modificado',
            'email' => $objetivo->email,
            'rol' => 'Administrador',
        ])->assertForbidden();

        $this->patch("/usuarios/{$objetivo->id}/estado")->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
        $this->assertDatabaseHas('users', ['id' => $objetivo->id, 'name' => $objetivo->name, 'activo' => true]);
    }

    public function test_administrador_accede_a_usuarios_y_bitacora(): void
    {
        $administrador = $this->usuarioCon('Administrador');

        $this->actingAs($administrador);

        $this->get('/usuarios')->assertOk();
        $this->get('/usuarios/create')->assertOk();
        $this->get('/bitacora')->assertOk();
    }

    public function test_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE, 'activo' => false]);

        $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => self::CLAVE,
            'remember' => 1,
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertFalse(Auth::check());

        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_usuario_desactivado_no_puede_usar_rutas_administrativas(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $desactivado = $this->usuarioCon('Administrador', ['activo' => false]);

        $this->actingAs($desactivado)->get('/usuarios')->assertRedirect('/login');
        $this->actingAs($desactivado)->get('/bitacora')->assertRedirect('/login');

        $this->assertTrue($administrador->fresh()->activo);
    }

    // ------------------------------------------------------------------
    // 2. Protección CSRF en formularios
    // ------------------------------------------------------------------

    public function test_rutas_web_incluyen_csrf_y_control_de_usuarios_activos(): void
    {
        // El stack de middleware del grupo 'web' se resuelve al procesar una petición.
        $this->get('/login');

        $grupoWeb = app('router')->getMiddlewareGroups()['web'] ?? [];

        $this->assertContains(PreventRequestForgery::class, $grupoWeb);
        $this->assertContains(EnsureUserIsActive::class, $grupoWeb);
    }

    public function test_formularios_de_invitados_incluyen_csrf(): void
    {
        $paginas = [
            '/login' => 1,
            '/register' => 1,
            '/password/reset' => 1,
            '/password/reset/token-de-prueba' => 1,
        ];

        foreach ($paginas as $url => $minimo) {
            $auditoria = $this->auditarCsrfEnHtml($this->get($url)->assertOk()->getContent());

            $this->assertGreaterThanOrEqual($minimo, $auditoria['total'], "Se esperaban formularios POST en {$url}");
            $this->assertSame([], $auditoria['sin_token'], "Formularios sin @csrf en {$url}");
        }
    }

    public function test_formularios_autenticados_incluyen_csrf(): void
    {
        $administrador = $this->usuarioCon('Administrador');
        $empleado = $this->usuarioCon('Empleado', ['name' => 'Empleado Demo']);

        $this->actingAs($administrador);

        $paginas = [
            '/dashboard' => 1,          // formulario de logout en la barra superior
            '/demo' => 1,               // modal de registro
            '/usuarios/create' => 1,
            "/usuarios/{$empleado->id}/edit" => 1,
            '/usuarios' => 2,           // modales de activar/desactivar por usuario
        ];

        foreach ($paginas as $url => $minimo) {
            $auditoria = $this->auditarCsrfEnHtml($this->get($url)->assertOk()->getContent());

            $this->assertGreaterThanOrEqual($minimo, $auditoria['total'], "Se esperaban formularios POST en {$url}");
            $this->assertSame([], $auditoria['sin_token'], "Formularios sin @csrf en {$url}");
        }
    }

    // ------------------------------------------------------------------
    // 3. Hash de contraseñas
    // ------------------------------------------------------------------

    public function test_contrasena_de_usuario_creado_por_administrador_se_guarda_con_hash(): void
    {
        $this->actingAs($this->usuarioCon('Administrador'))->post('/usuarios', [
            'name' => 'Nuevo Empleado',
            'email' => 'nuevo@example.com',
            'password' => self::CLAVE,
            'password_confirmation' => self::CLAVE,
            'rol' => 'Empleado',
        ])->assertRedirect('/usuarios');

        $usuario = User::where('email', 'nuevo@example.com')->firstOrFail();

        $this->assertNotSame(self::CLAVE, $usuario->password);
        $this->assertSame('bcrypt', password_get_info($usuario->password)['algoName']);
        $this->assertTrue(Hash::check(self::CLAVE, $usuario->password));
    }

    public function test_contrasena_actualizada_por_administrador_se_guarda_con_hash(): void
    {
        $objetivo = $this->usuarioCon('Empleado');
        $nueva = 'OtraClave#2026';

        $this->actingAs($this->usuarioCon('Administrador'))->put("/usuarios/{$objetivo->id}", [
            'name' => $objetivo->name,
            'email' => $objetivo->email,
            'password' => $nueva,
            'password_confirmation' => $nueva,
            'rol' => 'Empleado',
        ])->assertRedirect('/usuarios');

        $actualizado = $objetivo->fresh();

        $this->assertNotSame($nueva, $actualizado->password);
        $this->assertTrue(Hash::check($nueva, $actualizado->password));
    }

    public function test_al_editar_sin_contrasena_se_conserva_el_hash_anterior(): void
    {
        $objetivo = $this->usuarioCon('Empleado', ['password' => self::CLAVE]);
        $hashAnterior = $objetivo->fresh()->password;

        $this->actingAs($this->usuarioCon('Administrador'))->put("/usuarios/{$objetivo->id}", [
            'name' => 'Nombre Cambiado',
            'email' => $objetivo->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => 'Empleado',
        ])->assertRedirect('/usuarios');

        $this->assertSame($hashAnterior, $objetivo->fresh()->password);
    }

    public function test_contrasena_de_registro_publico_se_guarda_con_hash(): void
    {
        $this->post('/register', [
            'name' => 'Registro Seguro',
            'email' => 'seguro@example.com',
            'password' => self::CLAVE,
            'password_confirmation' => self::CLAVE,
        ])->assertRedirect('/dashboard');

        $usuario = User::where('email', 'seguro@example.com')->firstOrFail();

        $this->assertNotSame(self::CLAVE, $usuario->password);
        $this->assertTrue(Hash::check(self::CLAVE, $usuario->password));
    }

    public function test_contrasena_de_recuperacion_se_guarda_con_hash(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE]);
        $token = Password::broker()->createToken($usuario);
        $nueva = 'RecuperadaSegura#2026';

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $usuario->email,
            'password' => $nueva,
            'password_confirmation' => $nueva,
        ])->assertRedirect('/login');

        $actualizado = $usuario->fresh();

        $this->assertNotSame($nueva, $actualizado->password);
        $this->assertTrue(Hash::check($nueva, $actualizado->password));
    }

    public function test_seeder_guarda_la_contrasena_del_administrador_con_hash(): void
    {
        $this->seed(DatabaseSeeder::class);

        $administrador = User::where('email', 'administrador@finanzy.com')->firstOrFail();

        $this->assertNotSame('AdminFinanzy#0', $administrador->password);
        $this->assertTrue(Hash::check('AdminFinanzy#0', $administrador->password));
    }

    // ------------------------------------------------------------------
    // 4. Validación de entradas mediante FormRequests
    // ------------------------------------------------------------------

    public static function formRequestsConCampos(): array
    {
        return [
            'login' => [LoginRequest::class, ['email', 'password', 'remember']],
            'registro' => [RegisterRequest::class, ['name', 'email', 'password']],
            'enlace de recuperacion' => [SendResetLinkRequest::class, ['email']],
            'restablecer contrasena' => [ResetPasswordRequest::class, ['token', 'email', 'password']],
            'alta de usuario' => [StoreUserRequest::class, ['name', 'email', 'password', 'rol']],
            'filtro de usuarios' => [FiltrarUsuariosRequest::class, ['q', 'rol', 'estado']],
            'filtro de bitacora' => [FiltrarBitacoraRequest::class, ['user_id', 'fecha_inicio', 'fecha_fin', 'modulo']],
            'registro de demo' => [StoreDemoRequest::class, ['descripcion', 'monto', 'fecha', 'categoria_id']],
        ];
    }

    #[DataProvider('formRequestsConCampos')]
    public function test_cada_form_request_valida_todos_sus_campos(string $clase, array $campos): void
    {
        $reglas = array_keys((new $clase)->rules());

        $this->assertEqualsCanonicalizing($campos, $reglas);
    }

    public function test_registro_de_demo_rechaza_datos_invalidos(): void
    {
        $this->from('/demo')->post('/demo', [
            'descripcion' => '',
            'monto' => 0,
            'fecha' => 'no-es-fecha',
            'categoria_id' => 99,
        ])->assertSessionHasErrors(['descripcion', 'monto', 'fecha', 'categoria_id']);
    }

    public function test_alta_de_usuario_rechaza_rol_no_permitido(): void
    {
        $this->actingAs($this->usuarioCon('Administrador'))
            ->from('/usuarios/create')
            ->post('/usuarios', [
                'name' => 'Rol Inventado',
                'email' => 'rol@example.com',
                'password' => self::CLAVE,
                'password_confirmation' => self::CLAVE,
                'rol' => 'SuperUsuario',
            ])
            ->assertSessionHasErrors('rol');

        $this->assertDatabaseMissing('users', ['email' => 'rol@example.com']);
    }
}
