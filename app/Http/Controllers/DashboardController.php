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
        $reclamations = \App\Models\Reclamation::orderBy('created_at', 'desc')
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
    public function reclamationStates()
    {
        $total = \App\Models\Reclamation::count();

        if ($total === 0) {
            return response()->json([
                'Clôturées' => 0,
                'Critique' => 0,
                'En cours' => 0,
            ]);
        }

        $closed = \App\Models\Reclamation::whereNotNull('closing_date')->count();

        $critical = \App\Models\Reclamation::whereNull('closing_date')
            ->where('priority', 'critique') // adjust column/value to your schema
            ->count();

        $inProgress = \App\Models\Reclamation::whereNull('closing_date')
            ->where(function ($query) {
                $query->where('priority', '!=', 'critique')
                    ->orWhereNull('priority');
            })
            ->count();

        return response()->json([
            'Clôturées' => round(($closed / $total) * 100),
            'Critique' => round(($critical / $total) * 100),
            'En cours' => round(($inProgress / $total) * 100),
        ]);
    }
}