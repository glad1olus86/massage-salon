@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <button type="button" class="mobile-header-btn" onclick="openSidebar()">
                    <img src="{{ asset('fromfigma/menu_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><line x1=3 y1=6 x2=21 y2=6></line><line x1=3 y1=12 x2=21 y2=12></line><line x1=3 y1=18 x2=21 y2=18></line></svg>'">
                </button>
                <a href="{{ route('mobile.notifications.index') }}" class="mobile-header-btn">
                    <img src="{{ asset('fromfigma/bell_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\'></path><path d=\'M13.73 21a2 2 0 0 1-3.46 0\'></path></svg>'">
                </a>
            </div>
            <div class="mobile-header-right">
                <div class="mobile-logo">
                    <img src="{{ asset('fromfigma/jobsi_mobile.png') }}" alt="JOBSI">
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-content">
        {{-- Page Title --}}
        <div class="mobile-section-title">
            <div class="mobile-section-title-left">
                <img src="{{ asset('fromfigma/document.svg') }}" alt="" width="22" height="22"
                     onerror="this.outerHTML='<svg width=22 height=22 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z\'></path><polyline points=\'14,2 14,8 20,8\'></polyline></svg>'">
                <span>{{ __('Document Templates') }}</span>
            </div>
        </div>

        {{-- Search --}}
        <div class="mobile-search-box mb-3">
            <input type="text" id="searchTemplates" class="form-control" placeholder="{{ __('Search templates...') }}">
            <svg class="mobile-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
        </div>

        {{-- Templates List --}}
        <div class="mobile-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="ti ti-template me-2 text-primary"></i>{{ __('Templates') }}</h6>
                <span class="badge bg-light text-dark">{{ $templates->count() }}</span>
            </div>

            <div id="templatesList">
                @forelse($templates as $template)
                    <div class="mobile-template-item" data-name="{{ strtolower($template->name) }}" data-desc="{{ strtolower($template->description ?? '') }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1" onclick="showTemplateDetail({{ $template->id }})">
                                <div class="fw-medium text-dark mb-1">{{ $template->name }}</div>
                                @if($template->description)
                                    <small class="text-muted d-block mb-2">{{ Str::limit($template->description, 60) }}</small>
                                @endif
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="ti ti-variable me-1"></i>{{ $template->variables_count ?? 0 }} {{ __('variables') }}
                                    </span>
                                    @if($template->is_active)
                                        <span class="badge bg-success-subtle text-success">{{ __('Active') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-sm mobile-btn-primary" onclick="openGenerateModal({{ $template->id }}, '{{ addslashes($template->name) }}')">
                                    <i class="ti ti-file-download"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-file-off" style="font-size: 48px; opacity: 0.5;"></i>
                        <p class="mt-2 mb-0">{{ __('No document templates') }}</p>
                        <p class="small text-muted">{{ __('Create templates in desktop version') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Quick Generate Section --}}
        @if($templates->count() > 0 && $workers->count() > 0)
            <div class="mobile-card mb-3">
                <h6 class="mb-3"><i class="ti ti-bolt me-2 text-warning"></i>{{ __('Quick Generate') }}</h6>
                <p class="small text-muted mb-3">{{ __('Select template and worker to generate document') }}</p>
                
                <div class="form-group mb-3">
                    <label class="form-label small">{{ __('Template') }}</label>
                    <select id="quickTemplate" class="form-control">
                        <option value="">{{ __('Select template') }}</option>
                        @foreach($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label small">{{ __('Worker') }}</label>
                    <select id="quickWorker" class="form-control">
                        <option value="">{{ __('Select worker') }}</option>
                        @foreach($workers as $worker)
                            <option value="{{ $worker->id }}">{{ $worker->first_name }} {{ $worker->last_name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" class="btn mobile-btn-primary w-100" id="quickGenerateBtn" onclick="quickGenerate()">
                    <i class="ti ti-file-download me-2"></i>{{ __('Generate') }}
                </button>
            </div>
        @endif

        {{-- Info Card --}}
        <div class="mobile-card bg-light">
            <div class="d-flex align-items-start">
                <i class="ti ti-info-circle text-info me-2" style="font-size: 20px;"></i>
                <div>
                    <p class="small mb-1">{{ __('Documents are generated with worker data') }}</p>
                    <p class="small text-muted mb-0">{{ __('Available formats: PDF, DOCX, Excel') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Template Detail Modal --}}
    <div class="modal fade" id="templateDetailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Template Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="templateDetailContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn mobile-btn-primary" id="generateFromDetailBtn" onclick="generateFromDetail()">
                        <i class="ti ti-file-download me-1"></i>{{ __('Generate') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Generate Document Modal --}}
    <div class="modal fade" id="generateDocumentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form id="generateDocumentForm" method="POST" action="">
                    @csrf
                    <input type="hidden" name="template_id" id="gen_template_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="ti ti-file-download me-2 text-primary"></i>{{ __('Generate Document') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        {{-- Template Name --}}
                        <div class="alert alert-light mb-3">
                            <strong>{{ __('Template') }}:</strong> <span id="gen_template_name"></span>
                        </div>

                        {{-- Worker Selection --}}
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Worker') }} <span class="text-danger">*</span></label>
                            <select name="worker_id" id="gen_worker_id" class="form-control" required>
                                <option value="">{{ __('Select worker') }}</option>
                                @foreach($workers as $worker)
                                    <option value="{{ $worker->id }}">{{ $worker->first_name }} {{ $worker->last_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Dynamic Fields Container --}}
                        <div id="dynamicFieldsContainer"></div>

                        {{-- Format Selection --}}
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Document Format') }}</label>
                            <div class="row g-2">
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="format" id="format_pdf" value="pdf" checked>
                                    <label class="btn btn-outline-danger w-100 py-3" for="format_pdf">
                                        <i class="ti ti-file-type-pdf d-block mb-1" style="font-size: 24px;"></i>
                                        <span class="small">PDF</span>
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="format" id="format_docx" value="docx">
                                    <label class="btn btn-outline-primary w-100 py-3" for="format_docx">
                                        <i class="ti ti-file-type-doc d-block mb-1" style="font-size: 24px;"></i>
                                        <span class="small">DOCX</span>
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="format" id="format_xlsx" value="xlsx">
                                    <label class="btn btn-outline-success w-100 py-3" for="format_xlsx">
                                        <i class="ti ti-file-spreadsheet d-block mb-1" style="font-size: 24px;"></i>
                                        <span class="small">Excel</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn mobile-btn-primary" id="generateSubmitBtn">
                            <i class="ti ti-download me-1"></i>{{ __('Generate') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Preview Modal --}}
    <div class="modal fade" id="previewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" id="previewContent" style="background: #525659; overflow-y: auto;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-light" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mobile-btn-primary {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
            color: #fff !important;
        }
        .mobile-btn-primary:hover, .mobile-btn-primary:focus {
            background: #e00040 !important;
            border-color: #e00040 !important;
            color: #fff !important;
        }
        .mobile-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .mobile-template-item {
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
        }
        .mobile-template-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .mobile-template-item:first-child {
            padding-top: 0;
        }
        .mobile-template-item:active {
            background: #f8f9fa;
        }
        .mobile-search-box {
            position: relative;
        }
        .mobile-search-box input {
            padding-left: 40px;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        .mobile-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
        }
        .text-primary {
            color: #FF0049 !important;
        }
        .bg-info-subtle {
            background-color: rgba(13, 202, 240, 0.1) !important;
        }
        .bg-success-subtle {
            background-color: rgba(25, 135, 84, 0.1) !important;
        }
        .modal-fullscreen-sm-down {
            margin: 0;
        }
        @media (max-width: 576px) {
            .modal-fullscreen-sm-down .modal-content {
                height: 100%;
                border: 0;
                border-radius: 0;
            }
            .modal-fullscreen-sm-down .modal-dialog {
                width: 100%;
                max-width: none;
                height: 100%;
                margin: 0;
            }
        }
        .a4-preview {
            background: white;
            width: 100%;
            max-width: 210mm;
            padding: 10mm;
            margin: 15px auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.3);
            font-family: "Times New Roman", Times, serif;
            font-size: 11pt;
            line-height: 1.4;
        }
    </style>
@endsection

@push('scripts')
<script>
var currentTemplateId = null;
var templatesData = @json($templates->keyBy('id'));
var workersData = @json($workers->keyBy('id'));

document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    var searchInput = document.getElementById('searchTemplates');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase();
            document.querySelectorAll('.mobile-template-item').forEach(function(item) {
                var name = item.dataset.name || '';
                var desc = item.dataset.desc || '';
                if (name.includes(query) || desc.includes(query)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }

    // Form submission
    var generateForm = document.getElementById('generateDocumentForm');
    if (generateForm) {
        generateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitGenerateForm();
        });
    }
});

function showTemplateDetail(templateId) {
    currentTemplateId = templateId;
    var modal = new bootstrap.Modal(document.getElementById('templateDetailModal'));
    var content = document.getElementById('templateDetailContent');
    
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    modal.show();

    var template = templatesData[templateId];
    if (template) {
        var html = '<div class="mobile-info-list">';
        html += '<div class="mobile-info-item d-flex justify-content-between py-2 border-bottom"><span class="text-muted">{{ __("Name") }}</span><span class="fw-medium">' + template.name + '</span></div>';
        if (template.description) {
            html += '<div class="mobile-info-item py-2 border-bottom"><span class="text-muted d-block mb-1">{{ __("Description") }}</span><span>' + template.description + '</span></div>';
        }
        html += '<div class="mobile-info-item d-flex justify-content-between py-2 border-bottom"><span class="text-muted">{{ __("Variables") }}</span><span class="badge bg-info">' + (template.variables_count || 0) + '</span></div>';
        html += '<div class="mobile-info-item d-flex justify-content-between py-2"><span class="text-muted">{{ __("Status") }}</span><span class="badge ' + (template.is_active ? 'bg-success' : 'bg-secondary') + '">' + (template.is_active ? '{{ __("Active") }}' : '{{ __("Inactive") }}') + '</span></div>';
        html += '</div>';
        
        // Show variables if available
        if (template.variables && template.variables.length > 0) {
            html += '<div class="mt-3"><h6 class="small text-muted mb-2">{{ __("Used Variables") }}:</h6><div class="d-flex flex-wrap gap-1">';
            template.variables.forEach(function(v) {
                html += '<span class="badge bg-light text-dark border">' + v + '</span>';
            });
            html += '</div></div>';
        }
        
        content.innerHTML = html;
    }
}

function generateFromDetail() {
    if (currentTemplateId) {
        var template = templatesData[currentTemplateId];
        bootstrap.Modal.getInstance(document.getElementById('templateDetailModal')).hide();
        openGenerateModal(currentTemplateId, template ? template.name : '');
    }
}

function openGenerateModal(templateId, templateName) {
    currentTemplateId = templateId;
    document.getElementById('gen_template_id').value = templateId;
    document.getElementById('gen_template_name').textContent = templateName;
    document.getElementById('gen_worker_id').value = '';
    document.getElementById('dynamicFieldsContainer').innerHTML = '';
    
    // Load dynamic fields for template
    loadDynamicFields(templateId);
    
    var modal = new bootstrap.Modal(document.getElementById('generateDocumentModal'));
    modal.show();
}

function loadDynamicFields(templateId) {
    var container = document.getElementById('dynamicFieldsContainer');
    
    fetch('{{ url("/documents/template-fields") }}/' + templateId)
        .then(response => response.json())
        .then(data => {
            if (data.fields && data.fields.length > 0) {
                var html = '<div class="alert alert-info small mb-3"><i class="ti ti-info-circle me-1"></i>{{ __("This template requires additional fields") }}</div>';
                data.fields.forEach(function(field) {
                    html += '<div class="form-group mb-3">';
                    html += '<label class="form-label">' + field.label + ' <span class="text-danger">*</span></label>';
                    html += '<input type="date" name="' + field.field_name + '" class="form-control" required>';
                    html += '</div>';
                });
                container.innerHTML = html;
            }
        })
        .catch(function(error) {
            console.log('No dynamic fields or error:', error);
        });
}

function submitGenerateForm() {
    var form = document.getElementById('generateDocumentForm');
    var submitBtn = document.getElementById('generateSubmitBtn');
    var workerId = document.getElementById('gen_worker_id').value;
    var templateId = document.getElementById('gen_template_id').value;
    var format = document.querySelector('input[name="format"]:checked').value;
    
    if (!workerId) {
        show_toastr('error', '{{ __("Please select a worker") }}');
        return;
    }
    
    // Get worker and template data for filename
    var worker = workersData[workerId];
    var template = templatesData[templateId];
    var today = new Date();
    var dateStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    
    // Build filename: FirstName_LastName_TemplateName_Date.format
    var workerName = (worker.first_name + '_' + worker.last_name).replace(/\s+/g, '_');
    var templateName = template.name.replace(/\s+/g, '_');
    var fileName = workerName + '_' + templateName + '_' + dateStr + '.' + format;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __("Generating...") }}';
    
    var formData = new FormData(form);
    
    // Use worker generate document route
    fetch('{{ url("/worker") }}/' + workerId + '/generate-document', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        return response.json().then(data => { throw new Error(data.error || '{{ __("Generation error") }}'); });
    })
    .then(blob => {
        // Download file with proper name
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
        
        show_toastr('success', '{{ __("Document generated successfully") }}');
        bootstrap.Modal.getInstance(document.getElementById('generateDocumentModal')).hide();
    })
    .catch(error => {
        show_toastr('error', error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="ti ti-download me-1"></i>{{ __("Generate") }}';
    });
}

function quickGenerate() {
    var templateId = document.getElementById('quickTemplate').value;
    var workerId = document.getElementById('quickWorker').value;
    var btn = document.getElementById('quickGenerateBtn');
    
    if (!templateId) {
        show_toastr('error', '{{ __("Please select a template") }}');
        return;
    }
    if (!workerId) {
        show_toastr('error', '{{ __("Please select a worker") }}');
        return;
    }
    
    // Get worker and template data for filename
    var worker = workersData[workerId];
    var template = templatesData[templateId];
    var today = new Date();
    var dateStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    
    // Build filename: FirstName_LastName_TemplateName_Date.pdf
    var workerName = (worker.first_name + '_' + worker.last_name).replace(/\s+/g, '_');
    var templateName = template.name.replace(/\s+/g, '_');
    var fileName = workerName + '_' + templateName + '_' + dateStr + '.pdf';
    
    // Disable button and show loading
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>{{ __("Generating...") }}';
    
    // Create form data
    var formData = new FormData();
    formData.append('template_id', templateId);
    formData.append('format', 'pdf');
    
    // Direct download without modal
    fetch('{{ url("/worker") }}/' + workerId + '/generate-document', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: formData
    })
    .then(response => {
        if (response.ok) {
            return response.blob();
        }
        return response.json().then(data => { throw new Error(data.error || '{{ __("Generation error") }}'); });
    })
    .then(blob => {
        // Download file with proper name
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
        
        show_toastr('success', '{{ __("Document generated successfully") }}');
    })
    .catch(error => {
        show_toastr('error', error.message);
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-file-download me-2"></i>{{ __("Generate") }}';
    });
}
</script>
@endpush
