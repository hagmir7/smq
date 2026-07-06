<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    /**
     * List clients with optional search.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        if ($request->filled('search')) {
            $search = $request->string('search');

            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $clients = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($clients);
    }

    /**
     * Show a single client.
     */
    public function show(Client $client): JsonResponse
    {
        return response()->json($client);
    }

    /**
     * Create a new client.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code'         => ['required', 'string', 'max:20', 'unique:clients,code'],
            'company_name' => ['nullable', 'string', 'max:30'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'email'        => ['nullable', 'email', 'max:255'],
            'address'      => ['nullable', 'string', 'max:500'],
        ]);

        $client = Client::create($validated);

        return response()->json([
            'message' => 'Client créé avec succès.',
            'data'    => $client,
        ], 201);
    }

    /**
     * Update an existing client.
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        $validated = $request->validate([
            'code'         => [
                'sometimes', 'string', 'max:20',
                Rule::unique('clients', 'code')->ignore($client->id),
            ],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:30'],
            'phone'        => ['sometimes', 'nullable', 'string', 'max:30'],
            'email'        => ['sometimes', 'nullable', 'email', 'max:255'],
            'address'      => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $client->update($validated);

        return response()->json([
            'message' => 'Client mis à jour.',
            'data'    => $client->fresh(),
        ]);
    }

    /**
     * Delete a client.
     */
    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(['message' => 'Client supprimé.']);
    }
}