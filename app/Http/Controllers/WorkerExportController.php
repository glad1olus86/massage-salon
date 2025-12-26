<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use App\Exports\WorkerExcelExport;
use App\Services\WorkerPdfExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class WorkerExportController extends Controller
{
    /**
     * Show export modal with workers list
     * GET /worker/export
     */
    public function showExportModal()
    {
        if (!Auth::user()->can('manage worker')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $workers = Worker::where('created_by', Auth::user()->creatorId())
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();

            return view('worker.export_modal', compact('workers'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export selected workers to PDF
     * POST /worker/export/pdf
     */
    public function exportPdf(Request $request)
    {
        if (!Auth::user()->can('manage worker')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workerIds = $request->input('worker_ids', []);
        
        if (empty($workerIds)) {
            return redirect()->back()->with('error', __('No workers selected.'));
        }

        // Verify all workers belong to current user
        $workers = Worker::whereIn('id', $workerIds)
            ->where('created_by', Auth::user()->creatorId())
            ->pluck('id')
            ->toArray();

        if (empty($workers)) {
            return redirect()->back()->with('error', __('No valid workers selected.'));
        }

        $pdfService = new WorkerPdfExportService();
        $pdf = $pdfService->generate($workers);

        $filename = 'workers_export_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export selected workers to Excel
     * POST /worker/export/excel
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::user()->can('manage worker')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workerIds = $request->input('worker_ids', []);
        
        if (empty($workerIds)) {
            return redirect()->back()->with('error', __('No workers selected.'));
        }

        // Verify all workers belong to current user
        $workers = Worker::whereIn('id', $workerIds)
            ->where('created_by', Auth::user()->creatorId())
            ->pluck('id')
            ->toArray();

        if (empty($workers)) {
            return redirect()->back()->with('error', __('No valid workers selected.'));
        }

        $filename = 'workers_export_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new WorkerExcelExport($workers), $filename);
    }
}
