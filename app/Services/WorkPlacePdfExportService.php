<?php

namespace App\Services;

use App\Models\WorkPlace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class WorkPlacePdfExportService
{
    /**
     * Generate PDF with work places data
     */
    public function generate(array $workPlaceIds): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareData($workPlaceIds);

        $pdf = Pdf::loadView('work_place.export_pdf', [
            'workPlaces' => $data,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }

    /**
     * Prepare work places data for export
     */
    protected function prepareData(array $workPlaceIds): array
    {
        $workPlaces = WorkPlace::whereIn('id', $workPlaceIds)
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
        })->toArray();
    }
}
