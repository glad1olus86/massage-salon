<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Services\DocumentTemplateService;
use App\Services\DocumentGeneratorService;
use App\Services\DocumentAuditService;
use App\Services\PlanLimitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentTemplateController extends Controller
{
    protected DocumentTemplateService $templateService;
    protected DocumentGeneratorService $generatorService;
    protected DocumentAuditService $auditService;

    public function __construct(
        DocumentTemplateService $templateService,
        DocumentGeneratorService $generatorService,
        DocumentAuditService $auditService
    ) {
        $this->templateService = $templateService;
        $this->generatorService = $generatorService;
        $this->auditService = $auditService;
    }

    /**
     * Display a listing of templates
     */
    public function index()
    {
        if (!Auth::user()->can('document_template_read')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        $templates = $this->templateService->getAll();

        return view('documents.index', compact('templates'));
    }

    /**
     * Show the form for creating a new template
     */
    public function create()
    {
        if (!Auth::user()->can('document_template_create')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }
        
        // Check plan limit
        if (!PlanLimitService::canCreateDocumentTemplate()) {
            $limitInfo = PlanLimitService::getLimitInfo();
            $limit = $limitInfo['document_templates']['limit'];
            return redirect()->back()->with('error', __('You have reached the maximum number of document templates (:limit) allowed by your plan. Please upgrade your plan.', ['limit' => $limit]));
        }

        $variables = $this->generatorService->getAvailableVariables();

        return view('documents.create', compact('variables'));
    }

    /**
     * Store a newly created template
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('document_template_create')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }
        
        // Check plan limit
        if (!PlanLimitService::canCreateDocumentTemplate()) {
            $limitInfo = PlanLimitService::getLimitInfo();
            $limit = $limitInfo['document_templates']['limit'];
            return redirect()->back()->with('error', __('You have reached the maximum number of document templates (:limit) allowed by your plan. Please upgrade your plan.', ['limit' => $limit]));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $template = $this->templateService->create($request->all());
        $this->auditService->logTemplateCreated($template);

        return redirect()->route('documents.index')
            ->with('success', __('Template successfully created'));
    }

    /**
     * Show the form for editing the template
     */
    public function edit(DocumentTemplate $template)
    {
        if (!Auth::user()->can('document_template_edit')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($template->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Template not found'));
        }

        $variables = $this->generatorService->getAvailableVariables();

        return view('documents.edit', compact('template', 'variables'));
    }

    /**
     * Update the template
     */
    public function update(Request $request, DocumentTemplate $template)
    {
        if (!Auth::user()->can('document_template_edit')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($template->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Template not found'));
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $oldValues = $template->toArray();
        $template = $this->templateService->update($template, $request->all());
        $this->auditService->logTemplateUpdated($template, $oldValues);

        return redirect()->route('documents.index')
            ->with('success', __('Template successfully updated'));
    }

    /**
     * Remove the template
     */
    public function destroy(DocumentTemplate $template)
    {
        if (!Auth::user()->can('document_template_delete')) {
            return redirect()->back()->with('error', __('Insufficient permissions'));
        }

        // Multi-tenancy check
        if ($template->created_by !== Auth::user()->creatorId()) {
            return redirect()->back()->with('error', __('Template not found'));
        }

        $this->auditService->logTemplateDeleted($template);
        $this->templateService->delete($template);

        return redirect()->route('documents.index')
            ->with('success', __('Template successfully deleted'));
    }
}
