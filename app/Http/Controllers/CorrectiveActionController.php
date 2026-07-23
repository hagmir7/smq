<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CorrectiveAction;
use App\Models\Reclamation;
use App\Notifications\CorrectiveActionCreated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CorrectiveActionController extends Controller
{
    /**
     * List corrective actions, optionally scoped to a reclamation.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CorrectiveAction::query()
            ->with([
                'reclamation',
                'service',
                'responsable',
                'user',
                'parent',
                'children',
                'children.reclamation:id,code,client_code',
                'children.service',
                'children.responsable:id,full_name',
                'improvementSheets:id,code'
            ]);

        if ($request->filled('reclamation_id')) {
            $query->where('reclamation_id', $request->integer('reclamation_id'));
        }

        if ($request->filled('reclamation_code')) {
            $query->whereHas('reclamation', fn($r) =>
            $r->where('code', 'like', '%' . $request->input('reclamation_code') . '%'));
        }

        // NEW: single search box matching the corrective action's own code
        // OR its reclamation's code.
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('reclamation', fn($r) =>
                    $r->where('code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('effectiveness')) {
            $query->where('effectiveness', $request->string('effectiveness'));
        }

        // NEW: status filter
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // NEW: created_at date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        $query->whereNull('parent_id');
        $actions = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($actions);
    }

    /**
     * Show a single corrective action.
     */
    public function show(CorrectiveAction $correctiveAction): JsonResponse
    {
        return response()->json(
            $correctiveAction->load(['reclamation', 'service', 'responsable', 'user', 'parent', 'children'])
        );
    }

 


    /**
     * Update a corrective action (edit details, reassign, reschedule).
     */
    public function update(Request $request, CorrectiveAction $correctiveAction): JsonResponse
    {
        $validated = $request->validate([
            'description'             => ['sometimes', 'string'],
            'type'                    => ['sometimes', 'string', 'max:30'],
            'effectiveness_criteria'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'due_date'                => ['sometimes', 'nullable', 'date'],
            'service_id'              => ['sometimes', 'nullable', 'exists:services,id'],
            'responsable_id'          => ['sometimes', 'nullable', 'exists:users,id'],
            'parent_id'               => ['sometimes', 'nullable', 'exists:corrective_actions,id'],
        ]);

        // Prevent an action from becoming its own parent/ancestor.
        if (!empty($validated['parent_id']) && (int) $validated['parent_id'] === $correctiveAction->id) {
            return response()->json([
                'message' => "Une action corrective ne peut pas être son propre parent.",
            ], 422);
        }

        $correctiveAction->update($validated);

        return response()->json([
            'message' => 'Action corrective mise à jour.',
            'data'    => $correctiveAction->fresh()->load(['service', 'responsable', 'user', 'parent']),
        ]);
    }

    /**
     * Mark a corrective action as completed and evaluate its effectiveness.
     */
    public function complete(Request $request, CorrectiveAction $correctiveAction): JsonResponse
    {
        $validated = $request->validate([
            'completion_date' => ['required', 'date'],
            'effectiveness'   => ['required', 'string', 'in:Efficace,Partiellement efficace,Non efficace'],
        ]);

        $correctiveAction->update([...$validated,
            'status' => 'Clôturée'
        ]);

        return response()->json([
            'message' => 'Action corrective clôturée.',
            'data'    => $correctiveAction->fresh(),
        ]);
    }

    /**
     * Create a follow-up/child corrective action.
     */
    public function storeChild(Request $request, CorrectiveAction $correctiveAction): JsonResponse
    {
        if ($correctiveAction?->parent_id) {
            return response()->json([
                'message' => 'Impossible de créer une action de suivi à partir d\'une action de suivi.',
            ], 422);
        }

        $validated = $request->validate([
            'description'            => ['required', 'string'],
            'type'                   => ['nullable', 'string', 'max:30'],
            'effectiveness_criteria' => ['nullable', 'string', 'max:500'],
            'due_date'               => ['nullable', 'date'],
            'service_id'             => ['nullable', 'exists:services,id'],
            'responsable_id'         => ['nullable', 'exists:users,id'],
        ]);

        $child = CorrectiveAction::create([
            ...$validated,
            'code'           => $this->generateNextCode(),
            'type'           => $validated['type'] ?? 'Action corrective',
            'reclamation_id' => $correctiveAction->reclamation_id,
            'parent_id'      => $correctiveAction->id,
            'user_id'        => Auth::id(),
            'status'         => !empty($validated['responsable_id']) ? 'Affectée' : 'Créée',
        ]);

        $correctiveAction->update([
            'status' => 'En cours',
        ]);

        $correctiveAction->responsable->notify(new CorrectiveActionCreated($correctiveAction));

        return response()->json([
            'message' => 'Action corrective de suivi créée.',
            'data'    => $child->load(['service', 'responsable', 'user', 'parent']),
        ], 201);
    }

    public function destroy(CorrectiveAction $correctiveAction): JsonResponse
    {
        $correctiveAction->delete();

        return response()->json(['message' => 'Action corrective supprimée.']);
    }

    /**
     * Generate the next unique numeric code (e.g. 1, 2, 3 ...).
     * Uses a lock to avoid race conditions under concurrent requests.
     */
    private function generateNextCode(): string
    {
        return DB::transaction(function () {
            $lastCode = CorrectiveAction::lockForUpdate()
                ->orderByDesc('id')
                ->value('code');

            $lastNumber = $lastCode
                ? (int) substr($lastCode, 2) // Remove "AC"
                : 0;

            return 'AC' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        });
    }
}