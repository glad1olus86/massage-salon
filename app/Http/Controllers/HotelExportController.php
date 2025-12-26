<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Exports\HotelExcelExport;
use App\Services\HotelPdfExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class HotelExportController extends Controller
{
    /**
     * Show export modal with hotels list
     * GET /hotel/export
     */
    public function showExportModal()
    {
        if (!Auth::user()->can('manage hotel')) {
            return response()->json(['error' => __('Permission denied.')], 403);
        }

        try {
            $hotels = Hotel::where('created_by', Auth::user()->creatorId())
                ->withCount('rooms')
                ->orderBy('name')
                ->get();

            return view('hotel.export_modal', compact('hotels'));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export selected hotels to PDF
     * POST /hotel/export/pdf
     */
    public function exportPdf(Request $request)
    {
        if (!Auth::user()->can('manage hotel')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $hotelIds = $request->input('hotel_ids', []);
        
        if (empty($hotelIds)) {
            return redirect()->back()->with('error', __('No hotels selected.'));
        }

        $hotels = Hotel::whereIn('id', $hotelIds)
            ->where('created_by', Auth::user()->creatorId())
            ->pluck('id')
            ->toArray();

        if (empty($hotels)) {
            return redirect()->back()->with('error', __('No valid hotels selected.'));
        }

        $pdfService = new HotelPdfExportService();
        $pdf = $pdfService->generate($hotels);

        $filename = 'hotels_export_' . date('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export selected hotels to Excel
     * POST /hotel/export/excel
     */
    public function exportExcel(Request $request)
    {
        if (!Auth::user()->can('manage hotel')) {
            return redirect()->back()->with('error', __('Permission denied.'));
        }

        $hotelIds = $request->input('hotel_ids', []);
        
        if (empty($hotelIds)) {
            return redirect()->back()->with('error', __('No hotels selected.'));
        }

        $hotels = Hotel::whereIn('id', $hotelIds)
            ->where('created_by', Auth::user()->creatorId())
            ->pluck('id')
            ->toArray();

        if (empty($hotels)) {
            return redirect()->back()->with('error', __('No valid hotels selected.'));
        }

        $filename = 'hotels_export_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new HotelExcelExport($hotels), $filename);
    }
}
