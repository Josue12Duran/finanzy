<x-layout.app title="Panel de Pruebas">

    @php
        $categorias = [
            1 => 'Salario',
            2 => 'Ventas',
            3 => 'Alimentación',
            4 => 'Servicios',
        ];

        $ingresos = 12500.00;
        $gastos = 8430.50;
        $balance = $ingresos - $gastos;
        $variacionMonto = 1200.00;
        $variacionPorcentaje = 8.5;

        $registros = [
            ['id' => 1, 'fecha' => '2026-11-01', 'descripcion' => 'Pago de nómina', 'categoria' => 'Salario', 'monto' => 5000.00, 'tipo' => 'ingreso', 'estado' => 'Confirmado'],
            ['id' => 2, 'fecha' => '2026-11-03', 'descripcion' => 'Compra de insumos', 'categoria' => 'Ventas', 'monto' => 1250.75, 'tipo' => 'gasto', 'estado' => 'Pendiente'],
            ['id' => 3, 'fecha' => '2026-11-05', 'descripcion' => 'Recibo de energía', 'categoria' => 'Servicios', 'monto' => 320.40, 'tipo' => 'gasto', 'estado' => 'Rechazado'],
        ];

        $colorEstado = ['Confirmado' => 'success', 'Pendiente' => 'warning', 'Rechazado' => 'danger'];
    @endphp

    <x-layout.page-header
        title="Panel de Pruebas"
        subtitle="Vista de demostración de los componentes de Finanzy (datos simulados)">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRegistro">
            <i class="bi bi-plus-lg"></i> Nuevo registro
        </button>
    </x-layout.page-header>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
            <x-financial.kpi-card title="Ingresos" :amount="$ingresos" icon="bi-arrow-down-circle" type="success" :percentage="8.5" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-financial.kpi-card title="Gastos" :amount="$gastos" icon="bi-arrow-up-circle" type="danger" :percentage="-3.2" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-financial.kpi-card title="Balance" :amount="$balance" icon="bi-wallet2" type="primary" />
        </div>
        <div class="col-12 col-md-6 col-xl-3">
            <x-financial.kpi-card title="Variación vs. mes anterior" :amount="$variacionMonto" icon="bi-graph-up-arrow" type="info" :percentage="$variacionPorcentaje" />
        </div>
    </div>

    <x-financial.filter-bar
        :categories="$categorias"
        action-url="/demo"
        start-date="2026-11-01"
        end-date="2026-11-30"
        selected-category="2" />

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-8">
            <x-financial.chart-card title="Ingresos vs. gastos (últimos 6 meses)" chart-id="chartIngresosGastos" height="320px" />
        </div>
        <div class="col-12 col-lg-4">
            <x-financial.chart-card title="Distribución por categoría" chart-id="chartCategorias" height="320px" />
        </div>
    </div>

    <x-ui.data-table :headers="['Fecha', 'Descripción', 'Categoría', 'Tipo', 'Monto', 'Estado', 'Acciones']" :is-empty="empty($registros)">
        @foreach ($registros as $registro)
            <tr>
                <td class="text-nowrap">{{ $registro['fecha'] }}</td>
                <td>{{ $registro['descripcion'] }}</td>
                <td>{{ $registro['categoria'] }}</td>
                <td>
                    <x-ui.badge :type="$registro['tipo'] === 'ingreso' ? 'success' : 'danger'">
                        {{ ucfirst($registro['tipo']) }}
                    </x-ui.badge>
                </td>
                <td class="text-end fw-semibold">${{ number_format($registro['monto'], 2, '.', ',') }}</td>
                <td>
                    <x-ui.badge :type="$colorEstado[$registro['estado']] ?? 'secondary'">
                        {{ $registro['estado'] }}
                    </x-ui.badge>
                </td>
                <td>
                    <x-ui.action-buttons delete-target="modalEliminar-{{ $registro['id'] }}" />
                </td>
            </tr>
        @endforeach
    </x-ui.data-table>

    {{-- Modal de registro: se envía a POST /demo para validar --}}
    <x-ui.modal id="modalRegistro" title="Nuevo registro" action-url="/demo" submit-label="Guardar registro">
        <x-form.input name="descripcion" label="Descripción" placeholder="Ej. Pago a proveedor" required />
        <x-form.currency-input name="monto" label="Monto" placeholder="0.00" required />
        <x-form.date-picker name="fecha" label="Fecha" required />
        <x-form.select-category name="categoria_id" label="Categoría" :categories="$categorias" required />
    </x-ui.modal>

    {{-- Modales de confirmación de borrado (solo demostración, sin acción real) --}}
    @foreach ($registros as $registro)
        <x-ui.modal id="modalEliminar-{{ $registro['id'] }}" title="Confirmar eliminación" size="sm">
            <p class="mb-0">
                ¿Deseas eliminar el registro <strong>{{ $registro['descripcion'] }}</strong>?
                Esta acción no se puede deshacer.
            </p>

            <x-slot:footer>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
                    <i class="bi bi-trash"></i> Eliminar
                </button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Reabrir el modal de registro si la validación falló
                @if ($errors->any())
                    new window.bootstrap.Modal(document.getElementById('modalRegistro')).show();
                @endif

                const ctxBarras = document.getElementById('chartIngresosGastos');
                if (ctxBarras) {
                    new window.Chart(ctxBarras, {
                        type: 'bar',
                        data: {
                            labels: ['Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov'],
                            datasets: [
                                { label: 'Ingresos', data: [9800, 10400, 11000, 10750, 11900, 12500], backgroundColor: 'rgba(25, 135, 84, 0.7)' },
                                { label: 'Gastos', data: [7200, 7900, 8100, 7600, 8300, 8430.5], backgroundColor: 'rgba(220, 53, 69, 0.7)' },
                            ],
                        },
                        options: { responsive: true, maintainAspectRatio: false },
                    });
                }

                const ctxDona = document.getElementById('chartCategorias');
                if (ctxDona) {
                    new window.Chart(ctxDona, {
                        type: 'doughnut',
                        data: {
                            labels: @json(array_values($categorias)),
                            datasets: [{ data: [5000, 3200, 1800, 1450], backgroundColor: ['#0d6efd', '#198754', '#ffc107', '#dc3545'] }],
                        },
                        options: { responsive: true, maintainAspectRatio: false },
                    });
                }
            });
        </script>
    @endpush

</x-layout.app>
