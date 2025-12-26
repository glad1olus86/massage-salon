<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\Room;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class HotelPdfExportService
{
    /**
     * Generate PDF with hotels and rooms data
     */
    public function generate(array $hotelIds): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareData($hotelIds);

        $pdf = Pdf::loadView('hotel.export_pdf', [
            'hotels' => $data['hotels'],
            'rooms' => $data['rooms'],
            'generatedAt' => now()->format('d.m.Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }

    /**
     * Prepare hotels and rooms data for export
     */
    protected function prepareData(array $hotelIds): array
    {
        $hotels = Hotel::whereIn('id', $hotelIds)
            ->where('created_by', Auth::user()->creatorId())
            ->with(['rooms.currentAssignments.worker'])
            ->get();

        $hotelsData = $hotels->map(function ($hotel) {
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
        })->toArray();

        $rooms = Room::whereIn('hotel_id', $hotelIds)
            ->whereHas('hotel', function ($q) {
                $q->where('created_by', Auth::user()->creatorId());
            })
            ->with(['hotel', 'currentAssignments.worker'])
            ->orderBy('hotel_id')
            ->orderBy('room_number')
            ->get();

        $roomsData = $rooms->map(function ($room) {
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
                'agency' => __('Agency'),
                'worker' => __('Worker'),
                'partial' => __('Partial'),
                default => '-',
            };

            return [
                'hotel' => $room->hotel->name ?? '-',
                'room_number' => $room->room_number,
                'current_occupancy' => $currentOccupancy,
                'capacity' => $capacity,
                'is_full' => $isFull ? __('Yes') : __('No'),
                'is_partial' => $isPartial ? __('Yes') : __('No'),
                'is_empty' => $isEmpty ? __('Yes') : __('No'),
                'monthly_price' => $room->monthly_price ?? '-',
                'payment_type' => $paymentType,
                'partial_amount' => $room->payment_type === 'partial' ? ($room->partial_amount ?? '-') : '-',
                'workers' => $workers ?: '-',
            ];
        })->toArray();

        return [
            'hotels' => $hotelsData,
            'rooms' => $roomsData,
        ];
    }
}
