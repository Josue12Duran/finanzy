<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Catálogo opcional para desarrollo y demostración.
 * Ejecutar explícitamente después de DatabaseSeeder.
 */
class CategoriasEjemploSeeder extends Seeder
{
    public function run(): void
    {
        $administrador = User::where('email', 'administrador@finanzy.com')->first();

        if ($administrador === null) {
            throw new RuntimeException('Ejecuta DatabaseSeeder antes de cargar las categorías de ejemplo.');
        }

        foreach ([
            'Ventas' => 'ingreso',
            'Servicios prestados' => 'ingreso',
            'Otros ingresos' => 'ingreso',
            'Alquiler' => 'gasto',
            'Servicios básicos' => 'gasto',
            'Transporte' => 'gasto',
            'Suministros' => 'gasto',
            'Otros gastos' => 'gasto',
        ] as $nombre => $tipo) {
            Categoria::firstOrCreate([
                'user_id' => $administrador->id,
                'nombre' => $nombre,
                'tipo' => $tipo,
            ]);
        }
    }
}
