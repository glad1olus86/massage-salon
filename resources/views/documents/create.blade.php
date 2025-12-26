@extends('layouts.admin')

@section('page-title')
    {{ __('Create Document Template') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('documents.index') }}">{{ __('Document Templates') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@push('css-page')
<style>
    .variables-panel {
        max-height: 600px;
        overflow-y: auto;
    }
    .variable-item {
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 4px;
        background: #f8f9fa;
        transition: background 0.2s;
    }
    .variable-item:hover {
        background: #e9ecef;
    }
    .variable-item code {
        font-size: 12px;
        color: #6f42c1;
    }
    .variable-item small {
        display: block;
        color: #6c757d;
        font-size: 11px;
    }
    .tox-tinymce {
        border-radius: 4px !important;
        border: 1px solid #ddd !important;
    }
    /* A4 Preview Modal Styles */
    .a4-preview-modal .modal-dialog {
        max-width: 900px;
    }
    .a4-preview-header {
        background: #f8f9fa;
        padding: 10px 20px;
        border-bottom: 1px solid #dee2e6;
        font-size: 13px;
        color: #6c757d;
    }
    .a4-preview-container {
        background: #525659;
        padding: 20px;
        max-height: 75vh;
        overflow-y: auto;
    }
    .a4-page {
        background: white;
        width: 210mm;
        padding: 15mm 20mm;
        margin: 0 auto 20px auto;
        box-shadow: 0 0 10px rgba(0,0,0,0.3);
        font-family: "Times New Roman", Times, serif;
        font-size: 12pt;
        line-height: 1.5;
        box-sizing: border-box;
    }
    .a4-page:last-child {
        margin-bottom: 0;
    }
    .a4-page table {
        border-collapse: collapse;
        width: 100%;
    }
    .a4-page td, .a4-page th {
        border: 1px solid #000;
        padding: 5px 8px;
    }
    .a4-page p {
        margin: 0 0 8px 0;
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Template Editor') }}</h5>
                </div>
                <div class="card-body">
                    {{ Form::open(['route' => 'documents.store', 'method' => 'POST']) }}
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                {{ Form::label('name', __('Template Name'), ['class' => 'form-label']) }}
                                {{ Form::text('name', null, ['class' => 'form-control', 'required' => true, 'placeholder' => __('For example: Employment Contract')]) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('is_active', __('Status'), ['class' => 'form-label']) }}
                                {{ Form::select('is_active', [1 => __('Active'), 0 => __('Inactive')], 1, ['class' => 'form-control']) }}
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        {{ Form::label('description', __('Description'), ['class' => 'form-label']) }}
                        {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2, 'placeholder' => __('Brief template description')]) }}
                    </div>

                    <div class="form-group">
                        {{ Form::label('content', __('Template Content'), ['class' => 'form-label']) }}
                        <textarea name="content" id="template-content" class="form-control"></textarea>
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ route('documents.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                    
                    {{ Form::close() }}
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Variables') }}</h5>
                    <small class="text-muted">{{ __('Click to insert') }}</small>
                </div>
                <div class="card-body variables-panel">
                    <h6 class="text-primary mb-2"><i class="ti ti-user me-1"></i>{{ __('Worker') }}</h6>
                    @foreach($variables as $var => $desc)
                        @if(str_starts_with($var, '{worker_'))
                            <div class="variable-item" data-variable="{{ $var }}">
                                <code>{{ $var }}</code>
                                <small>{{ $desc }}</small>
                            </div>
                        @endif
                    @endforeach

                    <h6 class="text-success mb-2 mt-3"><i class="ti ti-building me-1"></i>{{ __('Company') }}</h6>
                    @foreach($variables as $var => $desc)
                        @if(str_starts_with($var, '{company_') || str_starts_with($var, '{current_'))
                            <div class="variable-item" data-variable="{{ $var }}">
                                <code>{{ $var }}</code>
                                <small>{{ $desc }}</small>
                            </div>
                        @endif
                    @endforeach

                    <h6 class="text-warning mb-2 mt-3"><i class="ti ti-home me-1"></i>{{ __('Assignment') }}</h6>
                    @foreach($variables as $var => $desc)
                        @if(str_starts_with($var, '{hotel_') || str_starts_with($var, '{room_') || str_starts_with($var, '{work_') || str_starts_with($var, '{check_') || str_starts_with($var, '{employment_'))
                            <div class="variable-item" data-variable="{{ $var }}">
                                <code>{{ $var }}</code>
                                <small>{{ $desc }}</small>
                            </div>
                        @endif
                    @endforeach
                    
                    <h6 class="text-info mb-2 mt-3"><i class="ti ti-calendar me-1"></i>{{ __('Dynamic') }}</h6>
                    <div class="variable-item" data-variable='{choose_date}:"Field Name"'>
                        <code>{choose_date}:"..."</code>
                        <small>{{ __('Date selection (user input)') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- A4 Preview Modal -->
    <div class="modal fade a4-preview-modal" id="a4PreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="a4-preview-header">
                        <i class="ti ti-info-circle me-1"></i>
                        {{ __('This is an approximate preview. Actual page breaks may differ in PDF.') }}
                    </div>
                    <div class="a4-preview-container" id="a4PreviewContent">
                        <!-- Pages will be inserted here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script src="https://cdn.tiny.cloud/1/5s735b5p5tv2ndcsws01ygx3cicnu3u9tnkdsl3bqnve1knq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize TinyMCE - simple document editor
    tinymce.init({
        selector: '#template-content',
        height: 600,
        width: '100%',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'pagebreak'
        ],
        toolbar: 'undo redo | fontfamily fontsize | bold italic forecolor backcolor | ' +
            'alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist outdent indent | table | pagebreak | removeformat | code | a4preview | fullscreen',
        font_family_formats: 'Times New Roman=times new roman,times,serif; Arial=arial,helvetica,sans-serif; Georgia=georgia,palatino,serif; Verdana=verdana,geneva,sans-serif; Courier New=courier new,courier,monospace; DejaVu Sans=dejavu sans,sans-serif',
        font_size_formats: '8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 28pt 36pt 48pt',
        content_style: `
            body {
                font-family: "Times New Roman", Times, serif;
                font-size: 12pt;
                line-height: 1.5;
                padding: 15px;
                margin: 0;
                background: #fff;
            }
            p { margin: 0 0 10px 0; }
            table {
                border-collapse: collapse;
                width: 100%;
            }
            td, th {
                border: 1px solid #000;
                padding: 6px 8px;
            }
        `,
        language: 'ru',
        menubar: 'file edit view insert format tools table help',
        menu: {
            file: { title: 'File', items: 'newdocument restoredraft | a4preview | print' }
        },
        branding: false,
        promotion: false,
        pagebreak_separator: '<div style="page-break-after: always;"></div>',
        pagebreak_split_block: true,
        setup: function(editor) {
            // Custom A4 Preview menu item
            editor.ui.registry.addMenuItem('a4preview', {
                text: '{{ __("Preview") }}',
                icon: 'preview',
                onAction: function() {
                    showA4Preview(editor.getContent());
                }
            });
            
            // Custom A4 Preview button
            editor.ui.registry.addButton('a4preview', {
                icon: 'preview',
                tooltip: '{{ __("A4 Preview") }}',
                onAction: function() {
                    showA4Preview(editor.getContent());
                }
            });
        }
    });

    // A4 Preview function
    function showA4Preview(content) {
        var previewContainer = document.getElementById('a4PreviewContent');
        
        // Clean content
        content = content.replace(/<p>\s*(&nbsp;)?\s*<\/p>/gi, '');
        content = content.replace(/<p><br\s*\/?><\/p>/gi, '');
        
        // Wrap content in A4 page
        var html = '<div class="a4-page">' + content + '</div>';
        
        // Handle page breaks
        html = html.replace(/<div[^>]*page-break[^>]*>\s*<\/div>/gi, '</div><div class="a4-page">');
        
        // Remove empty pages at the end
        html = html.replace(/<div class="a4-page">\s*<\/div>$/gi, '');
        
        previewContainer.innerHTML = html;
        
        var modal = new bootstrap.Modal(document.getElementById('a4PreviewModal'));
        modal.show();
    }

    // Insert variable on click
    document.querySelectorAll('.variable-item').forEach(function(item) {
        item.addEventListener('click', function() {
            var variable = this.dataset.variable;
            tinymce.activeEditor.execCommand('mceInsertContent', false, variable);
        });
    });
});
</script>
@endpush
