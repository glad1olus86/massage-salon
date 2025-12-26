<?php

namespace App\Http\Controllers;

use App\Models\WorkPlace;
use App\Exports\WorkPlaceExcelExport;
use App\Services\WorkPlacePdfExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class WorkPlaceExportController extends Controller
{
    /**
     * Show export modal with work places list
     * GET /work-place/export
     */
    public function showExportModal()
    {
        if (!Auth::user()->can('manage work place')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $workPlaces = WorkPlace::where('created_by', Auth::user()->creatorId())
                ->withCount(['currentAssignments as workers_count'])
                ->orderBy('name')
                ->get();

            return view('work_place.export_modal', compact('workPlaces'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export selected work places to PDF
     * POST /work-place/export/pdf
     */
    public function exportPdf(Request $request)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workPlaceIds = $request->input('work_place_ids', []);
        
        if (empty($workPlaceIds)) {
            return redirect()->back()->with('error', __('No work places selected.'));
        }

        $workPlaces = WorkPlace::whereIn('id', $workPlaceIds)
            ->where('created_by', Auth::user()->creatorId())
            ->pluck('id')
            ->toArray();

        if (empty($workPlaces)) {
            return redirect()->back()->with('error', __('No valid work places selected.'));
        }

        $pdfService = new WorkPlacePdfExportService();
        $pdf = $pdfService->generate($workPlaces);

        $filename = 'work_places_export_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export selected work places to Excel
     * POST /work-place/export/excel
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::user()->can('manage work place')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $workPlaceIds = $request->input('work_place_ids', []);
        
        if (empty($workPlaceIds)) {
            return redirect()->back()->with('error', __('No work places selected.'));
        }

        $workPlaces = WorkPlace::whereIn('id', $workPlaceIds)
            ->where('created_by', Auth::user()->creatorId())
            ->pluck('id')
            ->toArray();

        if (empty($workPlaces)) {
            return redirect()->back()->with('error', __('No valid work places selected.'));
        }

        $filename = 'work_places_export_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new WorkPlaceExcelExport($workPlaces), $filename);
    }
}
