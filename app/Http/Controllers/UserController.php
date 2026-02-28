<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = User::query()->with('roles');

        // Recherche par nom, email ou matricule
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('natricule', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10)->appends(request()->query());

        return Inertia::render('Administration/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => request('search'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return Inertia::render('Administration/Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'firstname' => $request->validated('firstname'),
            'lastname' => $request->validated('lastname'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
            'gender' => $request->validated('gender'),
            'birth_date' => $request->validated('birth_date'),
            'telephone' => $request->validated('telephone'),
            'address' => $request->validated('address'),
            'profile' => $request->validated('profile'),
        ]);

        // Attribuer les rôles à l'utilisateur
        if ($request->has('roles')) {
            $roleIds = $request->validated('roles');
            $roles = Role::whereIn('id', $roleIds)->pluck('name');
            $user->syncRoles($roles);
        }

        return redirect()->route('users.index')
            ->with('message', 'Utilisateur créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load('roles.permissions');

        return Inertia::render('Administration/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $user->load('roles');
        $roles = Role::orderBy('name')->get();

        return Inertia::render('Administration/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = [
            'firstname' => $request->validated('firstname'),
            'lastname' => $request->validated('lastname'),
            'email' => $request->validated('email'),
            'gender' => $request->validated('gender'),
            'birth_date' => $request->validated('birth_date'),
            'telephone' => $request->validated('telephone'),
            'address' => $request->validated('address'),
            'profile' => $request->validated('profile'),
        ];

        // Update password only if provided
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->validated('password'));
        }

        $user->update($data);

        // Mettre à jour les rôles de l'utilisateur
        if ($request->has('roles')) {
            $roleIds = $request->validated('roles');
            $roles = Role::whereIn('id', $roleIds)->pluck('name');
            $user->syncRoles($roles);
        } else {
            $user->syncRoles([]);
        }

        return redirect()->route('users.index')
            ->with('message', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index')
            ->with('message', 'Utilisateur supprimé avec succès.');
    }
}
