@extends('layouts.admin')

@section('page-title')
    {{ __('Worker Management') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Workers') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('document_generate')
            <button type="button" id="bulk-generate-doc-btn"
                data-bs-toggle="tooltip" title="{{ __('Document Generation') }}"
                class="btn btn-sm btn-info me-1">
                <i class="ti ti-file-text"></i>
            </button>
        @endcan
        @can('manage worker')
            <a href="#" data-url="{{ route('worker.export.modal') }}" data-ajax-popup="true"
                data-title="{{ __('Export Workers') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
                data-size="lg"
                class="btn btn-sm btn-secondary me-1">
                <i class="ti ti-file-export"></i>
            </a>
        @endcan
        @can('create worker')
            <a href="#" data-url="{{ route('worker.create') }}" data-ajax-popup="true"
                data-title="{{ __('Add New Worker') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i>
            </a>
        @endcan
    </div>
@endsection

@push('css-page')
<style>
    .search-filter-wrapper { display: flex; gap: 12px; align-items: stretch; margin-bottom: 20px; }
    .search-box { flex: 1; max-width: 400px; position: relative; }
    .search-box .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #999; font-size: 18px; pointer-events: none; }
    .search-box input { width: 100%; padding: 12px 40px 12px 42px; border: 1px solid #e0e0e0; border-radius: 10px; font-size: 14px; transition: all 0.2s; }
    .search-box input:focus { outline: none; border-color: #FF0049; box-shadow: 0 0 0 3px rgba(255, 0, 73, 0.1); }
    .search-box input.searching { border-color: #FF0049; background: #FFF8FA; }
    .search-box .search-clear { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #999; cursor: pointer; display: none; }
    .search-box .search-clear:hover { color: #FF0049; }
    .search-box .search-spinner { position: absolute; right: 12px; top: 50%; transform: translateY(-50%); display: none; }
    
    .filter-toggle-btn { padding: 12px 20px; border: 1px solid #e0e0e0; border-radius: 10px; background: #fff; color: #666; font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s; position: relative; }
    .filter-toggle-btn:hover, .filter-toggle-btn.has-filters { border-color: #FF0049; color: #FF0049; background: #FFF5F7; }
    .filter-toggle-btn .filter-count { position: absolute; top: -8px; right: -8px; width: 20px; height: 20px; background: #FF0049; color: #fff; border-radius: 50%; font-size: 11px; font-weight: 600; display: flex; align-items: center; justify-content: center; }
    
    .per-page-wrapper { margin-left: auto; }
    .per-page-select { padding: 12px 16px; border: 1px solid #e0e0e0; border-radius: 10px; background: #fff; color: #666; font-size: 14px; font-weight: 500; cursor: pointer; appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23666' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 36px; }
    .per-page-select:focus { outline: none; border-color: #FF0049; box-shadow: 0 0 0 3px rgba(255, 0, 73, 0.1); }
    
    .filter-panel { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 20px; margin-bottom: 20px; display: none; }
    .filter-panel.show { display: block; }
    .filter-panel-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f0; }
    .filter-panel-title { font-weight: 600; font-size: 15px; color: #333; display: flex; align-items: center; gap: 8px; }
    .filter-panel-title i { color: #FF0049; }
    .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .filter-group label { display: block; font-size: 12px; font-weight: 600; color: #666; margin-bottom: 6px; text-transform: uppercase; }
    .filter-group select, .filter-group input { width: 100%; padding: 10px 12px; border: 1px solid #e0e0e0; border-radius: 8px; font-size: 14px; }
    .filter-group select:focus, .filter-group input:focus { outline: none; border-color: #FF0049; }
    .gender-toggles { display: flex; gap: 10px; }
    .gender-toggle { flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
    .gender-toggle input { display: none; }
    .gender-toggle:hover { border-color: #FF0049; background: #FFF8FA; }
    .gender-toggle.active { border-color: #FF0049; background: #FFF0F4; color: #FF0049; }
    .filter-actions { display: flex; gap: 10px; margin-top: 16px; padding-top: 16px; border-top: 1px solid #f0f0f0; }
    .filter-btn-reset { padding: 10px 20px; border: 1px solid #ddd; border-radius: 8px; background: #fff; color: #666; font-size: 14px; cursor: pointer; }
    .filter-btn-reset:hover { background: #f5f5f5; }
    .filter-btn-apply { padding: 10px 24px; border: none; border-radius: 8px; background: linear-gradient(135deg, #FF0049 0%, #FF3366 100%); color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; }
    .filter-btn-apply:hover { transform: translateY(-1px); }
    
    .active-filters { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
    .active-filter-chip { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #FFF0F4; border: 1px solid #FFD6E0; border-radius: 20px; font-size: 12px; color: #FF0049; cursor: pointer; }
    .active-filter-chip:hover { background: #FFE0E8; }
    
    .results-info { display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #888; padding: 8px 0; margin-bottom: 30px; }
    .results-info strong { color: #FF0049; }
    
    .highlight { background: #FFE0E8; color: #FF0049; padding: 0 2px; border-radius: 2px; }
    
    .pagination-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; }
    .pagination-info { font-size: 13px; color: #666; }
    .pagination-controls { display: flex; gap: 5px; }
    .pagination-controls button { padding: 8px 12px; border: 1px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .pagination-controls button:hover:not(:disabled) { border-color: #FF0049; color: #FF0049; }
    .pagination-controls button:disabled { opacity: 0.5; cursor: not-allowed; }
    .pagination-controls button.active { background: #FF0049; color: #fff; border-color: #FF0049; }
    
    .table-loading { position: relative; min-height: 200px; }
    .table-loading::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); display: flex; align-items: center; justify-content: center; }
    .loading-spinner { display: none; text-align: center; padding: 40px; color: #999; }
    .loading-spinner.show { display: block; }
    
    #workers-table th { cursor: pointer; user-select: none; }
    #workers-table th:first-child, #workers-table th:last-child { cursor: default; }
    #workers-table th .sort-icon { margin-left: 5px; opacity: 0.3; }
    #workers-table th.sorted .sort-icon { opacity: 1; color: #FF0049; }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-border-style">
                    {{-- Search & Filter --}}
                    <div class="search-filter-wrapper">
                        <div class="search-box">
                            <i class="ti ti-search search-icon"></i>
                            <input type="text" id="liveSearchInput" placeholder="{{ __('Search by name, nationality...') }}" autocomplete="off">
                            <span class="search-clear" id="clearSearch"><i class="ti ti-x"></i></span>
                            <span class="search-spinner"><i class="ti ti-loader ti-spin"></i></span>
                        </div>
                        <button type="button" class="filter-toggle-btn" id="filterToggle">
                            <i class="ti ti-adjustments-horizontal"></i>
                            <span>{{ __('Filters') }}</span>
                        </button>
                        <div class="per-page-wrapper">
                            <select id="perPageSelect" class="per-page-select">
                                <option value="25">25</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    
                    {{-- Filter Panel --}}
                    <div class="filter-panel" id="filterPanel">
                        <div class="filter-panel-header">
                            <span class="filter-panel-title"><i class="ti ti-filter"></i>{{ __('Filter Workers') }}</span>
                            <button type="button" class="btn-close" onclick="WorkerTable.toggleFilters()"></button>
                        </div>
                        <div class="filter-grid" style="grid-template-columns: repeat(4, 1fr);">
                            <div class="filter-group">
                                <label><i class="ti ti-home-2 me-1"></i>{{ __('Accommodation') }}</label>
                                <select id="filterHotel">
                                    <option value="">{{ __('All accommodations') }}</option>
                                    @foreach($hotels ?? [] as $hotel)
                                        <option value="{{ $hotel->id }}">{{ $hotel->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-group">
                                <label><i class="ti ti-building-factory me-1"></i>{{ __('Work Place') }}</label>
                                <select id="filterWorkplace">
                                    <option value="">{{ __('All workplaces') }}</option>
                                    @foreach($workplaces ?? [] as $workplace)
                                        <option value="{{ $workplace->id }}">{{ $workplace->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-group">
                                <label><i class="ti ti-flag me-1"></i>{{ __('Nationality') }}</label>
                                <select id="filterNationality">
                                    <option value="">{{ __('All nationalities') }}</option>
                                    @foreach($nationalities ?? [] as $nat)
                                        <option value="{{ $nat }}">{{ __($nat) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="filter-group">
                                <label><i class="ti ti-man"></i>{{ __('Gender') }}</label>
                                <div class="gender-toggles">
                                    <label class="gender-toggle" id="genderMale">
                                        <input type="checkbox" value="male">
                                        <i class="ti ti-man"></i><span>{{ __('Male') }}</span>
                                    </label>
                                    <label class="gender-toggle" id="genderFemale">
                                        <input type="checkbox" value="female">
                                        <i class="ti ti-woman"></i><span>{{ __('Female') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="filter-grid mt-3" style="grid-template-columns: 1fr 1fr 1fr 1fr 2fr;">
                            <div class="filter-group">
                                <label><i class="ti ti-calendar me-1"></i>{{ __('Date of Birth From') }}</label>
                                <input type="date" id="filterDobFrom">
                            </div>
                            <div class="filter-group">
                                <label><i class="ti ti-calendar me-1"></i>{{ __('Date of Birth To') }}</label>
                                <input type="date" id="filterDobTo">
                            </div>
                            <div class="filter-group">
                                <label><i class="ti ti-calendar me-1"></i>{{ __('Registration From') }}</label>
                                <input type="date" id="filterRegDateFrom">
                            </div>
                            <div class="filter-group">
                                <label><i class="ti ti-calendar me-1"></i>{{ __('Registration To') }}</label>
                                <input type="date" id="filterRegDateTo">
                            </div>
                            <div class="filter-group">
                                <label><i class="ti ti-wallet me-1"></i>{{ __('Accommodation Payment') }}</label>
                                <select id="filterAccommodationPayment">
                                    <option value="">{{ __('All') }}</option>
                                    <option value="agency">{{ __('Agency pays') }}</option>
                                    <option value="worker">{{ __('Worker pays') }}</option>
                                    <option value="not_housed">{{ __('Not housed') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="filter-actions">
                            <button type="button" class="filter-btn-reset" onclick="WorkerTable.resetFilters()">
                                <i class="ti ti-refresh me-1"></i>{{ __('Reset') }}
                            </button>
                            <button type="button" class="filter-btn-apply" onclick="WorkerTable.applyFilters()">
                                <i class="ti ti-check me-1"></i>{{ __('Apply Filters') }}
                            </button>
                        </div>
                    </div>
                    
                    <div class="active-filters" id="activeFilters"></div>
                    
                    <div class="results-info">
                        <span>{{ __('Found') }}: <strong id="resultsCount">{{ $totalWorkers ?? 0 }}</strong> {{ __('workers') }}</span>
                    </div>
                    
                    {{-- Bulk Actions Panel --}}
                    <div id="bulk-actions-panel" class="mb-3 p-3 bg-light rounded" style="display: none;">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fw-bold"><span id="selected-count">0</span> {{ __('selected') }}</span>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-filtered-btn">
                                <i class="ti ti-checks me-1"></i>{{ __('Select all filtered') }} (<span id="total-filtered-count">0</span>)
                            </button>
                            <div class="vr mx-2"></div>
                            @can('manage work place')
                                <button type="button" class="btn btn-sm btn-success" id="bulk-assign-btn">
                                    <i class="ti ti-briefcase me-1"></i>{{ __('Assign to Work') }}
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" id="bulk-dismiss-btn">
                                    <i class="ti ti-user-off me-1"></i>{{ __('Dismiss') }}
                                </button>
                            @endcan
                            @can('manage worker')
                                <button type="button" class="btn btn-sm btn-danger" id="bulk-checkout-btn">
                                    <i class="ti ti-logout me-1"></i>{{ __('Check Out') }}
                                </button>
                            @endcan
                            @can('document_generate')
                                <button type="button" class="btn btn-sm btn-info bulk-generate-doc-btn">
                                    <i class="ti ti-file-text me-1"></i>{{ __('Document') }}
                                </button>
                            @endcan
                            @if(Auth::user()->isManager())
                                <button type="button" class="btn btn-sm btn-purple" id="bulk-responsible-btn" style="background-color: #6f42c1; border-color: #6f42c1; color: white;">
                                    <i class="ti ti-user-check me-1"></i>{{ __('Assign Responsible') }}
                                </button>
                            @endif
                            <button type="button" class="btn btn-sm btn-secondary" id="bulk-clear-btn">
                                <i class="ti ti-x me-1"></i>{{ __('Clear') }}
                            </button>
                        </div>
                    </div>

                    <div class="loading-spinner" id="loadingSpinner">
                        <i class="ti ti-loader ti-spin" style="font-size: 32px;"></i>
                        <p>{{ __('Loading...') }}</p>
                    </div>

                    <div class="table-responsive" id="tableContainer">
                        <table class="table" id="workers-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="select-all-checkbox">
                                        </div>
                                    </th>
                                    <th data-sort="first_name">{{ __('First Name') }} <i class="ti ti-arrows-sort sort-icon"></i></th>
                                    <th data-sort="last_name">{{ __('Last Name') }} <i class="ti ti-arrows-sort sort-icon"></i></th>
                                    <th data-sort="dob">{{ __('Date of Birth') }} <i class="ti ti-arrows-sort sort-icon"></i></th>
                                    <th data-sort="gender">{{ __('Gender') }} <i class="ti ti-arrows-sort sort-icon"></i></th>
                                    <th data-sort="nationality">{{ __('Nationality') }} <i class="ti ti-arrows-sort sort-icon"></i></th>
                                    <th data-sort="registration_date">{{ __('Registration Date') }} <i class="ti ti-arrows-sort sort-icon"></i></th>
                                    <th>{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody id="workers-tbody"></tbody>
                        </table>
                    </div>
                    
                    <div class="pagination-wrapper">
                        <div class="pagination-info" id="paginationInfo"></div>
                        <div class="pagination-controls" id="paginationControls"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- Bulk Assign Modal --}}
    <div class="modal fade" id="bulkAssignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Assign to Work') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulk-assign-form" method="POST" action="{{ route('worker.bulk.assign') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="worker_ids" id="assign-worker-ids">
                        <div id="assign-info" class="mb-3"></div>
                        <div class="form-group">
                            <label class="form-label">{{ __('Work Place') }}</label>
                            <select name="work_place_id" class="form-control" required>
                                <option value="">{{ __('Select Work Place') }}</option>
                                @php $workPlaces = \App\Models\WorkPlace::where('created_by', Auth::user()->creatorId())->get(); @endphp
                                @foreach($workPlaces as $place)
                                    <option value="{{ $place->id }}">{{ $place->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-success" id="assign-submit-btn">{{ __('Assign') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bulk Dismiss Modal --}}
    <div class="modal fade" id="bulkDismissModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Dismiss Workers') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulk-dismiss-form" method="POST" action="{{ route('worker.bulk.dismiss') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="worker_ids" id="dismiss-worker-ids">
                        <div id="dismiss-info" class="mb-3"></div>
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-1"></i>
                            {{ __('Workers will be dismissed from their current workplace.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-warning" id="dismiss-submit-btn">{{ __('Dismiss') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bulk Checkout Modal --}}
    <div class="modal fade" id="bulkCheckoutModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Check Out Workers') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulk-checkout-form" method="POST" action="{{ route('worker.bulk.checkout') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="worker_ids" id="checkout-worker-ids">
                        <div id="checkout-info" class="mb-3"></div>
                        <div class="alert alert-danger">
                            <i class="ti ti-alert-triangle me-1"></i>
                            {{ __('Workers will be checked out from their rooms.') }}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-danger" id="checkout-submit-btn">{{ __('Check Out') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Document Generation Modal --}}
    <div class="modal fade" id="bulkDocumentModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Generate Document') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" id="doc-modal-close"></button>
                </div>
                <form id="bulk-document-form" method="POST" action="{{ route('worker.bulk.generate-documents') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="worker_ids" id="doc-worker-ids">
                        <div id="doc-worker-select-group" class="form-group mb-3">
                            <label class="form-label">{{ __('Worker') }} <span class="text-danger">*</span></label>
                            <select name="single_worker_id" id="doc-single-worker" class="form-control">
                                <option value="">{{ __('Select Worker') }}</option>
                            </select>
                        </div>
                        <div class="mb-3" id="doc-selected-workers-info" style="display: none;">
                            <label class="form-label text-info"><i class="ti ti-users me-1"></i>{{ __('Selected Workers:') }}</label>
                            <div id="doc-selected-workers-list" class="text-muted small"></div>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Document Template') }} <span class="text-danger">*</span></label>
                            <select name="template_id" id="doc-template-select" class="form-control" required>
                                <option value="">{{ __('Select Template') }}</option>
                                @php
                                    $templates = \App\Models\DocumentTemplate::where('created_by', Auth::user()->creatorId())
                                        ->where('is_active', true)->orderBy('name')->get();
                                @endphp
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="doc-dynamic-fields"></div>
                        <div class="form-group">
                            <label class="form-label">{{ __('Format') }} <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="format-pdf" value="pdf" checked>
                                    <label class="form-check-label" for="format-pdf"><i class="ti ti-file-type-pdf text-danger me-1"></i>PDF</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="format-docx" value="docx">
                                    <label class="form-check-label" for="format-docx"><i class="ti ti-file-type-doc text-primary me-1"></i>Word</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="format" id="format-xlsx" value="xlsx">
                                    <label class="form-check-label" for="format-xlsx"><i class="ti ti-file-spreadsheet text-success me-1"></i>Excel</label>
                                </div>
                            </div>
                        </div>
                        {{-- Progress Bar --}}
                        <div id="doc-progress-container" class="mt-3" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted" id="doc-progress-text">{{ __('Generating...') }}</small>
                                <small class="text-muted" id="doc-progress-percent">0%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                                     role="progressbar" id="doc-progress-bar" 
                                     style="width: 0%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="doc-cancel-btn">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-info" id="doc-generate-btn">
                            <i class="ti ti-download me-1" id="doc-btn-icon"></i>
                            <span id="doc-btn-text">{{ __('Generate') }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bulk Assign Responsible Modal --}}
    @if(Auth::user()->isManager())
    <div class="modal fade" id="bulkResponsibleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Assign Responsible') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="bulk-responsible-form" method="POST" action="{{ route('worker.bulk.assign-responsible') }}">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="worker_ids" id="responsible-worker-ids">
                        <div class="mb-3">
                            <p class="text-muted">
                                <i class="ti ti-info-circle me-1"></i>
                                {{ __('Select a coordinator to assign as responsible for the selected workers.') }}
                            </p>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Responsible Person') }} <span class="text-danger">*</span></label>
                            <select name="responsible_id" id="responsible-select" class="form-control" required>
                                <option value="">{{ __('Select Coordinator') }}</option>
                                @php
                                    $curators = Auth::user()->assignedCurators ?? collect();
                                @endphp
                                @foreach($curators as $curator)
                                    <option value="{{ $curator->id }}">{{ $curator->name }} ({{ $curator->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="responsible-selected-info" class="mb-3" style="display: none;">
                            <label class="form-label text-info"><i class="ti ti-users me-1"></i>{{ __('Selected Workers:') }}</label>
                            <div id="responsible-selected-list" class="text-muted small"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary" id="responsible-submit-btn" style="background-color: #6f42c1; border-color: #6f42c1;">
                            <i class="ti ti-user-check me-1"></i>{{ __('Assign') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endsection


@push('script-page')
<script>
const WorkerTable = {
    searchUrl: '{{ route("worker.search") }}',
    filteredIdsUrl: '{{ route("worker.filtered.ids") }}',
    data: [],
    selectedIds: new Set(),
    selectAllFiltered: false,
    currentFilters: {},
    currentPage: 1,
    perPage: 50,
    totalRecords: 0,
    sortField: 'first_name',
    sortDir: 'asc',
    searchTimeout: null,
    canEdit: {{ Auth::user()->can('edit worker') ? 'true' : 'false' }},
    canDelete: {{ Auth::user()->can('delete worker') ? 'true' : 'false' }},

    init() {
        this.bindEvents();
        this.loadData();
    },

    bindEvents() {
        // Search
        const searchInput = document.getElementById('liveSearchInput');
        const clearBtn = document.getElementById('clearSearch');
        
        searchInput.addEventListener('input', (e) => {
            clearTimeout(this.searchTimeout);
            const val = e.target.value;
            clearBtn.style.display = val.length > 0 ? 'block' : 'none';
            searchInput.classList.toggle('searching', val.length > 0);
            
            this.searchTimeout = setTimeout(() => {
                this.currentFilters.search = val;
                this.currentPage = 1;
                this.loadData();
            }, 300);
        });
        
        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            searchInput.classList.remove('searching');
            clearBtn.style.display = 'none';
            this.currentFilters.search = '';
            this.currentPage = 1;
            this.loadData();
        });

        // Filter toggle
        document.getElementById('filterToggle').addEventListener('click', () => this.toggleFilters());

        // Gender toggles
        document.querySelectorAll('.gender-toggle input').forEach(cb => {
            cb.addEventListener('change', function() {
                this.closest('.gender-toggle').classList.toggle('active', this.checked);
            });
        });

        // Per page
        document.getElementById('perPageSelect').addEventListener('change', (e) => {
            this.perPage = parseInt(e.target.value);
            this.currentPage = 1;
            this.loadData();
        });

        // Select all checkbox
        document.getElementById('select-all-checkbox').addEventListener('change', (e) => {
            this.selectAllFiltered = false;
            if (e.target.checked) {
                this.data.forEach(w => this.selectedIds.add(w.id));
            } else {
                this.selectedIds.clear();
            }
            this.updateCheckboxes();
            this.updateBulkPanel();
        });

        // Select all filtered button
        document.getElementById('select-all-filtered-btn').addEventListener('click', () => this.selectAllFilteredWorkers());

        // Bulk action buttons
        document.getElementById('bulk-assign-btn')?.addEventListener('click', () => this.openBulkModal('assign'));
        document.getElementById('bulk-dismiss-btn')?.addEventListener('click', () => this.openBulkModal('dismiss'));
        document.getElementById('bulk-checkout-btn')?.addEventListener('click', () => this.openBulkModal('checkout'));
        document.getElementById('bulk-responsible-btn')?.addEventListener('click', () => this.openBulkModal('responsible'));
        document.querySelectorAll('.bulk-generate-doc-btn').forEach(btn => {
            btn.addEventListener('click', () => this.openBulkModal('document'));
        });
        document.getElementById('bulk-generate-doc-btn')?.addEventListener('click', () => this.openDocumentModal());
        document.getElementById('bulk-clear-btn').addEventListener('click', () => {
            this.selectedIds.clear();
            this.selectAllFiltered = false;
            this.updateCheckboxes();
            this.updateBulkPanel();
        });

        // Sorting
        document.querySelectorAll('#workers-table th[data-sort]').forEach(th => {
            th.addEventListener('click', () => {
                const field = th.dataset.sort;
                if (this.sortField === field) {
                    this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortField = field;
                    this.sortDir = 'asc';
                }
                this.loadData();
            });
        });
    },

    toggleFilters() {
        document.getElementById('filterPanel').classList.toggle('show');
    },

    applyFilters() {
        this.currentFilters.hotel_id = document.getElementById('filterHotel').value;
        this.currentFilters.workplace_id = document.getElementById('filterWorkplace').value;
        this.currentFilters.nationality = document.getElementById('filterNationality').value;
        this.currentFilters.dob_from = document.getElementById('filterDobFrom').value;
        this.currentFilters.dob_to = document.getElementById('filterDobTo').value;
        this.currentFilters.reg_from = document.getElementById('filterRegDateFrom').value;
        this.currentFilters.reg_to = document.getElementById('filterRegDateTo').value;
        this.currentFilters.accommodation_payment = document.getElementById('filterAccommodationPayment').value;
        
        const genders = [];
        if (document.querySelector('#genderMale input').checked) genders.push('male');
        if (document.querySelector('#genderFemale input').checked) genders.push('female');
        this.currentFilters.gender = genders;
        
        this.currentPage = 1;
        this.loadData();
        this.updateActiveFilters();
        this.toggleFilters();
    },

    resetFilters() {
        document.getElementById('filterHotel').value = '';
        document.getElementById('filterWorkplace').value = '';
        document.getElementById('filterNationality').value = '';
        document.getElementById('filterDobFrom').value = '';
        document.getElementById('filterDobTo').value = '';
        document.getElementById('filterRegDateFrom').value = '';
        document.getElementById('filterRegDateTo').value = '';
        document.getElementById('filterAccommodationPayment').value = '';
        document.querySelectorAll('.gender-toggle').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.gender-toggle input').forEach(cb => cb.checked = false);
        
        this.currentFilters = { search: this.currentFilters.search || '' };
        this.currentPage = 1;
        this.loadData();
        this.updateActiveFilters();
    },

    updateActiveFilters() {
        const container = document.getElementById('activeFilters');
        const chips = [];
        const filterToggle = document.getElementById('filterToggle');
        let count = 0;
        
        // Gender translations
        const genderLabels = {
            'male': '{{ __("Male") }}',
            'female': '{{ __("Female") }}'
        };
        
        if (this.currentFilters.hotel_id) {
            const name = document.querySelector(`#filterHotel option[value="${this.currentFilters.hotel_id}"]`)?.text;
            chips.push(`<span class="active-filter-chip" onclick="WorkerTable.removeFilter('hotel_id')"><i class="ti ti-home-2 me-1"></i>${name} <i class="ti ti-x chip-remove"></i></span>`);
            count++;
        }
        if (this.currentFilters.workplace_id) {
            const name = document.querySelector(`#filterWorkplace option[value="${this.currentFilters.workplace_id}"]`)?.text;
            chips.push(`<span class="active-filter-chip" onclick="WorkerTable.removeFilter('workplace_id')"><i class="ti ti-building-factory me-1"></i>${name} <i class="ti ti-x chip-remove"></i></span>`);
            count++;
        }
        if (this.currentFilters.nationality) {
            chips.push(`<span class="active-filter-chip" onclick="WorkerTable.removeFilter('nationality')"><i class="ti ti-flag me-1"></i>${this.currentFilters.nationality} <i class="ti ti-x chip-remove"></i></span>`);
            count++;
        }
        if (this.currentFilters.gender?.length) {
            const genderText = this.currentFilters.gender.map(g => genderLabels[g] || g).join(', ');
            chips.push(`<span class="active-filter-chip" onclick="WorkerTable.removeFilter('gender')"><i class="ti ti-man me-1"></i>${genderText} <i class="ti ti-x chip-remove"></i></span>`);
            count++;
        }
        if (this.currentFilters.accommodation_payment) {
            const paymentLabels = {
                'agency': '{{ __("Agency pays") }}',
                'worker': '{{ __("Worker pays") }}',
                'not_housed': '{{ __("Not housed") }}'
            };
            const paymentText = paymentLabels[this.currentFilters.accommodation_payment] || this.currentFilters.accommodation_payment;
            chips.push(`<span class="active-filter-chip" onclick="WorkerTable.removeFilter('accommodation_payment')"><i class="ti ti-wallet me-1"></i>${paymentText} <i class="ti ti-x chip-remove"></i></span>`);
            count++;
        }
        
        container.innerHTML = chips.join('');
        
        // Update filter button
        const existingCount = filterToggle.querySelector('.filter-count');
        if (existingCount) existingCount.remove();
        if (count > 0) {
            filterToggle.classList.add('has-filters');
            filterToggle.insertAdjacentHTML('beforeend', `<span class="filter-count">${count}</span>`);
        } else {
            filterToggle.classList.remove('has-filters');
        }
    },

    removeFilter(key) {
        if (key === 'hotel_id') document.getElementById('filterHotel').value = '';
        if (key === 'workplace_id') document.getElementById('filterWorkplace').value = '';
        if (key === 'nationality') document.getElementById('filterNationality').value = '';
        if (key === 'gender') {
            document.querySelectorAll('.gender-toggle').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.gender-toggle input').forEach(cb => cb.checked = false);
        }
        if (key === 'accommodation_payment') document.getElementById('filterAccommodationPayment').value = '';
        delete this.currentFilters[key];
        this.currentPage = 1;
        this.loadData();
        this.updateActiveFilters();
    },

    async loadData() {
        const spinner = document.getElementById('loadingSpinner');
        const tbody = document.getElementById('workers-tbody');
        spinner.classList.add('show');
        
        const params = new URLSearchParams({
            page: this.currentPage,
            per_page: this.perPage,
            sort: this.sortField,
            dir: this.sortDir,
            ...this.currentFilters
        });
        
        if (this.currentFilters.gender?.length) {
            params.delete('gender');
            this.currentFilters.gender.forEach(g => params.append('gender[]', g));
        }

        try {
            const response = await fetch(`${this.searchUrl}?${params}`);
            const result = await response.json();
            
            this.data = result.data;
            this.totalRecords = result.total;
            
            this.renderTable();
            this.renderPagination(result);
            document.getElementById('resultsCount').textContent = result.total;
            document.getElementById('total-filtered-count').textContent = result.total;
            
            // Show/hide "Select all filtered" button based on whether there are more results than displayed
            const selectAllBtn = document.getElementById('select-all-filtered-btn');
            if (selectAllBtn) {
                selectAllBtn.style.display = result.total > this.perPage ? 'inline-flex' : 'none';
            }
            
        } catch (error) {
            console.error('Error loading workers:', error);
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-danger">{{ __('Error loading data') }}</td></tr>`;
        } finally {
            spinner.classList.remove('show');
        }
    },

    renderTable() {
        const tbody = document.getElementById('workers-tbody');
        const searchTerm = this.currentFilters.search || '';
        
        // Update sort icons
        document.querySelectorAll('#workers-table th[data-sort]').forEach(th => {
            th.classList.remove('sorted');
            const icon = th.querySelector('.sort-icon');
            icon.className = 'ti ti-arrows-sort sort-icon';
            if (th.dataset.sort === this.sortField) {
                th.classList.add('sorted');
                icon.className = `ti ti-arrow-${this.sortDir === 'asc' ? 'up' : 'down'} sort-icon`;
            }
        });
        
        if (this.data.length === 0) {
            @if(Auth::user()->isCurator())
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5">
                <i class="ti ti-users-group" style="font-size: 48px; opacity: 0.3;"></i>
                <h5 class="mt-3">{{ __('No Assigned Workers') }}</h5>
                <p class="text-muted">{{ __('You have no assigned workers. Contact your manager.') }}</p>
            </td></tr>`;
            @else
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">{{ __('No workers found') }}</td></tr>`;
            @endif
            return;
        }
        
        tbody.innerHTML = this.data.map(worker => {
            const isSelected = this.selectedIds.has(worker.id);
            const firstName = this.highlight(worker.first_name, searchTerm);
            const lastName = this.highlight(worker.last_name, searchTerm);
            const nationality = this.highlight(worker.nationality || '', searchTerm);
            
            return `
                <tr data-worker-id="${worker.id}">
                    <td>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input worker-checkbox" 
                                value="${worker.id}" ${isSelected ? 'checked' : ''}
                                data-name="${worker.first_name} ${worker.last_name}"
                                data-is-working="${worker.is_working ? '1' : '0'}"
                                data-work-place="${worker.work_place}"
                                data-is-housed="${worker.is_housed ? '1' : '0'}"
                                data-hotel="${worker.hotel}"
                                onchange="WorkerTable.toggleSelection(${worker.id}, this.checked)">
                        </div>
                    </td>
                    <td><a href="${worker.show_url}" class="text-primary fw-medium">${firstName}</a></td>
                    <td><a href="${worker.show_url}" class="text-primary fw-medium">${lastName}</a></td>
                    <td>${worker.dob}</td>
                    <td>${worker.gender_label}</td>
                    <td>${worker.nationality_flag}${nationality}</td>
                    <td>${worker.registration_date}</td>
                    <td class="Action">
                        <span>
                            ${this.canEdit ? `
                            <div class="action-btn me-2">
                                <a href="#" data-url="${worker.edit_url}" data-ajax-popup="true" data-title="{{ __('Edit Worker') }}"
                                    class="mx-3 btn btn-sm align-items-center bg-info" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                    <i class="ti ti-pencil text-white"></i>
                                </a>
                            </div>` : ''}
                            ${this.canDelete ? `
                            <div class="action-btn">
                                <form method="POST" action="${worker.delete_url}" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para bg-danger"
                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                        data-confirm="{{ __('Are you sure?') }}|{{ __('This action cannot be undone. Do you want to continue?') }}"
                                        data-confirm-yes="this.closest('form').submit();">
                                        <i class="ti ti-trash text-white"></i>
                                    </a>
                                </form>
                            </div>` : ''}
                            <div class="action-btn ms-2">
                                <a href="${worker.show_url}" class="mx-3 btn btn-sm align-items-center bg-warning"
                                    data-bs-toggle="tooltip" title="{{ __('View') }}">
                                    <i class="ti ti-eye text-white"></i>
                                </a>
                            </div>
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
        
        // Re-init tooltips
        if (typeof bootstrap !== 'undefined') {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        }
    },

    highlight(text, term) {
        if (!term || !text) return text;
        const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<span class="highlight">$1</span>');
    },

    renderPagination(result) {
        const info = document.getElementById('paginationInfo');
        const controls = document.getElementById('paginationControls');
        
        const start = (result.page - 1) * result.per_page + 1;
        const end = Math.min(result.page * result.per_page, result.total);
        info.textContent = `${start}-${end} {{ __('of') }} ${result.total}`;
        
        let buttons = '';
        const lastPage = result.last_page;
        
        buttons += `<button ${result.page <= 1 ? 'disabled' : ''} onclick="WorkerTable.goToPage(${result.page - 1})"><i class="ti ti-chevron-left"></i></button>`;
        
        // Page numbers
        const range = 2;
        for (let i = 1; i <= lastPage; i++) {
            if (i === 1 || i === lastPage || (i >= result.page - range && i <= result.page + range)) {
                buttons += `<button class="${i === result.page ? 'active' : ''}" onclick="WorkerTable.goToPage(${i})">${i}</button>`;
            } else if (i === result.page - range - 1 || i === result.page + range + 1) {
                buttons += `<button disabled>...</button>`;
            }
        }
        
        buttons += `<button ${result.page >= lastPage ? 'disabled' : ''} onclick="WorkerTable.goToPage(${result.page + 1})"><i class="ti ti-chevron-right"></i></button>`;
        
        controls.innerHTML = buttons;
    },

    goToPage(page) {
        this.currentPage = page;
        this.loadData();
    },

    toggleSelection(id, checked) {
        if (checked) {
            this.selectedIds.add(id);
        } else {
            this.selectedIds.delete(id);
            this.selectAllFiltered = false;
        }
        this.updateBulkPanel();
    },

    updateCheckboxes() {
        document.querySelectorAll('.worker-checkbox').forEach(cb => {
            cb.checked = this.selectedIds.has(parseInt(cb.value));
        });
        document.getElementById('select-all-checkbox').checked = 
            this.data.length > 0 && this.data.every(w => this.selectedIds.has(w.id));
    },

    updateBulkPanel() {
        const panel = document.getElementById('bulk-actions-panel');
        const selectAllBtn = document.getElementById('select-all-filtered-btn');
        const count = this.selectAllFiltered ? this.totalRecords : this.selectedIds.size;
        document.getElementById('selected-count').textContent = count;
        panel.style.display = count > 0 ? 'block' : 'none';
        
        // Show "Select all filtered" button only if there are more results than currently displayed
        if (selectAllBtn) {
            selectAllBtn.style.display = this.totalRecords > this.perPage ? 'inline-flex' : 'none';
        }
    },

    async selectAllFilteredWorkers() {
        const params = new URLSearchParams(this.currentFilters);
        if (this.currentFilters.gender?.length) {
            params.delete('gender');
            this.currentFilters.gender.forEach(g => params.append('gender[]', g));
        }
        
        try {
            const response = await fetch(`${this.filteredIdsUrl}?${params}`);
            const result = await response.json();
            result.ids.forEach(id => this.selectedIds.add(id));
            this.selectAllFiltered = true;
            this.updateCheckboxes();
            this.updateBulkPanel();
        } catch (error) {
            console.error('Error fetching filtered IDs:', error);
        }
    },

    getSelectedWorkerIds() {
        return Array.from(this.selectedIds).join(',');
    },

    openBulkModal(type) {
        const ids = this.getSelectedWorkerIds();
        if (!ids) return;
        
        if (type === 'assign') {
            document.getElementById('assign-worker-ids').value = ids;
            document.getElementById('assign-info').innerHTML = `<span class="text-info"><i class="ti ti-users me-1"></i>{{ __('Workers selected:') }} ${this.selectedIds.size}</span>`;
            new bootstrap.Modal(document.getElementById('bulkAssignModal')).show();
        } else if (type === 'dismiss') {
            document.getElementById('dismiss-worker-ids').value = ids;
            document.getElementById('dismiss-info').innerHTML = `<span class="text-warning"><i class="ti ti-users me-1"></i>{{ __('Workers selected:') }} ${this.selectedIds.size}</span>`;
            new bootstrap.Modal(document.getElementById('bulkDismissModal')).show();
        } else if (type === 'checkout') {
            document.getElementById('checkout-worker-ids').value = ids;
            document.getElementById('checkout-info').innerHTML = `<span class="text-danger"><i class="ti ti-users me-1"></i>{{ __('Workers selected:') }} ${this.selectedIds.size}</span>`;
            new bootstrap.Modal(document.getElementById('bulkCheckoutModal')).show();
        } else if (type === 'document') {
            document.getElementById('doc-worker-ids').value = ids;
            document.getElementById('doc-worker-select-group').style.display = 'none';
            document.getElementById('doc-selected-workers-info').style.display = 'block';
            document.getElementById('doc-selected-workers-list').textContent = `${this.selectedIds.size} {{ __('workers selected') }}`;
            new bootstrap.Modal(document.getElementById('bulkDocumentModal')).show();
        } else if (type === 'responsible') {
            document.getElementById('responsible-worker-ids').value = ids;
            document.getElementById('responsible-selected-info').style.display = 'block';
            document.getElementById('responsible-selected-list').textContent = `${this.selectedIds.size} {{ __('workers selected') }}`;
            new bootstrap.Modal(document.getElementById('bulkResponsibleModal')).show();
        }
    },

    openDocumentModal() {
        // For single worker selection from header button
        document.getElementById('doc-worker-ids').value = '';
        document.getElementById('doc-worker-select-group').style.display = 'block';
        document.getElementById('doc-selected-workers-info').style.display = 'none';
        
        // Populate worker dropdown with current filtered data
        const select = document.getElementById('doc-single-worker');
        select.innerHTML = '<option value="">{{ __("Select Worker") }}</option>';
        this.data.forEach(w => {
            select.innerHTML += `<option value="${w.id}">${w.first_name} ${w.last_name}</option>`;
        });
        
        new bootstrap.Modal(document.getElementById('bulkDocumentModal')).show();
    }
};

document.addEventListener('DOMContentLoaded', () => WorkerTable.init());

// Document Generation Progress Handler
(function() {
    const form = document.getElementById('bulk-document-form');
    const btn = document.getElementById('doc-generate-btn');
    const btnText = document.getElementById('doc-btn-text');
    const btnIcon = document.getElementById('doc-btn-icon');
    const progressContainer = document.getElementById('doc-progress-container');
    const progressBar = document.getElementById('doc-progress-bar');
    const progressPercent = document.getElementById('doc-progress-percent');
    const progressText = document.getElementById('doc-progress-text');
    const cancelBtn = document.getElementById('doc-cancel-btn');
    const closeBtn = document.getElementById('doc-modal-close');
    
    let isGenerating = false;
    let progressInterval = null;
    let downloadDetected = false;
    
    form.addEventListener('submit', function(e) {
        if (isGenerating) {
            e.preventDefault();
            return;
        }
        
        // Get worker count for progress estimation
        const workerIds = document.getElementById('doc-worker-ids').value;
        const singleWorker = document.getElementById('doc-single-worker').value;
        let workerCount = 1;
        
        if (workerIds) {
            workerCount = workerIds.split(',').filter(id => id.trim()).length;
        }
        
        // Start progress animation
        isGenerating = true;
        downloadDetected = false;
        btn.disabled = true;
        cancelBtn.disabled = true;
        closeBtn.style.display = 'none';
        btnText.textContent = '{{ __("Generating...") }}';
        btnIcon.className = 'ti ti-loader ti-spin me-1';
        progressContainer.style.display = 'block';
        
        // Estimate time: ~300ms per PDF document for bulk generation
        const estimatedTime = Math.max(5000, workerCount * 300);
        let progress = 0;
        const startTime = Date.now();
        
        progressInterval = setInterval(() => {
            if (downloadDetected) return;
            
            const elapsed = Date.now() - startTime;
            // Slow logarithmic progress that approaches but never reaches 95%
            progress = Math.min(94, 94 * (1 - Math.exp(-elapsed / (estimatedTime * 0.7))));
            
            progressBar.style.width = progress + '%';
            progressPercent.textContent = Math.round(progress) + '%';
            
            // Update text based on progress
            if (progress < 20) {
                progressText.textContent = '{{ __("Preparing documents...") }}';
            } else if (progress < 60) {
                progressText.textContent = '{{ __("Generating documents...") }}';
            } else if (progress < 90) {
                progressText.textContent = '{{ __("Finalizing...") }}';
            } else {
                progressText.textContent = '{{ __("Almost done...") }}';
            }
        }, 200);
        
        // Maximum wait time: 10 minutes - after that assume something went wrong
        setTimeout(() => {
            if (isGenerating && !downloadDetected) {
                progressText.textContent = '{{ __("Taking longer than expected...") }}';
            }
        }, 120000); // 2 minutes warning
        
        setTimeout(() => {
            if (isGenerating && !downloadDetected) {
                resetProgress();
                alert('{{ __("Generation timeout. Please try with fewer workers or contact support.") }}');
            }
        }, 600000); // 10 minutes max
    });
    
    // Reset when modal is hidden
    document.getElementById('bulkDocumentModal').addEventListener('hidden.bs.modal', function() {
        resetProgress();
    });
    
    function resetProgress() {
        if (progressInterval) {
            clearInterval(progressInterval);
            progressInterval = null;
        }
        isGenerating = false;
        downloadDetected = false;
        btn.disabled = false;
        cancelBtn.disabled = false;
        closeBtn.style.display = '';
        btnText.textContent = '{{ __("Generate") }}';
        btnIcon.className = 'ti ti-download me-1';
        progressContainer.style.display = 'none';
        progressBar.style.width = '0%';
        progressPercent.textContent = '0%';
    }
    
    function completeProgress() {
        downloadDetected = true;
        if (progressInterval) {
            clearInterval(progressInterval);
            progressInterval = null;
        }
        progressBar.style.width = '100%';
        progressPercent.textContent = '100%';
        progressText.textContent = '{{ __("Complete!") }}';
        setTimeout(resetProgress, 2000);
    }
    
    // Detect download via blur/focus (when browser shows save dialog)
    let blurTime = 0;
    window.addEventListener('blur', function() {
        if (isGenerating) {
            blurTime = Date.now();
        }
    });
    
    window.addEventListener('focus', function() {
        if (isGenerating && !downloadDetected && blurTime > 0) {
            const blurDuration = Date.now() - blurTime;
            // If window was blurred for more than 300ms, likely a download dialog
            if (blurDuration > 300) {
                completeProgress();
            }
            blurTime = 0;
        }
    });
})();
</script>
@endpush