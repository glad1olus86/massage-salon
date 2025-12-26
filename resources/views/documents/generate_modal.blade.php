{{-- Document Generation Modal --}}
<div class="modal fade" id="generate-document-modal" tabindex="-1" role="dialog"
    aria-labelledby="generate-document-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="generate-document-modal-label">
                    <i class="ti ti-file-text me-2"></i>{{ __('Generate Document') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('worker.generate-document', $worker->id) }}" method="POST" id="generate-document-form">
                @csrf
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="template_id" class="form-label">{{ __('Document Template') }}</label>
                        <select name="template_id" id="template_id" class="form-control" required>
                            <option value="">{{ __('Select template') }}</option>
                            @php
                                $templates = \App\Models\DocumentTemplate::forCurrentUser()->active()->orderBy('name')->get();
                            @endphp
                            @forelse($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }}</option>
                            @empty
                                <option value="" disabled>{{ __('No templates available') }}</option>
                            @endforelse
                        </select>
                        @if($templates->isEmpty())
                            <small class="text-muted">
                                <a href="{{ route('documents.create') }}">{{ __('Create First Template') }}</a>
                            </small>
                        @endif
                    </div>

                    <!-- Dynamic date fields container -->
                    <div id="dynamic-fields-container" class="mb-3" style="display: none;">
                        <!-- Dynamic fields will be inserted here by JavaScript -->
                    </div>

                    <div class="form-group">
                        <label class="form-label">{{ __('Document Format') }}</label>
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="format" id="format-pdf" value="pdf" checked>
                                <label class="btn btn-outline-danger w-100" for="format-pdf">
                                    <i class="ti ti-file-type-pdf d-block" style="font-size: 24px;"></i>
                                    PDF
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="format" id="format-docx" value="docx">
                                <label class="btn btn-outline-primary w-100" for="format-docx">
                                    <i class="ti ti-file-type-doc d-block" style="font-size: 24px;"></i>
                                    DOCX
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check" name="format" id="format-xlsx" value="xlsx">
                                <label class="btn btn-outline-success w-100" for="format-xlsx">
                                    <i class="ti ti-file-spreadsheet d-block" style="font-size: 24px;"></i>
                                    Excel
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        <i class="ti ti-info-circle me-1"></i>
                        {{ __('Document will be generated with worker data') }}: 
                        <strong>{{ $worker->first_name }} {{ $worker->last_name }}</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="generate-btn" {{ $templates->isEmpty() ? 'disabled' : '' }}>
                        <i class="ti ti-download me-1"></i>{{ __('Generate') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var templateSelect = document.getElementById('template_id');
    var dynamicFieldsContainer = document.getElementById('dynamic-fields-container');
    
    if (templateSelect) {
        templateSelect.addEventListener('change', function() {
            var templateId = this.value;
            
            // Clear previous dynamic fields
            dynamicFieldsContainer.innerHTML = '';
            dynamicFieldsContainer.style.display = 'none';
            
            if (!templateId) return;
            
            // Fetch dynamic fields for selected template
            fetch('{{ url("/documents/template-fields") }}/' + templateId)
                .then(response => response.json())
                .then(data => {
                    if (data.fields && data.fields.length > 0) {
                        dynamicFieldsContainer.style.display = 'block';
                        
                        data.fields.forEach(function(field) {
                            var fieldHtml = `
                                <div class="form-group mb-3">
                                    <label for="${field.field_name}" class="form-label">${field.label}</label>
                                    <input type="date" 
                                           name="${field.field_name}" 
                                           id="${field.field_name}" 
                                           class="form-control" 
                                           required>
                                </div>
                            `;
                            dynamicFieldsContainer.innerHTML += fieldHtml;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error fetching template fields:', error);
                });
        });
    }
});
</script>
