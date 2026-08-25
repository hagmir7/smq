<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function states()
    {
        $totalReclamations = \App\Models\Reclamation::count();
        $closedReclamations = \App\Models\Reclamation::whereNotNull('closing_date')->count();

        $closureRate = $totalReclamations > 0
            ? round(($closedReclamations / $totalReclamations) * 100, 2)
            : 0;

        return response()->json([
            'total_reclamations' => $totalReclamations,
            'open_reclamations' => \App\Models\Reclamation::whereNull('closing_date')->count(),
            'closed_reclamations' => $closedReclamations,
            'taux_cloture' => $closureRate,
            'corrective_actions' => \App\Models\CorrectiveAction::count(),
            'improvement_sheet' => \App\Models\ImprovementSheet::count(),
        ]);
    }

    /**
     * Count reclamations grouped by month (for the current year by default).
     * Adjust the 'created_at' column if your date field is named differently.
     */
    public function reclamationsPerMonth(Request $request)
    {
        $year = $request->get('year', date('Y'));

        $results = \App\Models\Reclamation::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupByRaw('MONTH(created_at)')   // <-- group by the expression, not the alias
            ->orderByRaw('MONTH(created_at)')   // <-- same here
            ->pluck('total', 'month');

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = $results[$i] ?? 0;
        }

        return response()->json([
            'year' => $year,
            'data' => $months,
        ]);
    }

    /**
     * Return the last 10 reclamations, most recent first.
     */
    public function lastReclamations()
    {
        $reclamations = \App\Models\Reclamation::with(['responsable', 'user'])
            ->withcount('correctiveActions')
            // ->orderBy('created_at', 'desc')
            
            ->latest()
            ->take(10)
            ->get();

        return response()->json($reclamations);
    }

    /**
     * Return reclamation status breakdown as percentages:
     * Clôturées, Critique, En cours.
     *
     * Assumption: "Critique" is determined by a 'priority' or 'severity'
     * column (e.g. value 'critique'/'high'). Adjust the condition to match
     * your actual column/values.
     */
    public function reclamationStates(Request $request)
    {
        $year = $request->query('year');

        $query = \App\Models\Reclamation::query();

        if ($year) {
            $query->whereYear('created_at', $year);
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            return response()->json([
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ]);
        }

        $counts = (clone $query)
            ->selectRaw('workflow_step, count(*) as total')
            ->groupBy('workflow_step')
            ->pluck('total', 'workflow_step');

        $states = [];
        foreach (range(1, 5) as $step) {
            $count = $counts->get($step, 0);
            $states[$step] = round(($count / $total) * 100);
        }

        return response()->json($states);
    }






    public function notifications()
    {
        $new_recalmations_count = \App\Models\Reclamation::whereNull('is_recevable')->count();

        $validated_reclamation_count = \App\Models\Reclamation::whereNull('processing_analysis')
            ->whereNotNull('is_recevable')
            ->count();


        $new_corrective_action = \App\Models\CorrectiveAction::whereNull('completion_date')
            ->where('responsable_id', auth()->id())
            ->count();

        
        $registered_improvement_sheets = \App\Models\ImprovementSheet::where('statut', 'Enregistré')
            ->count();

        return response()->json([
            'new_recalmations_count' => $new_recalmations_count,
            'validated_reclamation_count' => $validated_reclamation_count,
            'new_corrective_action' => $new_corrective_action,
            'registered_improvement_sheets' => $registered_improvement_sheets
        ]);
    }
}