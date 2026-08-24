<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class PersonController extends Controller
{


    public function index(Request $request): JsonResponse
    {
        $query = Person::with('user');

        // Admin and SMQ can filter by any user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Other users can only see their own Person records
        if (!auth()->user()->hasRole(['admin', 'smq'])) {
            $query->where('user_id', auth()->id());
        }

        return response()->json($query->get());
    }



    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:255',
            ],

            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('people', 'code'),
            ],
        ], [
            'full_name.required' => 'Le nom complet est obligatoire.',
            'full_name.string' => 'Le nom complet doit être une chaîne de caractères.',
            'full_name.max' => 'Le nom complet ne peut pas dépasser 255 caractères.',

            'code.string' => 'Le code doit être une chaîne de caractères.',
            'code.max' => 'Le code ne peut pas dépasser 50 caractères.',
            'code.unique' => 'Ce code existe déjà.',
        ]);

        $person = Person::create([
            'full_name' => $validated['full_name'],
            'code' => $validated['code'] ?? null,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Personne créée avec succès.',
            'data' => $person,
        ], 201);
    }

}
