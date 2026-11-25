<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        // Las categorías son compartidas por toda la empresa.
        $categorias = Categoria::query()
            ->when($request->query('q'), fn ($q, $v) => $q->where('nombre', 'like', '%'.$v.'%'))
            ->when(in_array($request->query('tipo'), ['ingreso', 'gasto']), fn ($q) => $q->where('tipo', $request->query('tipo')))
            ->withCount('movimientos')->orderBy('tipo')->orderBy('nombre')->paginate(12)->withQueryString();

        return view('categorias.index', compact('categorias'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Categoria::create($data + ['user_id' => $request->user()->id]);

        return redirect()->route('categorias.index')->with('success', 'Categoría creada correctamente.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        $datos = $this->validated($request, $categoria);

        // El tipo de la categoría decide si sus movimientos son ingresos o gastos:
        // cambiarlo reclasificaría todo su histórico en el panel y en los reportes.
        if ($datos['tipo'] !== $categoria->tipo && $categoria->movimientos()->exists()) {
            return back()->with('error', 'No se puede cambiar el tipo de una categoría con movimientos asociados.');
        }

        $categoria->update($datos);

        return redirect()->route('categorias.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(Categoria $categoria)
    {
        if ($categoria->movimientos()->exists()) {
            return back()->with('error', 'No se puede eliminar una categoría con movimientos asociados.');
        }
        $categoria->delete();

        return back()->with('success', 'Categoría eliminada.');
    }

    /**
     * Las categorías son de toda la empresa: no puede haber dos con el mismo nombre y tipo,
     * sin distinguir mayúsculas ni minúsculas. El mismo nombre sí puede existir como ingreso
     * y como gasto (p. ej. «Otros»).
     */
    private function validated(Request $request, ?Categoria $categoria = null): array
    {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                function (string $atributo, mixed $valor, Closure $fallar) use ($request, $categoria): void {
                    $duplicada = Categoria::query()
                        ->whereRaw('LOWER(nombre) = LOWER(?)', [$valor])
                        ->where('tipo', $request->input('tipo'))
                        ->when($categoria, fn ($consulta) => $consulta->whereKeyNot($categoria->id))
                        ->exists();

                    if ($duplicada) {
                        $fallar('Ya existe una categoría con ese nombre y tipo.');
                    }
                },
            ],
            'tipo' => ['required', Rule::in(['ingreso', 'gasto'])],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
