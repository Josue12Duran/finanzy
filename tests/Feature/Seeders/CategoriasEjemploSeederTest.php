<?php

namespace Tests\Feature\Seeders;

use App\Models\Categoria;
use App\Models\User;
use Database\Seeders\CategoriasEjemploSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class CategoriasEjemploSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_seeder_principal_no_carga_las_categorias_de_ejemplo(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('categorias', 0);
    }

    public function test_repetir_la_carga_conserva_el_catalogo_aprobado_y_su_propietario(): void
    {
        $this->freezeTime();
        $this->seed(DatabaseSeeder::class);
        $administrador = User::where('email', 'administrador@finanzy.com')->firstOrFail();
        $this->seed(CategoriasEjemploSeeder::class);
        $categoriasOriginales = Categoria::orderBy('id')->get()->toArray();
        $this->travel(1)->day();

        $this->seed(CategoriasEjemploSeeder::class);

        $this->assertDatabaseCount('categorias', 8);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame($categoriasOriginales, Categoria::orderBy('id')->get()->toArray());
        $this->assertSame([
            'Alquiler' => 'gasto',
            'Otros gastos' => 'gasto',
            'Otros ingresos' => 'ingreso',
            'Servicios básicos' => 'gasto',
            'Servicios prestados' => 'ingreso',
            'Suministros' => 'gasto',
            'Transporte' => 'gasto',
            'Ventas' => 'ingreso',
        ], Categoria::where('user_id', $administrador->id)->orderBy('nombre')->pluck('tipo', 'nombre')->all());
    }

    public function test_crea_solo_las_faltantes_y_conserva_las_categorias_existentes_de_cualquier_usuario(): void
    {
        $this->freezeTime();
        $this->seed(DatabaseSeeder::class);
        $administrador = User::where('email', 'administrador@finanzy.com')->firstOrFail();
        $existente = Categoria::create([
            'user_id' => $administrador->id,
            'nombre' => 'Transporte',
            'tipo' => 'gasto',
            'descripcion' => 'Descripción personalizada del equipo',
        ]);
        $otra = Categoria::create([
            'user_id' => User::factory()->create()->id,
            'nombre' => 'Ventas',
            'tipo' => 'ingreso',
            'descripcion' => 'Categoría de otro propietario',
        ]);
        $datosExistente = $existente->refresh()->getRawOriginal();
        $datosOtra = $otra->refresh()->getRawOriginal();
        $this->travel(1)->day();

        $this->seed(CategoriasEjemploSeeder::class);
        $this->seed(CategoriasEjemploSeeder::class);

        $this->assertDatabaseCount('categorias', 9);
        $this->assertSame(8, Categoria::where('user_id', $administrador->id)->count());
        $this->assertSame($datosExistente, $existente->fresh()->getRawOriginal());
        $this->assertSame($datosOtra, $otra->fresh()->getRawOriginal());
        $this->assertDatabaseHas('categorias', [
            'user_id' => $administrador->id,
            'nombre' => 'Ventas',
            'tipo' => 'ingreso',
        ]);
    }

    public function test_un_empleado_puede_usar_el_catalogo_sin_editar_ni_eliminar_categorias_del_administrador(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(CategoriasEjemploSeeder::class);
        $empleado = User::factory()->create();
        $empleado->assignRole('Empleado');
        $transporte = Categoria::where('nombre', 'Transporte')->firstOrFail();

        $this->actingAs($empleado)->post(route('movimientos.store'), [
            'tipo' => 'gasto',
            'categoria_id' => $transporte->id,
            'monto' => '30.00',
            'fecha' => '2026-11-23',
            'concepto' => 'Traslado de demostración',
        ])->assertRedirect(route('gastos.index'));

        $this->assertDatabaseHas('movimientos_financieros', [
            'user_id' => $empleado->id,
            'categoria_id' => $transporte->id,
            'monto' => '30.00',
            'concepto' => 'Traslado de demostración',
            'activo' => true,
        ]);
        $this->actingAs($empleado)->put(route('categorias.update', $transporte), [
            'nombre' => 'Transporte modificado',
            'tipo' => 'gasto',
        ])->assertRedirect(route('categorias.index'));
        $this->assertDatabaseHas('categorias', [
            'id' => $transporte->id,
            'user_id' => $transporte->user_id,
            'nombre' => 'Transporte modificado',
            'tipo' => 'gasto',
        ]);
        $this->actingAs($empleado)->put(route('categorias.update', $transporte), [
            'nombre' => 'Transporte',
            'tipo' => 'gasto',
        ])->assertRedirect(route('categorias.index'));

        // Con movimientos asociados no se elimina, sin importar quién lo intente.
        $this->actingAs($empleado)->delete(route('categorias.destroy', $transporte))
            ->assertSessionHas('error', 'No se puede eliminar una categoría con movimientos asociados.');
        $this->assertModelExists($transporte);
    }

    public function test_sin_administrador_inicial_informa_la_dependencia_sin_crear_registros(): void
    {
        try {
            $this->seed(CategoriasEjemploSeeder::class);
            $this->fail('La carga debe requerir el administrador inicial.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Ejecuta DatabaseSeeder antes de cargar las categorías de ejemplo.',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('categorias', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('roles', 0);
    }
}
