<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImprovementJournal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImprovementJournalController extends Controller
{
    /**
     * List journal entries — fully auto-synced from
     * ImprovementSheet and ImprovementAction, read-only.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ImprovementJournal::query()->with('source');

        if ($request->filled('process')) {
            $query->where('process', 'like', '%' . $request->string('process') . '%');
        }

        if ($request->filled('finding_source')) {
            $query->where('finding_source', 'like', '%' . $request->string('finding_source') . '%');
        }

        if ($request->filled('responsible')) {
            $query->where('responsible', 'like', '%' . $request->string('responsible') . '%');
        }

        if ($request->filled('effectiveness')) {
            $query->where('effectiveness', $request->boolean('effectiveness'));
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->string('source_type'));
        }

        $journals = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($journals);
    }

    /**
     * Show a single journal entry.
     */
    public function show(ImprovementJournal $improvementJournal): JsonResponse
    {
        return response()->json($improvementJournal->load('source'));
    }
}