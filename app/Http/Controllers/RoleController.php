<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Role::query();

        // Recherche par nom ou description
        if (request('search')) {
            $search = request('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $roles = $query->withCount('permissions')->orderBy('created_at', 'desc')->paginate(10)->appends(request()->query());

        return Inertia::render('Administration/Roles/Index', [
            'roles' => $roles,
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
        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('Administration/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request)
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        // Attribuer les permissions au rôle
        if ($request->has('permissions')) {
            $permissionIds = $request->validated('permissions');
            $permissions = Permission::whereIn('id', $permissionIds)->pluck('name');
            $role->syncPermissions($permissions);
        }

        return redirect()->route('roles.index')
            ->with('message', 'Rôle créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $role->load('permissions');

        return Inertia::render('Administration/Roles/Show', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::orderBy('name')->get();

        return Inertia::render('Administration/Roles/Edit', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        $role->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        // Mettre à jour les permissions du rôle
        if ($request->has('permissions')) {
            $permissionIds = $request->validated('permissions');
            $permissions = Permission::whereIn('id', $permissionIds)->pluck('name');
            $role->syncPermissions($permissions);
        } else {
            $role->syncPermissions([]);
        }

        return redirect()->route('roles.index')
            ->with('message', 'Rôle mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()->route('roles.index')
            ->with('message', 'Rôle supprimé avec succès.');
    }
}
