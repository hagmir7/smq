<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{

    public function index(): JsonResponse
    {
        return response()->json(User::with('service')->paginate(15));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'full_name' => $request->input('full_name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'company_id' => $request->input('company_id'),
                'service_id' => $request->input('service_id'),
                'code' => $request->input('code'),
                'is_active' => true,
            ]);

            if ($request->filled('roles')) {
                $user->assignRole($request->input('roles'));
            }

            return $user;
        });

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
        ], 201);
    }


    public function show(User $user) : JsonResponse
    {
         $user->load('company:id,name', 'service:id,name');
        return response()->json($user->withMergedPermissions());
    }


    public function roles(User $user)
    {
        return response()->json($user->withMergedPermissions());
    }
    
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->update($request->only([
            'full_name',
            'email',
            'company_id',
            'service_id',
            'code',
        ]));

        if ($request->filled('roles')) {
            $user->syncRoles($request->input('roles'));
        }

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => __("Informations d'identification non valides"),
            ], 401);
        }

        $user = Auth::user();

        // Prevent inactive users from logging in
        if (! $user->is_active) {
            Auth::logout();

            return response()->json([
                'message' => __("Votre compte est désactivé. Veuillez contacter l'administrateur."),
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->withMergedPermissions(),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect']);
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return back()->with('success', 'Mot de passe mis à jour avec succès!');
    }

    /**
     * Update password for a specific user (admin only).
     */
    public function updateUserPassword(UpdateUserPasswordRequest $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        return response()->json(['message' => 'Mot de passe mis à jour avec succès']);
    }




    public function destroy(User $user): JsonResponse
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        // Delete all Sanctum tokens
        $user->tokens()->delete();


        if (!$user->is_active) {
            $user->delete();
        } else {
            $user->update([
                'is_active' => false,
            ]);
        }

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

        /**
     * Assign one or more permissions directly to a user.
     * body: { "permissions": ["edit posts", "delete posts"] }
     */
    public function assignPermissions(Request $request, $userId)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->givePermissionTo($request->permissions);

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions assignées à l\'utilisateur avec succès',
            'data' => $user->getDirectPermissions()->select('id', 'name'),
        ]);
    }

    /**
     * Replace ALL direct permissions on a user with the given list.
     * body: { "permissions": ["edit posts", "delete posts"] }
     */
    public function syncPermissions(Request $request, $userId)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->syncPermissions($request->permissions ?? []);

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions de l\'utilisateur synchronisées avec succès',
            'data' => $user->getDirectPermissions()->select('id', 'name'),
        ]);
    }

    /**
     * Remove one or more direct permissions from a user.
     * body: { "permissions": ["edit posts"] }
     */
    public function revokePermissions(Request $request, $userId)
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Utilisateur introuvable',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->permissions as $permissionName) {
            $user->revokePermissionTo($permissionName);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Permissions retirées de l\'utilisateur avec succès',
            'data' => $user->getDirectPermissions()->select('id', 'name'),
        ]);
    }


        public function responsibles(): JsonResponse
        {
            $responsibleIds = Service::whereNotNull('responsible_id')
                ->pluck('responsible_id')
                ->unique();

            return response()->json(User::whereIn('id', $responsibleIds)->get());
        }

}
