<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    private const CLAVE = 'secreto123';

    public function test_pantallas_de_autenticacion_son_accesibles_para_invitados(): void
    {
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/password/reset')->assertOk();
    }

    public function test_usuario_autenticado_es_redirigido_desde_login_al_dashboard(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/login')
            ->assertRedirect('/dashboard');
    }

    public function test_dashboard_requiere_autenticacion(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_es_accesible_para_usuario_autenticado(): void
    {
        $usuario = User::factory()->create(['name' => 'Ana Pérez']);

        $this->actingAs($usuario)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Ana Pérez');
    }

    public function test_home_redirige_al_dashboard(): void
    {
        $this->get('/home')->assertRedirect('/dashboard');
    }

    public function test_inicio_de_sesion_correcto(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE]);

        $this->post('/login', [
            'email' => $usuario->email,
            'password' => self::CLAVE,
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($usuario);
    }

    public function test_inicio_de_sesion_con_clave_incorrecta_falla(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE]);

        $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => 'clave-incorrecta',
        ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => 'Estas credenciales no coinciden con nuestros registros.']);

        $this->assertGuest();
    }

    public function test_inicio_de_sesion_requiere_correo_y_contrasena(): void
    {
        $this->from('/login')->post('/login', [])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_inicio_de_sesion_se_bloquea_tras_demasiados_intentos(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE]);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $usuario->email,
                'password' => 'clave-incorrecta',
            ]);
        }

        $respuesta = $this->from('/login')->post('/login', [
            'email' => $usuario->email,
            'password' => self::CLAVE,
        ]);

        $respuesta->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertStringContainsString('Demasiados intentos', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_registro_crea_usuario_y_lo_autentica(): void
    {
        $this->post('/register', [
            'name' => 'Luis Gómez',
            'email' => 'luis@example.com',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'Luis Gómez',
            'email' => 'luis@example.com',
        ]);

        $usuario = User::where('email', 'luis@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('NuevaClave123', $usuario->password));
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_registro_con_correo_duplicado_falla(): void
    {
        User::factory()->create(['email' => 'repetido@example.com']);

        $this->from('/register')->post('/register', [
            'name' => 'Otra Persona',
            'email' => 'repetido@example.com',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_registro_con_confirmacion_distinta_falla(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Luis Gómez',
            'email' => 'luis@example.com',
            'password' => 'NuevaClave123',
            'password_confirmation' => 'OtraClave456',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'luis@example.com']);
    }

    public function test_registro_con_contrasena_corta_falla(): void
    {
        $this->from('/register')->post('/register', [
            'name' => 'Luis Gómez',
            'email' => 'luis@example.com',
            'password' => 'corta',
            'password_confirmation' => 'corta',
        ])->assertSessionHasErrors('password');
    }

    public function test_cierre_de_sesion_con_post(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->post('/logout')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_cierre_de_sesion_por_get_no_esta_permitido(): void
    {
        $usuario = User::factory()->create();

        $this->actingAs($usuario)
            ->get('/logout')
            ->assertStatus(405);
    }

    public function test_enlace_de_recuperacion_se_envia_al_correo_registrado(): void
    {
        Notification::fake();

        $usuario = User::factory()->create(['email' => 'ana@example.com']);

        $this->post('/password/email', ['email' => 'ana@example.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($usuario, ResetPassword::class);
    }

    public function test_recuperacion_con_correo_no_registrado_muestra_error(): void
    {
        Notification::fake();

        $this->from('/password/reset')->post('/password/email', ['email' => 'nadie@example.com'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    public function test_contrasena_se_restablece_con_token_valido(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE]);
        $token = Password::broker()->createToken($usuario);

        $this->post('/password/reset', [
            'token' => $token,
            'email' => $usuario->email,
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertRedirect('/login');

        $this->assertTrue(Hash::check('NuevaClave123', $usuario->fresh()->password));
    }

    public function test_contrasena_no_se_restablece_con_token_invalido(): void
    {
        $usuario = User::factory()->create(['password' => self::CLAVE]);

        $this->from('/password/reset/token-falso')->post('/password/reset', [
            'token' => 'token-falso',
            'email' => $usuario->email,
            'password' => 'NuevaClave123',
            'password_confirmation' => 'NuevaClave123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check(self::CLAVE, $usuario->fresh()->password));
    }
}
