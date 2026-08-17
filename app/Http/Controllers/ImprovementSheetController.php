<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CorrectiveAction;
use App\Models\ImprovementAction;
use App\Models\ImprovementSheet;
use App\Models\ImprovementSheetResponsible;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Spatie\LaravelPdf\Facades\Pdf;


class ImprovementSheetController extends Controller
{
    /**
     * List improvement sheets with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ImprovementSheet::query()
            ->with(['correctiveAction', 'responsable', 'service', 'improvementActions.responsable', 'improvementActions.service']);

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
            $improvementSheet->load(['correctiveAction', 'improvementActions.responsable', 'improvementActions.service'])
        );
    }

    /**
     * Create a new improvement sheet.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'corrective_action_id'        => ['nullable', 'exists:corrective_actions,id'],
            'finding_source'              => ['nullable', 'string', 'max:100'],
            'description'                 => ['nullable', 'string'],
            'cause_analysis'              => ['nullable', 'string'],
            'title'                       => ['nullable', 'string', 'max:500'],
            'statut'                      => ['nullable', 'string', 'max:30'],

            'responsable_id'           => ['nullable', 'exists:users,id'],
            'service_id'               => ['nullable', 'exists:services,id'],

            'responsibles'                => ['required', 'array', 'min:1'],
            'responsibles.*.responsable_id' => ['required', 'exists:users,id'],
            'responsibles.*.service_id'     => ['nullable', 'exists:services,id'],
        ]);

        $sheet = DB::transaction(function () use ($validated) {
            $sheet = ImprovementSheet::create([
                ...collect($validated)->except('responsibles')->all(),
                'code'           => $this->generateNextCode(),
                'finding_source' => $validated['finding_source'] ?? 'Action corrective',
                'statut'         => $validated['statut'] ?? 'Planifié',
            ]);

            $sheet->responsibles()->createMany($validated['responsibles']);

            return $sheet;
        });

        return response()->json([
            'message' => 'Fiche d\'amélioration créée avec succès.',
            'data'    => $sheet->load(['correctiveAction', 'improvementActions.responsable', 'improvementActions.service']),
        ], 201);
    }

    /**
     * Update an improvement sheet (details, assignment, planning).
     */
    public function update(Request $request, ImprovementSheet $improvementSheet): JsonResponse
    {
        $validated = $request->validate([
            'corrective_action_id'           => ['sometimes', 'nullable', 'exists:corrective_actions,id'],
            'finding_source'                 => ['sometimes', 'string', 'max:100'],
            'description'                    => ['sometimes', 'nullable', 'string'],
            'cause_analysis'                 => ['sometimes', 'nullable', 'string'],
            'title'                          => ['sometimes', 'nullable', 'string', 'max:500'],
            'statut'                         => ['sometimes', 'string', 'max:30'],

            'responsable_id'           => ['nullable', 'exists:users,id'],
            'service_id'               => ['nullable', 'exists:services,id'],

            'responsibles'                   => ['sometimes', 'array', 'min:1'],
            'responsibles.*.responsable_id'  => ['required_with:responsibles', 'exists:users,id'],
            'responsibles.*.service_id'      => ['nullable', 'exists:services,id'],
        ]);

        DB::transaction(function () use ($validated, $improvementSheet) {
            $improvementSheet->update(collect($validated)->except('responsibles')->all());

            if (array_key_exists('responsibles', $validated)) {
                // Simplest reliable sync: wipe and re-insert.
                // Fine here since these rows carry no independent identity/history worth preserving.
                $improvementSheet->responsibles()->delete();
                $improvementSheet->responsibles()->createMany($validated['responsibles']);
            }
        });

        return response()->json([
            'message' => 'Fiche d\'amélioration mise à jour.',
            'data'    => $improvementSheet->fresh()->load(['correctiveAction', 'improvementActions.responsable', 'improvementActions.service']),
        ]);
    }

    /**
     * Evaluate and close an improvement sheet.
     */
    public function evaluate(Request $request, ImprovementSheet $improvementSheet): JsonResponse
    {
        // Peek at 'closed' before validation so we can branch the rest of the rules on it.
        // Tri-state: true = closed, false = not closed, null = "action complémentaire".
        $closedRaw = $request->has('closed') ? $request->input('closed') : null;
        $closed = $closedRaw === null ? null : filter_var($closedRaw, FILTER_VALIDATE_BOOLEAN);
        $isComplementaryAction = $closed === null;

        $validated = $request->validate([
            'effectiveness'            => ['required', 'boolean'],
            'observation_description'  => ['required', 'string'],
            'evaluation_date'          => ['required', 'date'],
            'closed'                   => ['present', 'nullable', 'boolean'],
            'closing_date'             => ['nullable', 'date', 'required_if:closed,true'],

            // Corrective action fields — required only when closed is null
            'description'      => [Rule::requiredIf($isComplementaryAction), 'nullable', 'string', 'max:500'],
            'due_date'          => [Rule::requiredIf($isComplementaryAction), 'nullable', 'date'],
            'service_id'        => ['nullable', 'exists:services,id'], // optional either way
            'responsable_id'    => [Rule::requiredIf($isComplementaryAction), 'nullable', 'exists:users,id'],
        ]);

        // Re-derive from validated data for consistency (booleans are now cast correctly)
        $closed = $validated['closed'];

        try {
            $improvementSheet = DB::transaction(function () use ($validated, $improvementSheet, $closed) {
                $improvementSheet->update([
                    'effectiveness'           => $validated['effectiveness'],
                    'observation_description' => $validated['observation_description'],
                    'evaluation_date'         => $validated['evaluation_date'],
                    'closed'                  => $closed,
                    'closing_date'            => $closed === true ? ($validated['closing_date'] ?? now()) : null,
                    'statut'                  => $closed === true ? 'Clôturée' : 'En cours',
                ]);

                if ($closed === null && !$improvementSheet->complementary_action) {
                    $correctiveAction = CorrectiveAction::create([
                        'description'     => $validated['description'],
                        'due_date'        => $validated['due_date'],
                        'service_id'      => $validated['service_id'] ?? null,
                        'responsable_id'  => $validated['responsable_id'],
                        'reclamation_id'  => $improvementSheet->correctiveAction?->reclamation_id,
                        'code'            => $this->generateACNextCode(),
                        'user_id'         => auth()->id(),
                    ]);

                    $improvementSheet->update([
                        'complementary_action' => $correctiveAction->id,
                    ]);
                }

                return $improvementSheet;
            });
        } catch (\Throwable $e) {
            report($e); // logs it via the exception handler

            return response()->json([
                'message' => "Une erreur est survenue lors de l'enregistrement de l'évaluation.",
            ], 500);
        }

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


    public function download(ImprovementSheet $improvementSheet)
    {

        $improvementSheet = $improvementSheet->load(['correctiveAction', 'responsable', 'service', 'improvementActions']);

        // return $improvementSheet;
        return Pdf::view('pdf.improvement-sheet', [
            'improvementSheet' => $improvementSheet,
        ])
            ->format('a4')
            // ->landscape()
            ->footerHtml('
                <div style="
                    font-size:15px;
                    text-align:center;
                    width:100%;
                    color:#555;
                ">
                    © Ce document ne doit être ni reproduit ni communiqué sans l’autorisation d’INTERCOCINA
                </div>
            ')->name(now()->format('Ymd_His') . '-improvementSheet.pdf');
    }

    private function generateACNextCode(): string
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
