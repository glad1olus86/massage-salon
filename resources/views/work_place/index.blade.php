@extends('layouts.admin')

@section('page-title')
    {{ __('Work Places Management') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Work Places') }}</li>
@endsection

@section('action-btn')
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
            <div class="my-3 d-flex justify-content-end">
                @can('manage work place')
                    <a href="#" data-url="{{ route('work-place.export.modal') }}" data-ajax-popup="true"
                        data-title="{{ __('Export Work Places') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
                        data-size="lg" class="btn btn-sm btn-secondary me-1">
                        <i class="ti ti-file-export"></i>
                    </a>
                @endcan
                @can('create work place')
                    <a href="#" data-url="{{ route('work-place.create') }}" data-ajax-popup="true"
                        data-title="{{ __('Create Work Place') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                        class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i>
                    </a>
                @endcan
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body table-border-style">
                            <div class="search-filter-wrapper">
                                <div class="search-box">
                                    <i class="ti ti-search search-icon"></i>
                                    <input type="text" id="liveSearchInput" placeholder="{{ __('Search by name, address...') }}" autocomplete="off">
                                    <span class="search-clear" id="clearSearch"><i class="ti ti-x"></i></span>
                                </div>
                            </div>

                            <div class="results-count">
                                {{ __('Found') }}: <strong id="resultsCount">{{ count($workPlaces) }}</strong> {{ __('work places') }}
                            </div>

                            @if($workPlaces->isEmpty() && Auth::user()->isCurator())
                                <div class="text-center py-5">
                                    <i class="ti ti-briefcase-off" style="font-size: 48px; opacity: 0.3;"></i>
                                    <h5 class="mt-3">{{ __('No Assigned Work Places') }}</h5>
                                    <p class="text-muted">{{ __('You have no assigned work places. Contact your manager.') }}</p>
                                </div>
                            @else
                            <div class="table-responsive">
                                <table class="table" id="workplaces-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Employees') }}</th>
                                            <th>{{ __('Address') }}</th>
                                            <th>{{ __('Contacts') }}</th>
                                            <th width="200px">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="font-style">
                                        @foreach ($workPlaces as $workPlace)
                                            <tr data-name="{{ strtolower($workPlace->name) }}" data-address="{{ strtolower($workPlace->address ?? '') }}">
                                                <td>
                                                    @if($workPlace->positions_count == 1)
                                                        <a href="#" data-url="{{ route('positions.workers', $workPlace->positions->first()->id) }}"
                                                            data-ajax-popup="true" data-title="{{ __('Employees') }}: {{ $workPlace->name }}"
                                                            data-size="lg" class="text-primary fw-bold">{{ $workPlace->name }}</a>
                                                    @elseif($workPlace->positions_count > 1)
                                                        <a href="{{ route('work-place.positions', $workPlace->id) }}" class="text-primary fw-bold">{{ $workPlace->name }}</a>
                                                    @else
                                                        <a href="{{ route('work-place.positions', $workPlace->id) }}" class="text-muted fw-bold">{{ $workPlace->name }}</a>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($workPlace->currentAssignments->count() > 0)
                                                        <span class="badge bg-success">{{ $workPlace->currentAssignments->count() }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">0</span>
                                                    @endif
                                                </td>
                                                <td>{{ $workPlace->address }}</td>
                                                <td>
                                                    @if ($workPlace->phone)
                                                        <div><i class="ti ti-phone"></i> {{ $workPlace->phone }}</div>
                                                    @endif
                                                    @if ($workPlace->email)
                                                        <div><i class="ti ti-mail"></i> {{ $workPlace->email }}</div>
                                                    @endif
                                                    @if (!$workPlace->phone && !$workPlace->email)
                                                        <span class="text-muted">{{ __('No data') }}</span>
                                                    @endif
                                                </td>
                                                <td class="Action">
                                                    <span>
                                                        @can('edit work place')
                                                            <div class="action-btn me-2">
                                                                <a href="#" data-url="{{ URL::to('work-place/' . $workPlace->id . '/edit') }}"
                                                                    data-ajax-popup="true" data-title="{{ __('Edit Work Place') }}"
                                                                    class="mx-3 btn btn-sm align-items-center bg-info"
                                                                    data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                                    <i class="ti ti-pencil text-white"></i></a>
                                                            </div>
                                                        @endcan
                                                        @can('delete work place')
                                                            <div class="action-btn">
                                                                {!! Form::open(['method' => 'DELETE', 'route' => ['work-place.destroy', $workPlace->id], 'id' => 'delete-form-' . $workPlace->id]) !!}
                                                                <a href="#" class="mx-3 btn btn-sm align-items-center bs-pass-para bg-danger"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are you sure?') . '|' . __('This action cannot be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $workPlace->id }}').submit();">
                                                                    <i class="ti ti-trash text-white"></i></a>
                                                                {!! Form::close() !!}
                                                            </div>
                                                        @endcan
                                                        @can('manage work place')
                                                            <div class="action-btn ms-2">
                                                                <a href="{{ route('work-place.positions', $workPlace->id) }}"
                                                                    class="mx-3 btn btn-sm align-items-center bg-success"
                                                                    data-bs-toggle="tooltip" title="{{ __('Positions') }}">
                                                                    <i class="ti ti-briefcase text-white"></i></a>
                                                            </div>
                                                            <div class="action-btn ms-2">
                                                                <a href="#" data-url="{{ route('work-place.workers', $workPlace->id) }}"
                                                                    data-ajax-popup="true" data-title="{{ __('Employees') }}" data-size="lg"
                                                                    class="mx-3 btn btn-sm align-items-center bg-warning"
                                                                    data-bs-toggle="tooltip" title="{{ __('View Employees') }}">
                                                                    <i class="ti ti-users text-white"></i></a>
                                                            </div>
                                                        @endcan
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
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
    var rows = document.querySelectorAll('#workplaces-table tbody tr');

    searchInput.addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        var visibleCount = 0;

        clearBtn.style.display = query.length > 0 ? 'block' : 'none';
        this.classList.toggle('searching', query.length > 0);

        rows.forEach(function(row) {
            var name = row.dataset.name || '';
            var address = row.dataset.address || '';

            if (query.length < 2 || name.includes(query) || address.includes(query)) {
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
