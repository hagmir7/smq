<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImprovementJournal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ImprovementJournalController extends Controller
{
    /**
     * List journal entries — this is a read-only, auto-synced log.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ImprovementJournal::query()->with('source');

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->string('source_type'));
        }

        if ($request->filled('process')) {
            $query->where('process', $request->string('process'));
        }

        $journals = $query->latest()->paginate($request->integer('per_page', 15));

        return response()->json($journals);
    }

    public function show(ImprovementJournal $improvementJournal): JsonResponse
    {
        return response()->json($improvementJournal->load('source'));
    }
}
