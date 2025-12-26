@extends('layouts.admin')

@section('page-title')
    {{ __('Document Templates') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Document Templates') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('document_template_create')
            <a href="{{ route('documents.create') }}"
                class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Create Template') }}">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@push('css-page')
<style>
    .search-filter-wrapper {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-bottom: 20px;
    }
    .search-box {
        flex: 1;
        max-width: 400px;
        position: relative;
    }
    .search-box .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 18px;
        pointer-events: none;
    }
    .search-box input {
        width: 100%;
        padding: 12px 40px 12px 42px;
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.2s;
    }
    .search-box input:focus {
        outline: none;
        border-color: #FF0049;
        box-shadow: 0 0 0 3px rgba(255, 0, 73, 0.1);
    }
    .search-box input.searching {
        border-color: #FF0049;
        background: #FFF8FA;
    }
    .search-box .search-clear {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        cursor: pointer;
        display: none;
    }
    .search-box .search-clear:hover {
        color: #FF0049;
    }
    .results-count {
        font-size: 13px;
        color: #888;
        padding: 8px 0;
        margin-bottom: 20px;
    }
    .results-count strong {
        color: #FF0049;
    }
    tr.search-hidden {
        display: none !important;
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    {{-- Search --}}
                    <div class="search-filter-wrapper">
                        <div class="search-box">
                            <i class="ti ti-search search-icon"></i>
                            <input type="text" id="liveSearchInput" placeholder="{{ __('Search by name, description...') }}" autocomplete="off">
                            <span class="search-clear" id="clearSearch"><i class="ti ti-x"></i></span>
                        </div>
                    </div>
                    
                    {{-- Results Count --}}
                    <div class="results-count">
                        {{ __('Found') }}: <strong id="resultsCount">{{ count($templates) }}</strong> {{ __('templates') }}
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table" id="templates-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Variables') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Created') }}</th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($templates as $template)
                                    <tr data-name="{{ strtolower($template->name) }}" data-description="{{ strtolower($template->description ?? '') }}">
                                        <td>
                                            @can('document_template_edit')
                                                <a href="{{ route('documents.edit', $template->id) }}" class="text-primary fw-medium">
                                                    {{ $template->name }}
                                                </a>
                                            @else
                                                <span class="fw-medium">{{ $template->name }}</span>
                                            @endcan
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ Str::limit($template->description, 50) }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $template->variables_count }}</span>
                                        </td>
                                        <td>
                                            @if($template->is_active)
                                                <span class="badge bg-success">{{ __('Active') }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('Inactive') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $template->formatted_created_at }}</td>
                                        <td class="Action">
                                            <span>
                                                @can('document_template_edit')
                                                    <div class="action-btn me-2">
                                                        <a href="{{ route('documents.edit', $template->id) }}"
                                                            class="mx-3 btn btn-sm align-items-center bg-info"
                                                            data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                            <i class="ti ti-pencil text-white"></i>
                                                        </a>
                                                    </div>
                                                @endcan
                                                @can('document_template_delete')
                                                    <div class="action-btn">
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['documents.destroy', $template->id],
                                                            'id' => 'delete-form-' . $template->id,
                                                        ]) !!}
                                                        <a href="#"
                                                            class="mx-3 btn btn-sm align-items-center bs-pass-para bg-danger"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Are you sure?') . '|' . __('This action cannot be undone. Do you want to continue?') }}"
                                                            data-confirm-yes="document.getElementById('delete-form-{{ $template->id }}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endcan
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="ti ti-file-text" style="font-size: 48px;"></i>
                                            <p class="mt-2">{{ __('No document templates found') }}</p>
                                            @can('document_template_create')
                                                <a href="{{ route('documents.create') }}" class="btn btn-primary btn-sm">
                                                    <i class="ti ti-plus me-1"></i>{{ __('Create First Template') }}
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('liveSearchInput');
    var clearBtn = document.getElementById('clearSearch');
    var rows = document.querySelectorAll('#templates-table tbody tr[data-name]');

    searchInput.addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var visibleCount = 0;

        clearBtn.style.display = query.length > 0 ? 'block' : 'none';
        this.classList.toggle('searching', query.length > 0);

        rows.forEach(function(row) {
            var name = row.dataset.name || '';
            var description = row.dataset.description || '';

            if (query.length < 2 || name.includes(query) || description.includes(query)) {
                row.classList.remove('search-hidden');
                visibleCount++;
            } else {
                row.classList.add('search-hidden');
            }
        });

        document.getElementById('resultsCount').textContent = visibleCount;
    });

    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.dispatchEvent(new Event('input'));
    });
});
</script>
@endpush
