<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImprovementAction;
use App\Models\ImprovementSheet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImprovementActionController extends Controller
{
    /**
     * List improvement actions, optionally filtered.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ImprovementAction::query()
            ->with(['improvementSheet', 'responsable', 'service']);

        if ($request->filled('improvement_sheet_id')) {
            $query->where('improvement_sheet_id', $request->integer('improvement_sheet_id'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->integer('responsable_id'));
        }

        if ($request->filled('effectiveness')) {
            $query->where('effectiveness', $request->string('effectiveness'));
        }

        if ($request->boolean('pending_only')) {
            $query->whereNull('completion_date');
        }

        $actions = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($actions);
    }

    /**
     * Show a single improvement action.
     */
    public function show(ImprovementAction $improvementAction): JsonResponse
    {
        return response()->json(
            $improvementAction->load(['improvementSheet', 'responsable', 'service'])
        );
    }



    /**
     * Update an improvement action (details, reassignment, rescheduling).
     */
    public function update(Request $request, ImprovementAction $improvementAction): JsonResponse
    {
        $validated = $request->validate([
            'description'             => ['sometimes', 'string'],
            'responsable_id'          => ['sometimes', 'exists:users,id'],
            'service_id'              => ['sometimes', 'exists:services,id'],
            'effectiveness_criteria'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'due_date'                => ['sometimes', 'nullable', 'date'],
        ]);

        $improvementAction->update($validated);

        return response()->json([
            'message' => 'Action d\'amélioration mise à jour.',
            'data'    => $improvementAction->fresh()->load(['responsable', 'service']),
        ]);
    }

    /**
     * Mark an improvement action as completed and evaluate its effectiveness.
     */
    public function complete(Request $request, ImprovementAction $improvementAction): JsonResponse
    {
        $validated = $request->validate([
            'completion_date' => ['required', 'date'],
            'effectiveness'   => ['required', 'string', 'in:Efficace,Partiellement efficace,Non efficace'],
        ]);

        $improvementAction->update($validated);

        return response()->json([
            'message' => 'Action d\'amélioration clôturée.',
            'data'    => $improvementAction->fresh(),
        ]);
    }

    /**
     * Delete an improvement action.
     */
    public function destroy(ImprovementAction $improvementAction): JsonResponse
    {
        $improvementAction->delete();

        return response()->json(['message' => 'Action d\'amélioration supprimée.']);
    }

 
}