<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->with('responsible');

        // Optional search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        // Optional filter by responsible
        if ($request->filled('responsible_id')) {
            $query->where('responsible_id', $request->input('responsible_id'));
        }

        // Optional withCount for related resources
        if ($request->boolean('with_counts')) {
            $query->withCount([
                'improvementSheets',
                'improvementActions',
                'correctiveActions',
                'improvementSheetResponsibles',
            ]);
        }

        $perPage = (int) $request->input('per_page', 20);
        $services = $query->paginate($perPage);

        return response()->json([
            'data' => ServiceResource::collection($services),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'per_page' => $services->perPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServiceRequest $request): JsonResponse
    {
        $service = Service::create($request->validated());

        return response()->json([
            'message' => 'Service créé avec succès.',
            'data' => new ServiceResource($service->load('responsible')),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Service $service): JsonResponse
    {
        $service->load('responsible')
            ->loadCount([
                'improvementSheets',
                'improvementActions',
                'correctiveActions',
                'improvementSheetResponsibles',
            ]);

        return response()->json([
            'data' => new ServiceResource($service),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $service->update($request->validated());

        return response()->json([
            'message' => 'Service mis à jour avec succès.',
            'data' => new ServiceResource($service->load('responsible')),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Service $service): JsonResponse
    {
        $service->delete();

        return response()->json([
            'message' => 'Service supprimé avec succès.',
        ]);
    }
}