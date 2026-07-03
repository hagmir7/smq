<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'responsible_id' => $this->responsible_id,
            'responsible' => $this->whenLoaded('responsible', function () {
                return [
                    'id' => $this->responsible->id,
                    'full_name' => $this->responsible->full_name,
                    'email' => $this->responsible->email,
                ];
            }),
            'counts' => [
                'improvement_sheets' => $this->whenCounted('improvementSheets'),
                'improvement_actions' => $this->whenCounted('improvementActions'),
                'corrective_actions' => $this->whenCounted('correctiveActions'),
                'improvement_sheet_responsibles' => $this->whenCounted('improvementSheetResponsibles'),
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}