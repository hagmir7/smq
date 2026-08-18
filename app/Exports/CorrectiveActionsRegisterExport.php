<?php

namespace App\Exports;

use App\Models\CorrectiveAction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Database\Eloquent\Builder;

/**
 * Excel export for the "Registre des réclamations" — mirrors the flat
 * register view (CorrectiveActionsRegister.jsx): one row per corrective
 * action, sub-actions excluded. Filters are identical to
 * CorrectiveActionController@index so the export always matches whatever
 * is currently on screen.
 */
class CorrectiveActionsRegisterExport implements
    FromQuery,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithColumnWidths,
    WithTitle,
    WithCustomStartCell
{
    private const HEADER_COLOR = '365F91';

    public function __construct(private Request $request)
    {
    }

    public function query(): Builder
    {
        // Same filtering rules as CorrectiveActionController@index.
        // If you extract that logic into a model scope (recommended —
        // see scopeFiltered() note below), replace this block with:
        //   return CorrectiveAction::query()->filtered($this->request)->whereNull('parent_id');
        $request = $this->request;

        $query = CorrectiveAction::query()
            ->with([
                'reclamation',
                'service',
                'responsable',
            ]);

        if ($request->filled('reclamation_id')) {
            $query->where('reclamation_id', $request->integer('reclamation_id'));
        }

        if ($request->filled('reclamation_code')) {
            $query->whereHas('reclamation', fn ($r) =>
                $r->where('code', 'like', '%' . $request->input('reclamation_code') . '%'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhereHas('reclamation', fn ($r) =>
                        $r->where('code', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->integer('service_id'));
        }

        if ($request->filled('effectiveness')) {
            $query->where('effectiveness', $request->string('effectiveness'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date('date_to'));
        }

        // The register is always the flat, root-level view.
        $query->whereNull('parent_id');

        if (auth()->user()->hasRole('pilote')) {
            $query->where('responsable_id', auth()->id());
        }

        return $query->latest();
    }

    public function headings(): array
    {
        return [
            'Référence Réclamation',
            'Date Réclamation',
            "Date d'enregistrement",
            'Client',
            'Objet de la réclamation',
            'Actions correctives',
            'Responsable',
            'Processus concerné',
            'Date de réalisation',
            "Fiche d'amélioration",
            'Date de clôture',
        ];
    }

    public function map($action): array
    {
        return [
            $action->reclamation?->code ?? '—',
            optional($action->reclamation?->claimant_date)->format('d/m/Y') ?? '—',
            optional($action->reclamation?->registration_date)->format('d/m/Y') ?? '—',
            $action->reclamation?->client_code ?? '—',
            $action->reclamation?->object ?? '—',
            $action->code ?? '—', // see note: swap for a description field if you have one
            $action->responsable?->full_name ?? '—',
            $action->service?->name ?? '—',
            optional($action->completion_date)->format('d/m/Y') ?? '—',
            $action->reclamation?->improvement_sheets ?? '—',
            optional($action->reclamation?->closing_date)->format('d/m/Y') ?? '—',
        ];
    }

    public function title(): string
    {
        return 'Registre des réclamations';
    }

    // Leave row 1 free so the sheet has a little breathing room above the
    // header — drop this interface + method if you'd rather headings sit
    // in row 1.
    public function startCell(): string
    {
        return 'A2';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Référence Réclamation
            'B' => 14, // Date Réclamation
            'C' => 16, // Date d'enregistrement
            'D' => 12, // Client
            'E' => 32, // Objet de la réclamation
            'F' => 22, // Actions correctives
            'G' => 20, // Responsable
            'H' => 18, // Processus concerné
            'I' => 16, // Date de réalisation
            'J' => 18, // Fiche d'amélioration
            'K' => 16, // Date de clôture
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = 'K';
                $headerRow = 2;
                $lastRow = $sheet->getHighestRow();

                $headerRange = "A{$headerRow}:{$lastColumn}{$headerRow}";

                // White bold text on #365F91 background, matching the
                // Word template.
                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => self::HEADER_COLOR],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(30);

                // Thin borders across the whole table, like the template.
                $dataRange = "A{$headerRow}:{$lastColumn}{$lastRow}";
                $sheet->getStyle($dataRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'B7B7B7'],
                        ],
                    ],
                ]);

                // Center data cells, vertically middle.
                if ($lastRow > $headerRow) {
                    $sheet->getStyle("A" . ($headerRow + 1) . ":{$lastColumn}{$lastRow}")
                        ->getAlignment()
                        ->setVertical(Alignment::VERTICAL_CENTER);
                }

                $sheet->freezePane('A' . ($headerRow + 1));
            },
        ];
    }
}