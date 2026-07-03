<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateUserPasswordRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

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


    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $user->update($request->only([
            'name',
            'full_name',
            'email',
            'phone',
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

    public function usersByRole(string $role): JsonResponse
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $users = User::role($role)
            ->select('id', 'name', 'full_name', 'status')
            ->get();

        return response()->json($users);
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
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
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

    public function usersActions(): JsonResponse
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        return response()->json(
            User::withCount('movements')->get()
        );
    }



    public function destroy(User $user): JsonResponse
    {
        abort_unless(auth()->user()->hasRole('admin'), 403, "Vous n'êtes pas autorisé");

        $user->update(['is_active' => false]);

        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }
}
