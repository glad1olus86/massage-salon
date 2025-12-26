<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\Worker;
use App\Services\DocumentGeneratorService;
use App\Services\DocumentTemplateService;
use App\Services\DocumentAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentGeneratorController extends Controller
{
    protected DocumentGeneratorService $generatorService;
    protected DocumentTemplateService $templateService;
    protected DocumentAuditService $auditService;

    public function __construct(
        DocumentGeneratorService $generatorService,
        DocumentTemplateService $templateService,
        DocumentAuditService $auditService
    ) {
        $this->generatorService = $generatorService;
        $this->templateService = $templateService;
        $this->auditService = $auditService;
    }

    /**
     * Generate document for worker
     */
    public function generate(Request $request, Worker $worker)
    {
        if (!Auth::user()->can('document_generate')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check for worker
        if ($worker->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Worker not found'));
        }

        $request->validate([
            'template_id' => 'required|exists:document_templates,id',
            'format' => 'required|in:pdf,docx,xlsx',
        ]);

        $template = DocumentTemplate::find($request->template_id);

        // Multi-tenancy check for template
        if ($template->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Template not found'));
        }

        $format = $request->format;
        
        // Collect dynamic date fields from request
        $dynamicData = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'choose_date_')) {
                $dynamicData[$key] = $value;
            }
        }

        // Log generation
        $this->auditService->logDocumentGenerated($template, $worker, $format);

        // Generate document
        try {
            switch ($format) {
                case 'pdf':
                    return $this->generatorService->generatePdf($template, $worker, $dynamicData);
                case 'docx':
                    return $this->generatorService->generateDocx($template, $worker, $dynamicData);
                case 'xlsx':
                    return $this->generatorService->generateExcel($template, $worker, $dynamicData);
                default:
                    return redirect()->back()->with('error', __('Unsupported format'));
            }
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Document generation error: ') . $e->getMessage());
        }
    }

    /**
     * Get available templates for worker (AJAX)
     */
    public function getTemplates()
    {
        if (!Auth::user()->can('document_generate')) {
            return response()->json(['error' => __('Insufficient permissions')], 403);
        }

        $templates = $this->templateService->getActive();

        return response()->json($templates);
    }
    
    /**
     * Get dynamic fields for a template (AJAX)
     */
    public function getTemplateFields(DocumentTemplate $template)
    {
        if (!Auth::user()->can('document_generate')) {
            return response()->json(['error' => __('Insufficient permissions')], 403);
        }
        
        // Multi-tenancy check
        if ($template->created_by !== Auth::user()->creatorId()) {
            return response()->json(['error' => __('Template not found')], 404);
        }
        
        $dynamicFields = $this->generatorService->extractDynamicDateFields($template->content);
        
        return response()->json([
            'fields' => $dynamicFields
        ]);
    }
    
    /**
     * Bulk generate documents for multiple workers
     */
    public function bulkGenerate(Request $request)
    {
        // Increase limits for bulk operations
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        
        if (!Auth::user()->can('document_generate')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        $request->validate([
            'template_id' => 'required|exists:document_templates,id',
            'format' => 'required|in:pdf,docx,xlsx',
        ]);

        $template = DocumentTemplate::find($request->template_id);

        // Multi-tenancy check for template
        if ($template->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Template not found'));
        }

        // Get worker IDs - either from bulk selection or single selection
        $workerIds = [];
        if ($request->filled('worker_ids')) {
            $workerIds = array_filter(explode(',', $request->worker_ids));
        } elseif ($request->filled('single_worker_id')) {
            $workerIds = [$request->single_worker_id];
        }

        if (empty($workerIds)) {
            return redirect()->back()->with('error', __('Select at least one worker'));
        }

        // Get workers with multi-tenancy check
        $workers = Worker::whereIn('id', $workerIds)
            ->where('created_by', Auth::user()->creatorId())
            ->get();

        if ($workers->isEmpty()) {
            return redirect()->back()->with('error', __('Workers not found'));
        }

        $format = $request->format;
        
        // Collect dynamic date fields from request
        $dynamicData = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'choose_date_')) {
                $dynamicData[$key] = $value;
            }
        }

        // If single worker, generate single document
        if ($workers->count() === 1) {
            $worker = $workers->first();
            $this->auditService->logDocumentGenerated($template, $worker, $format);
            
            try {
                switch ($format) {
                    case 'pdf':
                        return $this->generatorService->generatePdf($template, $worker, $dynamicData);
                    case 'docx':
                        return $this->generatorService->generateDocx($template, $worker, $dynamicData);
                    case 'xlsx':
                        return $this->generatorService->generateExcel($template, $worker, $dynamicData);
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', __('Document generation error: ') . $e->getMessage());
            }
        }

        // Multiple workers - generate ZIP archive
        try {
            \Log::info('Bulk document generation started', [
                'workers_count' => $workers->count(),
                'format' => $format,
                'template_id' => $template->id,
                'memory_limit' => ini_get('memory_limit'),
            ]);
            
            return $this->generatorService->generateBulkZip($template, $workers, $format, $dynamicData);
        } catch (\Exception $e) {
            \Log::error('Bulk document generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'workers_count' => $workers->count(),
            ]);
            return redirect()->back()->with('error', __('Documents generation error: ') . $e->getMessage());
        }
    }
}
