<?php

namespace App\Exports;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RoomSheetExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles, WithTitle
{
    protected array $hotelIds;

    public function __construct(array $hotelIds)
    {
        $this->hotelIds = $hotelIds;
    }

    public function title(): string
    {
        return 'Комнаты';
    }

    public function collection()
    {
        $rooms = Room::whereIn('hotel_id', $this->hotelIds)
            ->whereHas('hotel', function ($q) {
                $q->where('created_by', Auth::user()->creatorId());
            })
            ->with(['hotel', 'currentAssignments.worker'])
            ->orderBy('hotel_id')
            ->orderBy('room_number')
            ->get();

        return $rooms->map(function ($room) {
            $currentOccupancy = $room->currentAssignments->count();
            $capacity = $room->capacity;
            
            $isFull = $currentOccupancy >= $capacity;
            $isPartial = $currentOccupancy > 0 && $currentOccupancy < $capacity;
            $isEmpty = $currentOccupancy === 0;

            $workers = $room->currentAssignments
                ->map(fn($a) => $a->worker ? $a->worker->first_name . ' ' . $a->worker->last_name : null)
                ->filter()
                ->implode(', ');

            $paymentType = match ($room->payment_type) {
                'agency' => 'Агенство',
                'worker' => 'Работник',
                'partial' => 'Частично',
                default => '-',
            };

            return [
                'hotel' => $room->hotel->name ?? '-',
                'room_number' => $room->room_number,
                'current_occupancy' => $currentOccupancy,
                'capacity' => $capacity,
                'is_full' => $isFull ? 'Да' : 'Нет',
                'is_partial' => $isPartial ? 'Да' : 'Нет',
                'is_empty' => $isEmpty ? 'Да' : 'Нет',
                'monthly_price' => $room->monthly_price ?? '-',
                'payment_type' => $paymentType,
                'partial_amount' => $room->payment_type === 'partial' ? ($room->partial_amount ?? '-') : '-',
                'workers' => $workers ?: '-',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Отель',
            'Номер комнаты',
            'Актуальная занятость',
            'Вместимость',
            'Занята полностью',
            'Занята частично',
            'Свободна',
            'Цена за месяц',
            'Кто платит',
            'Сумма частичной оплаты',
            'Работники',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 25,
            'B' => 15,
            'C' => 18,
            'D' => 14,
            'E' => 16,
            'F' => 16,
            'G' => 12,
            'H' => 15,
            'I' => 15,
            'J' => 22,
            'K' => 50,
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
