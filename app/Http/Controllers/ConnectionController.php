<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ConnectionController extends Controller
{
    /**
     * List all connections.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Connection::query();

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        $connections = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($connections);
    }

    /**
     * Show a single connection.
     */
    public function show(Connection $connection): JsonResponse
    {
        return response()->json($connection);
    }

    /**
     * Create a new connection.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server'      => ['required', 'string', 'max:255'],
            'username'    => ['required', 'string', 'max:255'],
            'password'    => ['required_if:auth_win,false', 'nullable', 'string', 'max:255'],
            'auth_win'    => ['sometimes', 'boolean'],
        ]);

        $connection = Connection::create($validated);

        return response()->json([
            'message' => 'Connexion créée avec succès.',
            'data'    => $connection,
        ], 201);
    }

    /**
     * Update an existing connection.
     */
    public function update(Request $request, Connection $connection): JsonResponse
    {
        $validated = $request->validate([
            'server'   => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255'],
            'password' => ['sometimes', 'nullable', 'string', 'max:255'],
            'auth_win' => ['sometimes', 'boolean'],
        ]);

        // Don't overwrite the password if the client sent an empty value
        // (e.g. a form that displays a masked/blank password field).
        if (array_key_exists('password', $validated) && empty($validated['password'])) {
            unset($validated['password']);
        }

        $connection->update($validated);

        return response()->json([
            'message' => 'Connexion mise à jour.',
            'data'    => $connection->fresh(),
        ]);
    }

    /**
     * Test a connection and update its status.
     * NOTE: implement the real check inside testConnection() for your DB driver.
     */
    public function test(Connection $connection): JsonResponse
    {
        $isSuccessful = $this->testConnection($connection);

        $connection->update(['status' => $isSuccessful]);

        return response()->json([
            'message' => $isSuccessful
                ? 'Connexion réussie.'
                : 'Échec de la connexion.',
            'status'  => $isSuccessful,
        ], $isSuccessful ? 200 : 422);
    }

    /**
     * Delete a connection.
     */
    public function destroy(Connection $connection): JsonResponse
    {
        $connection->delete();

        return response()->json(['message' => 'Connexion supprimée.']);
    }

    /**
     * Attempt an actual connection using the stored credentials.
     * Adjust the driver/config to match your target DB (sqlsrv, mysql, etc.).
     */
    private function testConnection(Connection $connection): bool
    {
        try {
            config([
                'database.connections.dynamic_test' => [
                    'driver'   => 'sqlsrv',
                    'host'     => $connection->server,
                    'database' => 'master',
                    'username' => $connection->auth_win ? null : $connection->username,
                    'password' => $connection->auth_win ? null : $connection->password,
                    'trust_server_certificate' => true,
                ],
            ]);

            \DB::connection('dynamic_test')->getPdo();

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        } finally {
            \DB::purge('dynamic_test');
        }
    }
}