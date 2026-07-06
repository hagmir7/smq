<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImprovementSheet;
use App\Models\ImprovementSheetResponsible;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ImprovementSheetResponsibleController extends Controller
{
    /**
     * List mappings, optionally filtered.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ImprovementSheetResponsible::query()
            ->with(['improvementSheet', 'service', 'responsable']);

        if ($request->filled('improvement_sheet_id')) {
            $query->where('improvement_sheet_id', $request->integer('improvement_sheet_id'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->integer('responsable_id'));
        }

        $mappings = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($mappings);
    }

    /**
     * Show a single mapping.
     */
    public function show(ImprovementSheetResponsible $improvementSheetResponsible): JsonResponse
    {
        return response()->json(
            $improvementSheetResponsible->load(['improvementSheet', 'service', 'responsable'])
        );
    }


    /**
     * Update a mapping (reassign service or responsible).
     */
    public function update(Request $request, ImprovementSheetResponsible $improvementSheetResponsible): JsonResponse
    {
        $validated = $request->validate([
            'service_id'     => ['sometimes', 'exists:services,id'],
            'responsable_id' => ['sometimes', 'exists:users,id'],
        ]);

        $serviceId     = $validated['service_id'] ?? $improvementSheetResponsible->service_id;
        $responsableId = $validated['responsable_id'] ?? $improvementSheetResponsible->responsable_id;

        $duplicateExists = ImprovementSheetResponsible::where('improvement_sheet_id', $improvementSheetResponsible->improvement_sheet_id)
            ->where('service_id', $serviceId)
            ->where('responsable_id', $responsableId)
            ->where('id', '!=', $improvementSheetResponsible->id)
            ->exists();

        if ($duplicateExists) {
            return response()->json([
                'message' => 'Ce responsable est déjà assigné à cette fiche pour ce service.',
            ], 422);
        }

        $improvementSheetResponsible->update($validated);

        return response()->json([
            'message' => 'Assignation mise à jour.',
            'data'    => $improvementSheetResponsible->fresh()->load(['service', 'responsable']),
        ]);
    }

    /**
     * Remove a mapping (un-assign a responsible from a sheet).
     */
    public function destroy(ImprovementSheetResponsible $improvementSheetResponsible): JsonResponse
    {
        $improvementSheetResponsible->delete();

        return response()->json(['message' => 'Assignation supprimée.']);
    }
}