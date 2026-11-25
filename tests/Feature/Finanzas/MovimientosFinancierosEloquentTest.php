<?php

namespace Tests\Feature\Finanzas;

use App\Models\Categoria;
use App\Models\MovimientoFinanciero;
use App\Models\User;
use App\Services\Finanzas\FuenteDatosFinancieros;
use App\Services\Finanzas\MovimientosFinancierosEloquent;
use App\Services\Finanzas\Periodo;
use App\Services\Finanzas\TipoMovimiento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MovimientosFinancierosEloquentTest extends TestCase
{
    use RefreshDatabase;

    private function categoria(User $usuario, string $nombre, string $tipo): Categoria
    {
        return Categoria::create(['user_id' => $usuario->id, 'nombre' => $nombre, 'tipo' => $tipo]);
    }

    private function movimiento(Categoria $categoria, string $fecha, float $monto, string $concepto = 'Movimiento'): MovimientoFinanciero
    {
        return MovimientoFinanciero::create([
            'user_id' => $categoria->user_id,
            'categoria_id' => $categoria->id,
            'monto' => $monto,
            'fecha' => $fecha,
            'concepto' => $concepto,
        ]);
    }

    private function fuenteDe(User $usuario): FuenteDatosFinancieros
    {
        $this->actingAs($usuario);

        return $this->app->make(FuenteDatosFinancieros::class);
    }

    private function noviembre(): Periodo
    {
        return new Periodo('2026-11-01', '2026-11-30');
    }

    public function test_la_aplicacion_usa_los_datos_reales_por_defecto(): void
    {
        $fuente = $this->fuenteDe(User::factory()->create());

        $this->assertInstanceOf(MovimientosFinancierosEloquent::class, $fuente);
        $this->assertFalse($fuente->esSimulada());
    }

    public function test_el_tipo_de_cada_movimiento_es_el_de_su_categoria(): void
    {
        $usuario = User::factory()->create();
        $this->movimiento($this->categoria($usuario, 'Salario', 'ingreso'), '2026-11-02', 5000);
        $this->movimiento($this->categoria($usuario, 'Alquiler', 'gasto'), '2026-11-03', 1500.25);

        $fuente = $this->fuenteDe($usuario);

        $this->assertSame(5000.0, $fuente->totalIngresos($this->noviembre()));
        $this->assertSame(1500.25, $fuente->totalGastos($this->noviembre()));
        $this->assertSame(['Alquiler' => 1500.25], $fuente->gastosPorCategoria($this->noviembre()));
    }

    public function test_los_movimientos_inactivos_no_se_incluyen_en_totales_ni_listados(): void
    {
        $usuario = User::factory()->create();
        $movimiento = $this->movimiento($this->categoria($usuario, 'Ventas', 'ingreso'), '2026-11-02', 500);
        $movimiento->update(['activo' => false]);

        $fuente = $this->fuenteDe(User::factory()->create());

        $this->assertSame(0.0, $fuente->totalIngresos($this->noviembre()));
        $this->assertSame([], $fuente->movimientos($this->noviembre()));
    }

    public function test_incluye_el_primer_y_el_ultimo_dia_del_periodo_y_excluye_los_demas(): void
    {
        $usuario = User::factory()->create();
        $gastos = $this->categoria($usuario, 'Insumos', 'gasto');
        $this->movimiento($gastos, '2026-10-31', 1);
        $this->movimiento($gastos, '2026-11-01', 10);
        $this->movimiento($gastos, '2026-11-30', 100);
        $this->movimiento($gastos, '2026-12-01', 1000);

        $this->assertSame(110.0, $this->fuenteDe($usuario)->totalGastos($this->noviembre()));
    }

    public function test_todos_los_usuarios_autenticados_consultan_los_mismos_movimientos(): void
    {
        $cajero = User::factory()->create();
        $gerente = User::factory()->create();
        $this->movimiento($this->categoria($cajero, 'Salario', 'ingreso'), '2026-11-02', 5000, 'Nómina de empresa');

        $fuente = $this->fuenteDe($gerente);

        $this->assertSame(5000.0, $fuente->totalIngresos($this->noviembre()));
        $this->assertSame(['Nómina de empresa'], array_map(
            fn ($movimiento) => $movimiento->descripcion,
            $fuente->movimientos($this->noviembre()),
        ));
        $this->assertSame(['Salario'], array_values($fuente->categorias()));
    }

    public function test_sin_sesion_no_devuelve_datos(): void
    {
        $usuario = User::factory()->create();
        $this->movimiento($this->categoria($usuario, 'Salario', 'ingreso'), '2026-11-02', 5000);

        $fuente = $this->app->make(FuenteDatosFinancieros::class);

        $this->assertSame([], $fuente->movimientos($this->noviembre()));
        $this->assertSame([], $fuente->categorias());
    }

    public function test_convierte_cada_registro_en_un_movimiento_del_panel(): void
    {
        $usuario = User::factory()->create();
        $this->movimiento($this->categoria($usuario, 'Ventas', 'ingreso'), '2026-11-02', 300, 'Venta al contado');

        [$movimiento] = $this->fuenteDe($usuario)->movimientos($this->noviembre());

        $this->assertSame('2026-11-02', $movimiento->fecha->toDateString());
        $this->assertSame('Venta al contado', $movimiento->descripcion);
        $this->assertSame('Ventas', $movimiento->categoria);
        $this->assertSame(TipoMovimiento::Ingreso, $movimiento->tipo);
        $this->assertSame(300.0, $movimiento->monto);
        $this->assertSame('Confirmado', $movimiento->estado);
    }

    public function test_filtra_por_categoria_y_ordena_del_mas_reciente_al_mas_antiguo(): void
    {
        $usuario = User::factory()->create();
        $insumos = $this->categoria($usuario, 'Insumos', 'gasto');
        $this->movimiento($insumos, '2026-11-03', 10, 'Primero');
        $this->movimiento($insumos, '2026-11-09', 20, 'Segundo');
        $this->movimiento($this->categoria($usuario, 'Alquiler', 'gasto'), '2026-11-05', 1500);

        $movimientos = $this->fuenteDe($usuario)->movimientos($this->noviembre(), (string) $insumos->id);

        $this->assertSame(['Segundo', 'Primero'], array_map(fn ($movimiento) => $movimiento->descripcion, $movimientos));
    }

    public function test_las_categorias_se_identifican_por_su_clave_y_distinguen_nombres_repetidos(): void
    {
        $usuario = User::factory()->create();
        $otrosIngresos = $this->categoria($usuario, 'Otros', 'ingreso');
        $otrosGastos = $this->categoria($usuario, 'Otros', 'gasto');
        $alquiler = $this->categoria($usuario, 'Alquiler', 'gasto');

        $this->assertSame([
            (string) $alquiler->id => 'Alquiler',
            (string) $otrosGastos->id => 'Otros (Gasto)',
            (string) $otrosIngresos->id => 'Otros (Ingreso)',
        ], $this->fuenteDe($usuario)->categorias());
    }

    public function test_la_distribucion_suma_categorias_del_mismo_nombre_sin_mezclar_sus_filtros_por_id(): void
    {
        $primero = User::factory()->create();
        $segundo = User::factory()->create();
        $transportePrimero = $this->categoria($primero, 'Transporte', 'gasto');
        $transporteSegundo = $this->categoria($segundo, 'Transporte', 'gasto');
        $this->movimiento($transportePrimero, '2026-11-02', 30, 'Traslado del primer usuario');
        $this->movimiento($transporteSegundo, '2026-11-03', 20, 'Traslado del segundo usuario');
        $inactivo = $this->movimiento($transporteSegundo, '2026-11-04', 100, 'Traslado inactivo');
        $inactivo->update(['activo' => false]);
        $this->movimiento($transportePrimero, '2026-10-31', 200, 'Traslado fuera del periodo');

        $fuente = $this->fuenteDe($primero);

        $this->assertSame(['Transporte' => 50.0], $fuente->gastosPorCategoria($this->noviembre()));
        $this->assertSame(['Traslado del primer usuario'], array_map(
            fn ($movimiento) => $movimiento->descripcion,
            $fuente->movimientos($this->noviembre(), (string) $transportePrimero->id),
        ));
        $this->assertSame(['Traslado del segundo usuario'], array_map(
            fn ($movimiento) => $movimiento->descripcion,
            $fuente->movimientos($this->noviembre(), (string) $transporteSegundo->id),
        ));
    }
}
