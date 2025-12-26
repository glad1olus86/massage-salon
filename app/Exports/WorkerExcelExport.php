<?php

namespace App\Exports;

use App\Models\Worker;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorkerExcelExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles
{
    protected array $workerIds;

    public function __construct(array $workerIds)
    {
        $this->workerIds = $workerIds;
    }

    public function collection()
    {
        $workers = Worker::whereIn('id', $this->workerIds)
            ->where('created_by', Auth::user()->creatorId())
            ->with([
                'currentAssignment.hotel',
                'currentAssignment.room',
                'currentAssignment.creator',
                'currentWorkAssignment.workPlace',
                'currentWorkAssignment.creator',
            ])
            ->get();

        return $workers->map(function ($worker) {
            $assignment = $worker->currentAssignment;
            $workAssignment = $worker->currentWorkAssignment;

            return [
                'first_name' => $worker->first_name,
                'last_name' => $worker->last_name,
                'dob' => $worker->dob ? Carbon::parse($worker->dob)->format('d.m.Y') : '-',
                'age' => $worker->dob ? Carbon::parse($worker->dob)->age : '-',
                'gender' => $this->formatGender($worker->gender),
                'nationality' => $worker->nationality ?? '-',
                'registration_date' => $worker->registration_date 
                    ? Carbon::parse($worker->registration_date)->format('d.m.Y') 
                    : '-',
                'hotel' => $assignment?->hotel?->name ?? '-',
                'room' => $assignment?->room?->room_number ?? '-',
                'check_in_date' => $assignment?->check_in_date 
                    ? Carbon::parse($assignment->check_in_date)->format('d.m.Y') 
                    : '-',
                'checked_in_by' => $assignment?->creator?->name ?? '-',
                'work_place' => $workAssignment?->workPlace?->name ?? '-',
                'work_started_at' => $workAssignment?->started_at 
                    ? Carbon::parse($workAssignment->started_at)->format('d.m.Y') 
                    : '-',
                'work_duration' => $this->calculateWorkDuration($workAssignment?->started_at),
                'work_assigned_by' => $workAssignment?->creator?->name ?? '-',
            ];
        });
    }


    public function headings(): array
    {
        return [
            'Имя',
            'Фамилия',
            'Дата рождения',
            'Возраст',
            'Пол',
            'Национальность',
            'Дата регистрации',
            'Отель',
            'Комната',
            'Дата заселения',
            'Кем заселён',
            'Место работы',
            'Дата трудоустройства',
            'Время работы',
            'Кем устроен',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Имя
            'B' => 15, // Фамилия
            'C' => 14, // Дата рождения
            'D' => 10, // Возраст
            'E' => 12, // Пол
            'F' => 15, // Национальность
            'G' => 16, // Дата регистрации
            'H' => 20, // Отель
            'I' => 10, // Комната
            'J' => 14, // Дата заселения
            'K' => 18, // Кем заселён
            'L' => 20, // Место работы
            'M' => 18, // Дата трудоустройства
            'N' => 14, // Время работы
            'O' => 18, // Кем устроен
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

    protected function formatGender(?string $gender): string
    {
        return match ($gender) {
            'male' => 'Мужчина',
            'female' => 'Женщина',
            default => '-',
        };
    }

    protected function calculateWorkDuration(?string $startDate): string
    {
        if (!$startDate) {
            return '-';
        }

        $start = Carbon::parse($startDate);
        $diff = $start->diff(now());

        if ($diff->y > 0) {
            return $diff->y . ' г. ' . $diff->m . ' мес.';
        }

        if ($diff->m > 0) {
            return $diff->m . ' мес. ' . $diff->d . ' дн.';
        }

        return $diff->d . ' дн.';
    }
}
