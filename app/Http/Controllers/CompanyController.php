<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $companies = Company::latest()->paginate(15);

        return response()->json($companies);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:companies,name',
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
            ],
            [],
            [
                'name' => "Nom de l'entreprise",
                'description' => 'Description',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $company = DB::transaction(function () use ($validator) {
            return Company::create($validator->validated());
        });

        return response()->json([
            'message' => 'Entreprise créée avec succès.',
            'data' => $company,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Company $company): JsonResponse
    {
        return response()->json([
            'data' => $company,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Company $company): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            [
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('companies', 'name')->ignore($company->id),
                ],
                'description' => [
                    'nullable',
                    'string',
                ],
            ],
            [],
            [
                'name' => "Nom de l'entreprise",
                'description' => 'Description',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::transaction(function () use ($validator, $company) {
            $company->update($validator->validated());
        });

        return response()->json([
            'message' => 'Entreprise mise à jour avec succès.',
            'data' => $company->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Company $company): JsonResponse
    {
        DB::transaction(function () use ($company) {
            $company->delete();
        });

        return response()->json([
            'message' => 'Société supprimée avec succès.',
        ]);
    }
}