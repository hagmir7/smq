<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImprovementAction;
use App\Models\ImprovementSheet;
use App\Models\ImprovementSheetResponsible;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImprovementSheetController extends Controller
{
    /**
     * List improvement sheets with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ImprovementSheet::query()
            ->with(['correctiveAction', 'responsable', 'service']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('responsable_id')) {
            $query->where('responsable_id', $request->integer('responsable_id'));
        }

        if ($request->filled('corrective_action_id')) {
            $query->where('corrective_action_id', $request->integer('corrective_action_id'));
        }

        if ($request->boolean('closed_only')) {
            $query->where('closed', true);
        }

        if ($request->boolean('open_only')) {
            $query->where(function ($q) {
                $q->whereNull('closed')->orWhere('closed', false);
            });
        }

        $sheets = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($sheets);
    }

    /**
     * Show a single improvement sheet.
     */
    public function show(ImprovementSheet $improvementSheet): JsonResponse
    {
        return response()->json(
            $improvementSheet->load(['correctiveAction', 'responsable', 'service'])
        );
    }

    /**
     * Create a new improvement sheet.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'corrective_action_id'     => ['nullable', 'exists:corrective_actions,id'],
            'finding_source'           => ['nullable', 'string', 'max:100'],
            'description'              => ['nullable', 'string'],
            'cause_analysis'           => ['nullable', 'string'],
            'title'                    => ['nullable', 'string', 'max:500'],
            'responsable_id'           => ['nullable', 'exists:users,id'],
            'service_id'               => ['nullable', 'exists:services,id'],
            'impact'                   => ['nullable', 'string', 'max:20'],
            'statut'                   => ['nullable', 'string', 'max:30'],
        ]);

        $sheet = DB::transaction(function () use ($validated) {
            return ImprovementSheet::create([
                ...$validated,
                'code'           => $this->generateNextCode(),
                'finding_source' => $validated['finding_source'] ?? 'Action corrective',
                'statut'         => $validated['statut'] ?? 'Planifié',
            ]);
        });

        return response()->json([
            'message' => 'Fiche d\'amélioration créée avec succès.',
            'data'    => $sheet->load(['correctiveAction', 'responsable', 'service']),
        ], 201);
    }

    /**
     * Update an improvement sheet (details, assignment, planning).
     */
    public function update(Request $request, ImprovementSheet $improvementSheet): JsonResponse
    {
        $validated = $request->validate([
            'corrective_action_id' => ['sometimes', 'nullable', 'exists:corrective_actions,id'],
            'finding_source'       => ['sometimes', 'string', 'max:100'],
            'description'          => ['sometimes', 'nullable', 'string'],
            'cause_analysis'       => ['sometimes', 'nullable', 'string'],
            'title'                => ['sometimes', 'nullable', 'string', 'max:500'],
            'responsable_id'       => ['sometimes', 'nullable', 'exists:users,id'],
            'service_id'           => ['sometimes', 'nullable', 'exists:services,id'],
            'impact'               => ['sometimes', 'nullable', 'string', 'max:20'],
            'statut'               => ['sometimes', 'string', 'max:30'],
        ]);

        $improvementSheet->update($validated);

        return response()->json([
            'message' => 'Fiche d\'amélioration mise à jour.',
            'data'    => $improvementSheet->fresh()->load(['correctiveAction', 'responsable', 'service']),
        ]);
    }

    /**
     * Evaluate and close an improvement sheet.
     */
    public function evaluate(Request $request, ImprovementSheet $improvementSheet): JsonResponse
    {
        $validated = $request->validate([
            'effectiveness'            => ['required', 'boolean'],
            'observation_description'  => ['required', 'string'],
            'observation_date'         => ['required', 'date'],
            'closed'                   => ['required', 'boolean'],
            'closing_date'             => ['nullable', 'date', 'required_if:closed,true'],
        ]);

        $improvementSheet->update([
            'effectiveness'            => $validated['effectiveness'],
            'observation_description'  => $validated['observation_description'],
            'observation_date'         => $validated['observation_date'],
            'closed'                   => $validated['closed'],
            'closing_date'             => $validated['closed'] ? ($validated['closing_date'] ?? now()) : null,
            'statut'                   => $validated['closed'] ? 'Clôturée' : 'En cours',
        ]);

        return response()->json([
            'message' => 'Évaluation enregistrée.',
            'data'    => $improvementSheet->fresh(),
        ]);
    }


    /**
     * List improvement actions for a specific improvement sheet.
     */
    public function improvementActions(ImprovementSheet $improvementSheet): JsonResponse
    {
        $actions = $improvementSheet->improvementActions()
            ->with(['responsable', 'service'])
            ->latest()
            ->get();

        return response()->json($actions);
    }

    /**
     * Create an improvement action (linked to an improvement sheet).
     */
    public function improvementActionsStore(Request $request, ImprovementSheet $improvementSheet): JsonResponse
    {
        $validated = $request->validate([
            'description'             => ['required', 'string'],
            'responsable_id'          => ['required', 'exists:users,id'],
            'service_id'              => ['required', 'exists:services,id'],
            'effectiveness_criteria'  => ['nullable', 'string', 'max:500'],
            'due_date'                => ['nullable', 'date'],
        ]);

        $action = DB::transaction(function () use ($validated, $improvementSheet) {
            return ImprovementAction::create([
                ...$validated,
                'code'                  => $this->generateImprovementActionsCode(),
                'improvement_sheet_id'  => $improvementSheet->id,
            ]);
        });

        return response()->json([
            'message' => 'Action d\'amélioration créée avec succès.',
            'data'    => $action->load(['responsable', 'service']),
        ], 201);
    }



    /**
     * Delete an improvement sheet.
     */
    public function destroy(ImprovementSheet $improvementSheet): JsonResponse
    {
        $improvementSheet->delete();

        return response()->json(['message' => 'Fiche d\'amélioration supprimée.']);
    }

    /**
     * Generate the next unique code (e.g. FA-0001, FA-0002 ...).
     */
    private function generateNextCode(): string
    {
        return DB::transaction(function () {
            $lastCode = ImprovementSheet::lockForUpdate()
                ->where('code', 'like', 'FA-%')
                ->orderByDesc('id')
                ->value('code');

            $nextNumber = $lastCode
                ? ((int) substr($lastCode, 3)) + 1
                : 1;

            return 'FA-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

        /**
     * List all responsibles for a given improvement sheet.
     */
    public function responsibles(ImprovementSheet $improvementSheet): JsonResponse
    {
        $responsibles = $improvementSheet->responsibles()
            ->with(['service', 'responsable'])
            ->get();

        return response()->json($responsibles);
    }

    /**
     * Assign a responsible (with a service) to an improvement sheet.
     */
    public function responsiblesStore(Request $request, ImprovementSheet $improvementSheet): JsonResponse
    {
        $validated = $request->validate([
            'service_id'     => ['required', 'exists:services,id'],
            'responsable_id' => ['required', 'exists:users,id'],
        ]);

        $exists = ImprovementSheetResponsible::where('improvement_sheet_id', $improvementSheet->id)
            ->where('service_id', $validated['service_id'])
            ->where('responsable_id', $validated['responsable_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ce responsable est déjà assigné à cette fiche pour ce service.',
            ], 422);
        }

        $mapping = ImprovementSheetResponsible::create([
            ...$validated,
            'improvement_sheet_id' => $improvementSheet->id,
        ]);

        return response()->json([
            'message' => 'Responsable assigné avec succès.',
            'data'    => $mapping->load(['service', 'responsable']),
        ], 201);
    }

    /**
     * Generate the next unique code (e.g. AA-0001, AA-0002 ...).
     */
    private function generateImprovementActionsCode(): string
    {
        return DB::transaction(function () {
            $lastCode = ImprovementAction::lockForUpdate()
                ->where('code', 'like', 'AA-%')
                ->orderByDesc('id')
                ->value('code');

            $nextNumber = $lastCode
                ? ((int) substr($lastCode, 3)) + 1
                : 1;

            return 'AA-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }
}
