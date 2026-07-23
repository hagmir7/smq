<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * French display labels for each derived category key.
     * Falls back to a capitalized version of the key if not listed here.
     */
    private const CATEGORY_LABELS = [
        'reclamation'          => 'Réclamations',
        'action_corrective'    => 'Actions Correctives',
        'fiche_amelioration'   => "Fiches d'Amélioration",
        'action_amelioration'  => "Actions d'Amélioration",
        'journal_amelioration' => "Journal d'Amélioration",
        'registre_reclamation' => 'Registre des Réclamations',
        'utilisateur'          => 'Utilisateurs',
        'connexion'            => 'Connexions',
        'processus'            => 'Processus',
        'role'                 => 'Rôles',
    ];

    public function index()
    {
        $permissions = Permission::select('id', 'name')->get()
            ->map(function ($permission) {
                $category = $this->extractCategory($permission->name);

                return [
                    'id'             => $permission->id,
                    'name'           => $permission->name,
                    'category'       => $category,
                    'category_label' => self::CATEGORY_LABELS[$category] ?? ucfirst(str_replace('_', ' ', $category)),
                ];
            })
            ->sortBy('category_label')
            ->values();

        return response()->json(['data' => $permissions]);
    }

    private function extractCategory(string $name): string
    {
        if (! str_contains($name, '.')) {
            $resource = str_contains($name, '_connexions') ? 'connexion' : $name;
            return $this->singularize($resource);
        }

        $resource = explode('.', $name, 2)[1];

        return $this->singularize($resource);
    }

    private function singularize(string $resource): string
    {
        $words = explode('_', $resource);

        $singular = array_map(function ($word) {
            return str_ends_with($word, 's') ? rtrim($word, 's') : $word;
        }, $words);

        return implode('_', $singular);
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
            'data' => $created->count() === 1 ? $created->first() : $created->values()
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

        return response()->json([
            'data' => $permission->users()->select('users.id', 'users.full_name', 'users.email')->get(),
        ]);
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

        return response()->json([
            'data' => $permission->roles()->select('roles.id', 'roles.name')->get(),
        ]);
    }
}