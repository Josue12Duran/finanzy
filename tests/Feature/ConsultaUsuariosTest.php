<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultaUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function administrador(): User
    {
        $administrador = User::factory()->create([
            'name' => 'Admin Principal',
            'email' => 'admin.principal@correo.com',
        ]);
        $administrador->assignRole('Administrador');

        return $administrador;
    }

    private function usuario(string $nombre, string $correo, string $rol = 'Empleado', bool $activo = true): User
    {
        $usuario = User::factory()->create([
            'name' => $nombre,
            'email' => $correo,
            'activo' => $activo,
        ]);
        $usuario->assignRole($rol);

        return $usuario;
    }

    public function test_busca_por_nombre_y_por_correo(): void
    {
        $admin = $this->administrador();
        $this->usuario('Ana Gómez', 'ana@correo.com');
        $this->usuario('Luis Pérez', 'luis.perez@empresa.com');

        $this->actingAs($admin)
            ->get('/usuarios?q=gómez')
            ->assertOk()
            ->assertSee('Ana Gómez')
            ->assertDontSee('Luis Pérez');

        $this->actingAs($admin)
            ->get('/usuarios?q=empresa.com')
            ->assertOk()
            ->assertSee('Luis Pérez')
            ->assertDontSee('Ana Gómez');
    }

    public function test_el_termino_de_busqueda_se_trata_literalmente_y_no_como_comodin(): void
    {
        $admin = $this->administrador();
        $this->usuario('Ana Gómez', 'ana@correo.com');

        $this->actingAs($admin)
            ->get('/usuarios?q=%25')
            ->assertOk()
            ->assertDontSee('Ana Gómez')
            ->assertSee('No hay usuarios que coincidan con los filtros.');
    }

    public function test_filtra_por_rol(): void
    {
        $admin = $this->administrador();
        $this->usuario('Empleado Uno', 'empleado@correo.com', 'Empleado');

        $this->actingAs($admin)
            ->get('/usuarios?rol=Empleado')
            ->assertOk()
            ->assertSee('Empleado Uno')
            ->assertDontSee('admin.principal@correo.com');
    }

    public function test_filtra_por_estado(): void
    {
        $admin = $this->administrador();
        $this->usuario('Usuario Activo', 'activo@correo.com', 'Empleado', true);
        $this->usuario('Usuario Inactivo', 'inactivo@correo.com', 'Empleado', false);

        $this->actingAs($admin)
            ->get('/usuarios?estado=inactivo')
            ->assertOk()
            ->assertSee('Usuario Inactivo')
            ->assertDontSee('Usuario Activo');
    }

    public function test_combina_filtros(): void
    {
        $admin = $this->administrador();
        $this->usuario('Ana Gómez', 'ana@correo.com', 'Empleado', true);
        $this->usuario('Ana Ruiz', 'ruiz@correo.com', 'Empleado', false);
        $this->usuario('Ana Admin', 'anaadmin@correo.com', 'Administrador', true);

        $this->actingAs($admin)
            ->get('/usuarios?q=Ana&rol=Empleado&estado=activo')
            ->assertOk()
            ->assertSee('Ana Gómez')
            ->assertDontSee('Ana Ruiz')
            ->assertDontSee('Ana Admin');
    }

    public function test_muestra_el_contador_de_resultados(): void
    {
        $admin = $this->administrador();
        $this->usuario('Ana Gómez', 'ana@correo.com');

        $this->actingAs($admin)
            ->get('/usuarios')
            ->assertOk()
            ->assertSee('Mostrando 1 a 2 de 2 usuarios');
    }

    public function test_la_paginacion_conserva_los_filtros(): void
    {
        $admin = $this->administrador();

        for ($i = 1; $i <= 12; $i++) {
            $this->usuario("Empleado {$i}", "empleado{$i}@correo.com", 'Empleado');
        }

        $this->actingAs($admin)
            ->get('/usuarios?rol=Empleado&page=2')
            ->assertOk()
            ->assertSee('rol=Empleado', false);
    }

    public function test_rechaza_un_rol_o_estado_invalido(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)
            ->from('/usuarios')
            ->get('/usuarios?rol=Invitado&estado=desconocido')
            ->assertRedirect('/usuarios')
            ->assertSessionHasErrors(['rol', 'estado']);
    }

    public function test_el_boton_limpiar_apunta_al_listado_sin_filtros(): void
    {
        $admin = $this->administrador();

        $this->actingAs($admin)
            ->get('/usuarios?q=Ana')
            ->assertOk()
            ->assertSee('title="Limpiar filtros"', false);
    }
}
