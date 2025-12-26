<?php

namespace App\Exports;

use App\Models\Hotel;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HotelSheetExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles, WithTitle
{
    protected array $hotelIds;

    public function __construct(array $hotelIds)
    {
        $this->hotelIds = $hotelIds;
    }

    public function title(): string
    {
        return 'Отели';
    }

    public function collection()
    {
        $hotels = Hotel::whereIn('id', $this->hotelIds)
            ->where('created_by', Auth::user()->creatorId())
            ->with(['rooms.currentAssignments.worker'])
            ->get();

        return $hotels->map(function ($hotel) {
            $totalCapacity = 0;
            $currentOccupancy = 0;
            $totalRooms = $hotel->rooms->count();
            $fullyOccupied = 0;
            $partiallyOccupied = 0;
            $freeRooms = 0;
            $workers = collect();

            foreach ($hotel->rooms as $room) {
                $totalCapacity += $room->capacity;
                $roomOccupancy = $room->currentAssignments->count();
                $currentOccupancy += $roomOccupancy;

                if ($roomOccupancy === 0) {
                    $freeRooms++;
                } elseif ($roomOccupancy >= $room->capacity) {
                    $fullyOccupied++;
                } else {
                    $partiallyOccupied++;
                }

                foreach ($room->currentAssignments as $assignment) {
                    if ($assignment->worker) {
                        $workers->push($assignment->worker->first_name . ' ' . $assignment->worker->last_name);
                    }
                }
            }

            return [
                'name' => $hotel->name,
                'address' => $hotel->address ?? '-',
                'current_occupancy' => $currentOccupancy,
                'total_capacity' => $totalCapacity,
                'total_rooms' => $totalRooms,
                'fully_occupied' => $fullyOccupied,
                'partially_occupied' => $partiallyOccupied,
                'free_rooms' => $freeRooms,
                'workers' => $workers->implode(', ') ?: '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Название',
            'Адрес',
            'Актуальная занятость',
            'Вместимость',
            'Кол-во комнат',
            'Полностью занятых',
            'Частично занятых',
            'Свободных',
            'Работники',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 35,
            'C' => 18,
            'D' => 14,
            'E' => 14,
            'F' => 18,
            'G' => 18,
            'H' => 14,
            'I' => 60,
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
