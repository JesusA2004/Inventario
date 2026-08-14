<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:gestionar-usuarios'),
        ];
    }

    public function index(Request $request): Response
    {
        $users = User::query()
            ->with('roles:id,name')
            ->when($request->string('q')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at?->toISOString(),
                'roles' => $user->roles->pluck('name'),
            ]);

        return Inertia::render('usuarios/Index', [
            'users' => $users,
            'filters' => $request->only('q'),
            'roles' => Role::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $password = Str::password(14);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $user->assignRole($data['role']);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Usuario creado. Contraseña temporal: {$password}",
        ]);

        return back();
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $user->update(['name' => $data['name'], 'email' => $data['email']]);
        $user->syncRoles([$data['role']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Usuario actualizado correctamente.']);

        return back();
    }

    public function toggleActive(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $user->is_active ? 'Usuario activado.' : 'Usuario desactivado.',
        ]);

        return back();
    }

    public function resetPassword(User $user): RedirectResponse
    {
        $password = Str::password(14);

        $user->update(['password' => Hash::make($password)]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Contraseña restablecida para {$user->email}. Nueva contraseña temporal: {$password}",
        ]);

        return back();
    }
}
