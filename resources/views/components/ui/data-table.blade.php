{{-- $headers: arreglo de etiquetas de columna. El contenido de las filas se pasa como slot (<tr>...</tr>). --}}
@props(['headers' => [], 'striped' => true, 'empty' => 'No hay registros para mostrar.', 'isEmpty' => false])

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table {{ $attributes->class(['table', 'table-hover', 'align-middle', 'mb-0', 'table-striped' => $striped]) }}>
            <thead class="table-light">
                <tr>
                    @foreach ($headers as $header)
                        <th scope="col" class="small text-uppercase text-muted">{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @if ($isEmpty)
                    <tr>
                        <td colspan="{{ count($headers) }}" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                            {{ $empty }}
                        </td>
                    </tr>
                @else
                    {{ $slot }}
                @endif
            </tbody>
        </table>
    </div>
</div>
