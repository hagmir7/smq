<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Ensure the authenticated user is an admin.
     * Returns a JSON error response if not, or null if authorized.
     */
    private function authorizeAdmin()
    {
        $user = auth()->user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        return null;
    }

    public function index()
    {
        return response()->json(Role::all());
    }

    public function store(Request $request)
    {
        if ($response = $this->authorizeAdmin()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 203);
        }

        $role = Role::create(['name' => $request->name, 'guard_name' => "web"]);
        return response()->json($role);
    }

    public function show($roleName)
    {
        try {
            $role = Role::findByName($roleName, 'web');
            $permissions = $role->permissions->map(function ($perm) {
                return [
                    'id' => $perm->id,
                    'name' => $perm->name
                ];
            });
            return response()->json([
                'role' => $role->name,
                'permissions' => $permissions
            ]);
        } catch (\Spatie\Permission\Exceptions\RoleDoesNotExist $e) {
            return response()->json([
                'error' => "Role '{$roleName}' not found for the 'web' guard."
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        if ($response = $this->authorizeAdmin()) {
            return $response;
        }

        $role = Role::find($id);

        if (!$role) {
            return response()->json([
                'message' => 'Role not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 203);
        }

        $role->update(['name' => $request->name]);

        return response()->json($role);
    }

    public function destroy($id)
    {
        if ($response = $this->authorizeAdmin()) {
            return $response;
        }

        $role = Role::find($id);

        if (!$role) {
            return response()->json([

                'message' => 'Role not found.'
            ], 404);
        }

        // Protect a critical role from being deleted
        if ($role->name === 'admin') {
            return response()->json([
                'message' => 'The admin role cannot be deleted.'
            ], 403);
        }

        $role->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Role deleted successfully.'
        ]);
    }

    public function users($role)
    {
        return User::role($role)
            ->where('company_id', auth()->user()->company_id)
            ->select('id AS value', 'full_name AS label')->get();
    }

        /**
     * Assign one or more permissions to a role.
     * body: { "permissions": ["edit posts", "delete posts"] }
     */
    public function assignPermissions(Request $request, $roleId)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rôle introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->givePermissionTo($request->permissions);

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions assignées au rôle avec succès',
            'data' => $role->permissions()->select('permissions.id', 'permissions.name')->get(),
        ]);
    }

    /**
     * Replace ALL permissions on a role with the given list.
     * body: { "permissions": ["edit posts", "delete posts"] }
     */
    public function syncPermissions(Request $request, $roleId)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rôle introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $role->syncPermissions($request->permissions ?? []);

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions du rôle synchronisées avec succès',
            'data' => $role->permissions()->select('permissions.id', 'permissions.name')->get(),
        ]);
    }

    /**
     * Remove one or more permissions from a role.
     * body: { "permissions": ["edit posts"] }
     */
    public function revokePermissions(Request $request, $roleId)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rôle introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->permissions as $permissionName) {
            $role->revokePermissionTo($permissionName);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions retirées du rôle avec succès',
            'data' => $role->permissions()->select('permissions.id', 'permissions.name')->get(),
        ]);
    }
}