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
use App\Exports\CorrectiveActionsRegisterExport;
use App\Models\User;
use App\Notifications\CorrectiveActionCompletionDate;
use Maatwebsite\Excel\Facades\Excel;

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
                'person',
                'user',
                'parent',
                'children',
                'children.reclamation:id,code,client_code',
                'children.service',
                'children.person',
                'children.responsable:id,full_name',
                'improvementSheet:id,code'
            ]);

            if(!auth()->user()->hasRole(['admin', 'smq'])){
                $query->where('responsable_id', auth()->id());
            }
            
            $query->withCount('children')
            ->filtered($request);

        $actions = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($actions);
    }

    /**
     * Show a single corrective action.
     */
    public function show(CorrectiveAction $correctiveAction): JsonResponse
    {
        return response()->json(
            $correctiveAction->load(['reclamation', 'service', 'responsable', 'user', 'parent', 'children', 'person'])
        );
    }

 


    /**
     * Update a corrective action (edit details, reassign, reschedule).
     */
    public function update(Request $request, CorrectiveAction $correctiveAction): JsonResponse
    {
        $validated = $request->validate([
            'description'          => ['sometimes', 'string'],
            'type'                 => ['sometimes', 'string', 'max:30'],
            'effectiveness_criteria' => ['sometimes', 'nullable', 'string', 'max:500'],
            'due_date'             => ['sometimes', 'nullable', 'date'],
            'completion_date'      => ['sometimes', 'nullable', 'date'],
            'service_id'           => ['sometimes', 'nullable', 'exists:services,id'],
            'responsable_id'       => ['sometimes', 'nullable', 'exists:users,id'],
            'person_id'            => ['sometimes', 'nullable', 'exists:people,id'],
            'parent_id'            => ['sometimes', 'nullable', 'exists:corrective_actions,id'],
        ]);

        // Prevent an action from becoming its own parent.
        if (
            !empty($validated['parent_id']) &&
            (int) $validated['parent_id'] === $correctiveAction->id
        ) {
            return response()->json([
                'message' => "Une action corrective ne peut pas être son propre parent.",
            ], 422);
        }

        $correctiveAction->update($validated);


        // Notify SMQ users only when completion_date was sent.
        if (array_key_exists('completion_date', $validated) & (!auth()->user()->hasRole('smq'))) {
            $smqUsers = User::role('smq')->get();
   
            foreach ($smqUsers as $user) {
                $user->notify(new CorrectiveActionCompletionDate($correctiveAction));
            }
        }

        return response()->json([
            'message' => 'Action corrective mise à jour.',
            'data' => $correctiveAction->fresh()->load([
                'service',
                'responsable',
                'user',
                'parent'
            ]),
        ]);
    }

    /**
     * Mark a corrective action as completed and evaluate its effectiveness.
     */
    public function complete(Request $request, CorrectiveAction $correctiveAction): JsonResponse
    {
        $validated = $request->validate([
            'closing_date' => ['required', 'date'],
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
                'message' => 'Impossible de créer une action de suivi à partir d\'une sous-action.',
            ], 422);
        }

        $reclamation = $correctiveAction->reclamation;

        $validated = $request->validate([
            'description'            => ['required', 'string'],
            'type'                   => ['nullable', 'string', 'max:30'],
            'effectiveness_criteria' => ['nullable', 'string', 'max:500'],
            'due_date'               => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($reclamation) {
                    if (
                        $reclamation?->planned_closing_date &&
                        $value &&
                        \Carbon\Carbon::parse($value)->gt($reclamation->planned_closing_date)
                    ) {
                        $fail("La date d'échéance doit être antérieure ou égale à la date de clôture prévue de la réclamation ({$reclamation->planned_closing_date->format('d/m/Y')}).");
                    }
                },
            ],
            'service_id'             => ['nullable', 'exists:services,id'],
            'person_id'         => ['nullable', 'exists:people,id'],
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


    public function list(Request $request): JsonResponse
    {
        $query = CorrectiveAction::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->select('id', 'code', 'description')->limit(20)->latest()->get(),
        );
    }



    public function exportRegister(Request $request)
    {
        $filename = 'registre-des-reclamations-' . now()->format('Y-m-d') . '.xlsx';

        return Excel::download(new CorrectiveActionsRegisterExport($request), $filename);
    }
}