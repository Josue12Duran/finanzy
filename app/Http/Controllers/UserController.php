<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $usuarios = User::with('roles')
            ->orderBy('name')
            ->paginate(10);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.create', [
            'usuario' => new User,
            'roles' => User::ROLES,
            'rolActual' => null,
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos) {
            $usuario = User::create([
                'name' => $datos['name'],
                'email' => $datos['email'],
                'password' => Hash::make($datos['password']),
                'activo' => true,
                'email_verified_at' => now(),
            ]);

            $usuario->syncRoles([$datos['rol']]);

            AuditLogger::log('crear', 'usuarios', "Creó al usuario {$usuario->email} con rol {$datos['rol']}");
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => User::ROLES,
            'rolActual' => $usuario->roles->first()?->name,
        ]);
    }

    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();
        $rolAnterior = $usuario->roles->first()?->name;

        // Evita que un administrador se quite a sí mismo el rol y pierda el acceso.
        if ($usuario->is($request->user()) && $rolAnterior === 'Administrador' && $datos['rol'] !== 'Administrador') {
            throw ValidationException::withMessages([
                'rol' => 'No puede quitarse a sí mismo el rol de Administrador.',
            ]);
        }

        DB::transaction(function () use ($datos, $usuario, $rolAnterior) {
            $cambioPassword = ! empty($datos['password']);

            $usuario->fill([
                'name' => $datos['name'],
                'email' => $datos['email'],
            ]);

            if ($cambioPassword) {
                $usuario->password = Hash::make($datos['password']);
            }

            $usuario->save();
            $usuario->syncRoles([$datos['rol']]);

            $detalle = "Editó al usuario {$usuario->email}";
            if ($cambioPassword) {
                $detalle .= '; cambió la contraseña';
            }
            if ($rolAnterior !== $datos['rol']) {
                $detalle .= "; rol: {$rolAnterior} → {$datos['rol']}";
            }

            AuditLogger::log('editar', 'usuarios', $detalle);
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Activa o desactiva la cuenta. Un usuario inactivo no puede iniciar sesión.
     */
    public function toggleStatus(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return back()->with('error', 'No puede desactivar su propia cuenta.');
        }

        DB::transaction(function () use ($usuario) {
            $usuario->activo = ! $usuario->activo;
            $usuario->save();

            if ($usuario->activo) {
                AuditLogger::log('activar', 'usuarios', "Activó al usuario {$usuario->email}");
            } else {
                AuditLogger::log('desactivar', 'usuarios', "Desactivó al usuario {$usuario->email}");
            }
        });

        return back()->with(
            'success',
            $usuario->activo ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.',
        );
    }
}
