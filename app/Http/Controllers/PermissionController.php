<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json(Permission::select('id', 'name')->get());
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        // Normalize input: wrap single permission into an array
        $permissions = $request->has('name') && is_array($request->name)
            ? collect($request->name)->map(fn($name) => ['name' => $name])->toArray()
            : (is_array($request->all()) && isset($request->all()[0]) ? $request->all() : [$request->all()]);

        $validator = Validator::make(['permissions' => $permissions], [
            'permissions' => 'required|array|min:1',
            'permissions.*.name' => 'required|string|distinct|unique:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $created = collect($permissions)->map(function ($item) {
            return Permission::create([
                'name' => $item['name'],
                'guard_name' => $item['guard_name'] ?? 'web',
            ]);
        });

        return response()->json([
            'status' => 'success',
            'data' => $created->count() === 1 ? $created->first() : $created
        ], 201);
    }

    /**
     * Update an existing permission.
     */
    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:permissions,name,' . $permission->id,
            'guard_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permission->update([
            'name' => $request->name,
            'guard_name' => $request->guard_name ?? $permission->guard_name,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $permission,
        ]);
    }

    /**
     * Delete a permission.
     */
    public function destroy($id)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission introuvable',
            ], 404);
        }

        $permission->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Permission supprimée avec succès',
        ]);
    }

    /**
     * List users who have this permission directly assigned.
     */
    public function users($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission introuvable',
            ], 404);
        }

        return response()->json($permission->users()->select('users.id', 'users.full_name', 'users.email')->get());
    }

    /**
     * List roles that have this permission.
     */
    public function roles($id)
    {
        $permission = Permission::find($id);

        if (!$permission) {
            return response()->json([
                'status' => 'error',
                'message' => 'Permission introuvable',
            ], 404);
        }

        return response()->json($permission->roles()->select('roles.id', 'roles.name')->get());
    }
}