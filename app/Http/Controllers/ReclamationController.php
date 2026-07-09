<?php

namespace App\Http\Controllers;

use App\Models\CorrectiveAction;
use App\Models\Reclamation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReclamationController extends Controller
{
    /**
     * List reclamations (basic pagination + optional filters).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reclamation::query()->with('responsable', 'user');

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('workflow_step')) {
            $query->where('workflow_step', $request->integer('workflow_step'));
        }

        $reclamations = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($reclamations);
    }

    /**
     * Show a single reclamation.
     */
    public function show(Reclamation $reclamation): JsonResponse
    {
        return response()->json($reclamation->load('responsable', 'user', 'media'));
    }

    /**
     * STEP 1 — Create the reclamation (intake / registration).
     */
    public function storeStepOne(Request $request): JsonResponse
    {
        // Normalize: if a single file was sent as "attachments" (not attachments[]),
        // wrap it into an array so validation/storage works either way.
        if ($request->hasFile('attachments') && !is_array($request->file('attachments'))) {
            $request->merge([]); // no-op, just clarity
            $request->files->set('attachments', [$request->file('attachments')]);
        }

        $validated = $request->validate([
            'claimant_date'        => ['required', 'date'],
            'claimant_name'        => ['required', 'string', 'max:200'],
            'client_code'          => ['required', 'string', 'max:255'],
            'client_phone'         => ['nullable', 'string', 'max:30'],
            'client_email'         => ['nullable', 'email', 'max:255'],
            'client_company_name'  => ['nullable', 'string', 'max:200'],
            'reception_method'     => ['nullable', 'string', 'max:500'],
            'object'               => ['required', 'string', 'max:500'],
            'description'          => ['required', 'string'],
            'received_at'          => ['nullable', 'date'],

            'attachments'          => ['nullable', 'array', 'max:10'],
            'attachments.*'        => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        $reclamation = DB::transaction(function () use ($request, $validated) {
            $reclamation = Reclamation::create([
                ...collect($validated)->except('attachments')->all(),
                'code'            => $this->generateCode(),
                'user_id'       => Auth::id(),
                'statut'        => 'Ouverte',
                'workflow_step' => 1,
            ]);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $reclamation
                        ->addMedia($file)
                        ->usingFileName($file->getClientOriginalName())
                        ->toMediaCollection('attachments');
                }
            }

            return $reclamation;
        });

        return response()->json([
            'message' => 'Réclamation enregistrée avec succès (étape 1).',
            'data'    => $reclamation->load('media'),
        ], 201);
    }

    /**
     * Add attachments to an existing reclamation.
     */
    public function storeAttachments(Request $request, Reclamation $reclamation): JsonResponse
    {
        // Normalize: if a single file was sent as "attachments" instead of "attachments[]",
        // wrap it into an array so validation works either way.
        if ($request->hasFile('attachments') && !is_array($request->file('attachments'))) {
            $request->files->set('attachments', [$request->file('attachments')]);
        }

        $request->validate([
            'attachments'   => ['required', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
        ]);

        foreach ($request->file('attachments') as $file) {
            $reclamation->addMedia($file)
                ->usingFileName($file->getClientOriginalName())
                ->toMediaCollection('attachments');
        }

        return response()->json([
            'message' => 'Pièces jointes ajoutées.',
            'data'    => $reclamation->load('media'),
        ]);
    }

    /**
     * Delete a single attachment.
     */
    public function destroyAttachment(Reclamation $reclamation, int $mediaId): JsonResponse
    {
        $media = $reclamation->media()->findOrFail($mediaId);
        $media->delete();

        return response()->json(['message' => 'Pièce jointe supprimée.']);
    }

    /**
     * STEP 2 — Post-reception analysis / admissibility.
     */
    public function storeStepTwo(Request $request, Reclamation $reclamation): JsonResponse
    {
        $this->ensureStep($reclamation, 1);

        $validated = $request->validate([
            'post_analysis'     => ['required', 'string'],
            'is_recevable'      => ['required', 'boolean'],
            'corrective_action' => ['nullable', 'string', 'required_if:is_recevable,true'],
            'responsable_id'    => ['nullable', 'exists:users,id'],
        ]);

        $reclamation->update([
            'post_analysis'     => $validated['post_analysis'],
            'is_recevable'      => $validated['is_recevable'],
            'corrective_action' => $validated['corrective_action'] ?? null,
            'responsable_id'    => $validated['responsable_id'] ?? $reclamation->responsable_id,
            'statut'            => $validated['is_recevable'] ? 'En cours' : 'Clôturée',
            'workflow_step'     => 2,
            'closing_date'      => $validated['is_recevable'] ? null : now(),
        ]);

        return response()->json([
            'message' => 'Analyse de recevabilité enregistrée (étape 2).',
            'data'    => $reclamation->fresh(),
        ]);
    }

    /**
     * STEP 3 — Processing / root cause analysis / closing.
     */
    public function storeStepThree(Request $request, Reclamation $reclamation): JsonResponse
    {
        $this->ensureStep($reclamation, 2);

        if (!$reclamation->is_recevable) {
            return response()->json([
                'message' => "Cette réclamation n'est pas recevable, l'étape 3 n'est pas applicable.",
            ], 422);
        }

        $validated = $request->validate([
            'processing_analysis'   => ['required', 'string'],
            'is_justifiee'          => ['required', 'boolean'],
            'cause_analysis'        => ['nullable', 'string'],
            'priority'              => ['nullable', 'string', 'max:20'],
            'planned_closing_date'  => ['nullable', 'date'],
            'closing_date'          => ['nullable', 'date'],
        ]);

        $reclamation->update([
            'processing_analysis'  => $validated['processing_analysis'],
            'is_justifiee'         => $validated['is_justifiee'],
            'cause_analysis'       => $validated['cause_analysis'] ?? null,
            'priority'             => $validated['priority'] ?? $reclamation->priority,
            'planned_closing_date' => $validated['planned_closing_date'] ?? null,
            'closing_date'         => $validated['closing_date'] ?? now(),
            'statut'               => 'Clôturée',
            'workflow_step'        => 3,
        ]);

        return response()->json([
            'message' => 'Traitement finalisé, réclamation clôturée (étape 3).',
            'data'    => $reclamation->fresh(),
        ]);
    }

    /**
     * Generic update (e.g. editing fields without changing workflow step).
     */
    public function update(Request $request, Reclamation $reclamation): JsonResponse
    {
        $validated = $request->validate([
            'priority'       => ['sometimes', 'string', 'max:20'],
            'responsable_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'statut'         => ['sometimes', 'in:Ouverte,En cours,Clôturée'],
        ]);

        $reclamation->update($validated);

        return response()->json([
            'message' => 'Réclamation mise à jour.',
            'data'    => $reclamation->fresh(),
        ]);
    }

       /**
     * List corrective actions for a specific reclamation.
     */
    public function correctiveActions(Reclamation $reclamation): JsonResponse
    {
        $actions = $reclamation->correctiveActions()
            ->where('parent_id', null)
            ->with(['service', 'responsable', 'user', 'parent', 'children'])
            ->latest()
            ->get();

        return response()->json($actions);
    }

    
    /**
     * Create a corrective action (linked to a reclamation).
     */
    public function storeCorrectiveActions(Request $request, Reclamation $reclamation): JsonResponse
    {
        $validated = $request->validate([
            'description'             => ['required', 'string'],
            'type'                    => ['nullable', 'string', 'max:30'],
            'effectiveness_criteria'  => ['nullable', 'string', 'max:500'],
            'due_date'                => ['nullable', 'date'],
            'service_id'              => ['nullable', 'exists:services,id'],
            'responsable_id'          => ['nullable', 'exists:users,id'],
            'parent_id'               => ['nullable', 'exists:corrective_actions,id'],
        ]);

        $correctiveAction = DB::transaction(function () use ($validated, $reclamation) {
            return CorrectiveAction::create([
                ...$validated,
                'code'            => $this->generateNextCode(),
                'type'            => $validated['type'] ?? 'Action corrective',
                'reclamation_id'  => $reclamation->id,
                'user_id'         => Auth::id(),
            ]);
        });

        return response()->json([
            'message' => 'Action corrective créée avec succès.',
            'data'    => $correctiveAction->load(['service', 'responsable', 'user', 'parent']),
        ], 201);
    }




    

    public function destroy(Reclamation $reclamation): JsonResponse
    {
        $reclamation->delete();

        return response()->json(['message' => 'Réclamation supprimée.']);
    }

    /**
     * Guard: ensure the reclamation is at (or past) the expected step
     * before allowing the next step to be recorded.
     */
    private function ensureStep(Reclamation $reclamation, int $minStep): void
    {
        if ($reclamation->workflow_step < $minStep) {
            throw ValidationException::withMessages([
                'workflow_step' => "Vous devez d'abord compléter l'étape {$minStep}.",
            ]);
        }
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

    private function generateCode(): string
    {
        return DB::transaction(function () {
            $lastCode = CorrectiveAction::lockForUpdate()
                ->orderByDesc('id')
                ->value('code');

            $lastNumber = $lastCode
                ? (int) substr($lastCode, 2) // Remove "AC"
                : 0;

            return 'REC' . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        });
    }
}