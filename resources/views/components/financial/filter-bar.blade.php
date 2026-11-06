{{-- $categories: arreglo o colección con formato id => nombre (ej. Category::pluck('nombre', 'id')) --}}
@props(['categories' => [], 'actionUrl' => null, 'startDate' => '', 'endDate' => '', 'selectedCategory' => null])

<form action="{{ $actionUrl }}" method="GET" class="card card-body border-0 shadow-sm mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-12 col-md-3">
            <label for="fecha_inicio" class="form-label fw-semibold small">Desde</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ $startDate }}" class="form-control">
        </div>

        <div class="col-12 col-md-3">
            <label for="fecha_fin" class="form-label fw-semibold small">Hasta</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="{{ $endDate }}" class="form-control">
        </div>

        <div class="col-12 col-md-4">
            <label for="categoria_id" class="form-label fw-semibold small">Categoría</label>
            <select id="categoria_id" name="categoria_id" class="form-select">
                <option value="">Todas las categorías</option>
                @foreach ($categories as $id => $nombre)
                    <option value="{{ $id }}" @selected((string) $id === (string) $selectedCategory)>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12 col-md-2">
            <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
        </div>
    </div>
</form>
