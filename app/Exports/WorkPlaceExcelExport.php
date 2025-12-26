<?php

namespace App\Exports;

use App\Models\WorkPlace;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkPlaceExcelExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    protected array $workPlaceIds;

    public function __construct(array $workPlaceIds)
    {
        $this->workPlaceIds = $workPlaceIds;
    }

    public function collection()
    {
        $workPlaces = WorkPlace::whereIn('id', $this->workPlaceIds)
            ->where('created_by', Auth::user()->creatorId())
            ->with(['currentAssignments.worker'])
            ->get();

        return $workPlaces->map(function ($workPlace) {
            $workers = $workPlace->currentAssignments
                ->map(fn($assignment) => $assignment->worker)
                ->filter()
                ->map(fn($worker) => $worker->first_name . ' ' . $worker->last_name)
                ->implode(', ');

            return [
                'name' => $workPlace->name,
                'address' => $workPlace->address ?? '-',
                'workers_count' => $workPlace->currentAssignments->count(),
                'workers' => $workers ?: '-',
                'phone' => $workPlace->phone ?? '-',
                'email' => $workPlace->email ?? '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Название',
            'Адрес',
            'Кол-во сотрудников',
            'Сотрудники',
            'Контактный телефон',
            'Контактная почта',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 35,
            'C' => 18,
            'D' => 50,
            'E' => 20,
            'F' => 30,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0'],
                ],
            ],
        ];
    }
}
