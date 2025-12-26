<?php

namespace App\Services;

use App\Models\Worker;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class WorkerPdfExportService
{
    /**
     * Generate PDF with workers data
     */
    public function generate(array $workerIds): \Barryvdh\DomPDF\PDF
    {
        $data = $this->prepareData($workerIds);

        $pdf = Pdf::loadView('worker.export_pdf', [
            'workers' => $data,
            'generatedAt' => now()->format('d.m.Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf;
    }

    /**
     * Prepare workers data for export
     */
    protected function prepareData(array $workerIds): array
    {
        $workers = Worker::whereIn('id', $workerIds)
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
                'age' => $this->calculateAge($worker->dob),
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
        })->toArray();
    }

    /**
     * Calculate age from date of birth
     */
    protected function calculateAge(?string $dob): string
    {
        if (!$dob) {
            return '-';
        }

        return (string) Carbon::parse($dob)->age;
    }

    /**
     * Calculate work duration from start date
     */
    protected function calculateWorkDuration(?string $startDate): string
    {
        if (!$startDate) {
            return '-';
        }

        $start = Carbon::parse($startDate);
        $diff = $start->diff(now());

        if ($diff->y > 0) {
            return $diff->y . ' ' . __('y.') . ' ' . $diff->m . ' ' . __('mo.');
        }

        if ($diff->m > 0) {
            return $diff->m . ' ' . __('mo.') . ' ' . $diff->d . ' ' . __('d.');
        }

        return $diff->d . ' ' . __('d.');
    }

    /**
     * Format gender value
     */
    protected function formatGender(?string $gender): string
    {
        return match ($gender) {
            'male' => __('Male'),
            'female' => __('Female'),
            default => '-',
        };
    }
}
