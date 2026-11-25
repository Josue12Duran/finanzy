<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\MovimientoFinanciero;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoriasReportesTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitado_no_puede_acceder_a_modulos(): void
    {
        $this->get('/categorias')->assertRedirect('/login');
        $this->get('/reportes')->assertRedirect('/login');
    }

    public function test_usuario_crea_y_consulta_su_categoria(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('categorias.store'), ['nombre' => 'Salario', 'tipo' => 'ingreso'])->assertRedirect(route('categorias.index'));
        $this->assertDatabaseHas('categorias', ['user_id' => $user->id, 'nombre' => 'Salario']);
        $this->actingAs($user)->get(route('categorias.index'))->assertSee('Salario');
    }

    public function test_cualquier_usuario_puede_modificar_y_eliminar_categoria_de_otro(): void
    {
        $creador = User::factory()->create();
        $otro = User::factory()->create();
        $categoria = Categoria::create(['user_id' => $creador->id, 'nombre' => 'Compartida', 'tipo' => 'gasto']);

        $this->actingAs($otro)->put(route('categorias.update', $categoria), ['nombre' => 'Cambiada', 'tipo' => 'gasto'])
            ->assertRedirect(route('categorias.index'));
        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'nombre' => 'Cambiada']);

        $this->actingAs($otro)->delete(route('categorias.destroy', $categoria))->assertRedirect();
        $this->assertModelMissing($categoria);
    }

    public function test_no_permite_crear_categoria_duplicada_aunque_sea_de_otro_usuario(): void
    {
        Categoria::create(['user_id' => User::factory()->create()->id, 'nombre' => 'Salario', 'tipo' => 'ingreso']);
        $otro = User::factory()->create();

        $this->actingAs($otro)->post(route('categorias.store'), ['nombre' => 'Salario', 'tipo' => 'ingreso'])
            ->assertSessionHasErrors('nombre');
        $this->actingAs($otro)->post(route('categorias.store'), ['nombre' => '  salario  ', 'tipo' => 'ingreso'])
            ->assertSessionHasErrors('nombre');

        $this->assertSame(1, Categoria::where('tipo', 'ingreso')->count());
    }

    public function test_permite_el_mismo_nombre_con_tipo_distinto(): void
    {
        Categoria::create(['user_id' => User::factory()->create()->id, 'nombre' => 'Otros', 'tipo' => 'ingreso']);

        $this->actingAs(User::factory()->create())->post(route('categorias.store'), ['nombre' => 'Otros', 'tipo' => 'gasto'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categorias', ['nombre' => 'Otros', 'tipo' => 'gasto']);
    }

    public function test_no_permite_renombrar_a_un_nombre_existente_y_permite_conservar_el_propio(): void
    {
        $usuario = User::factory()->create();
        Categoria::create(['user_id' => $usuario->id, 'nombre' => 'Transporte', 'tipo' => 'gasto']);
        $categoria = Categoria::create(['user_id' => $usuario->id, 'nombre' => 'Luz', 'tipo' => 'gasto']);

        $this->actingAs($usuario)->put(route('categorias.update', $categoria), ['nombre' => 'transporte', 'tipo' => 'gasto'])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($usuario)->put(route('categorias.update', $categoria), ['nombre' => 'Luz', 'tipo' => 'gasto', 'descripcion' => 'Electricidad'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'descripcion' => 'Electricidad']);
    }

    public function test_reportes_exportan_csv_y_pdf(): void
    {
        // El nombre del archivo refleja los filtros: por defecto, reporte mensual de los últimos 6 meses.
        $this->travelTo('2026-11-20 10:00:00');
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('reportes.csv'))->assertOk()->assertDownload('reporte-mensual-2026-06-a-2026-11.csv');
        $this->actingAs($user)->get(route('reportes.pdf'))->assertOk()->assertDownload('reporte-mensual-2026-06-a-2026-11.pdf');
    }

    public function test_permite_registrar_movimiento_con_categoria_de_otro_usuario(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $categoria = Categoria::create(['user_id' => $owner->id, 'nombre' => 'Privada', 'tipo' => 'gasto']);

        $this->actingAs($other)->post(route('movimientos.store'), [
            'tipo' => 'gasto',
            'categoria_id' => $categoria->id,
            'monto' => 15,
            'fecha' => '2026-11-12',
            'concepto' => 'Prueba',
        ])->assertRedirect(route('gastos.index'));

        $this->assertDatabaseHas('movimientos_financieros', [
            'user_id' => $other->id,
            'categoria_id' => $categoria->id,
            'concepto' => 'Prueba',
            'activo' => true,
        ]);
    }

    public function test_no_permite_categoria_de_tipo_distinto_al_movimiento(): void
    {
        $user = User::factory()->create();
        $categoria = Categoria::create(['user_id' => $user->id, 'nombre' => 'Salario', 'tipo' => 'ingreso']);

        $this->actingAs($user)->post(route('movimientos.store'), [
            'tipo' => 'gasto',
            'categoria_id' => $categoria->id,
            'monto' => 15,
            'fecha' => '2026-11-12',
            'concepto' => 'Prueba',
        ])->assertSessionHasErrors('categoria_id');

        $this->assertDatabaseCount('movimientos_financieros', 0);
    }

    public static function estadosDeMovimiento(): array
    {
        return ['activo' => [true], 'inactivo' => [false]];
    }

    #[DataProvider('estadosDeMovimiento')]
    public function test_no_elimina_una_categoria_con_historial_aunque_el_movimiento_este_inactivo(bool $activo): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::create(['user_id' => $usuario->id, 'nombre' => 'Transporte', 'tipo' => 'gasto']);
        $movimiento = MovimientoFinanciero::create([
            'user_id' => $usuario->id,
            'categoria_id' => $categoria->id,
            'monto' => '30.00',
            'fecha' => '2026-11-23',
            'concepto' => 'Traslado con historial',
            'activo' => $activo,
        ]);

        $this->actingAs($usuario)->from(route('categorias.index'))
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('error', 'No se puede eliminar una categoría con movimientos asociados.');

        $this->assertModelExists($categoria);
        $this->assertModelExists($movimiento);
        $this->assertSame($categoria->id, $movimiento->fresh()->categoria->id);
        $this->assertSame($usuario->id, $movimiento->usuario->id);
        $this->assertSame([$movimiento->id], $categoria->movimientos()->pluck('id')->all());
        $this->assertSame($activo, $movimiento->fresh()->activo);
    }

    public function test_el_propietario_puede_eliminar_una_categoria_sin_movimientos(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::create(['user_id' => $usuario->id, 'nombre' => 'Sin uso', 'tipo' => 'gasto']);

        $this->actingAs($usuario)->from(route('categorias.index'))
            ->delete(route('categorias.destroy', $categoria))
            ->assertRedirect(route('categorias.index'))
            ->assertSessionHas('success', 'Categoría eliminada.');

        $this->assertModelMissing($categoria);
    }

    public function test_la_clave_foranea_impide_eliminar_directamente_una_categoria_con_movimientos(): void
    {
        $usuario = User::factory()->create();
        $categoria = Categoria::create(['user_id' => $usuario->id, 'nombre' => 'Transporte', 'tipo' => 'gasto']);
        $movimiento = MovimientoFinanciero::create([
            'user_id' => $usuario->id,
            'categoria_id' => $categoria->id,
            'monto' => '20.00',
            'fecha' => '2026-11-23',
            'concepto' => 'Historial protegido por la base',
        ]);

        try {
            $categoria->delete();
            $this->fail('La clave foránea debe impedir la eliminación del historial.');
        } catch (QueryException $exception) {
            $this->assertSame('23000', $exception->getCode());
        }

        $this->assertModelExists($categoria);
        $this->assertModelExists($movimiento);
    }
}
