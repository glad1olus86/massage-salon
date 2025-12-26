@extends('layouts.admin')

@section('page-title')
    {{ __('Hotel Management') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Hotels') }}</li>
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
                @can('manage hotel')
                    <a href="#" data-url="{{ route('hotel.export.modal') }}" data-ajax-popup="true"
                        data-title="{{ __('Export Hotels') }}" data-bs-toggle="tooltip" title="{{ __('Export') }}"
                        data-size="lg"
                        class="btn btn-sm btn-secondary me-1">
                        <i class="ti ti-file-export"></i>
                    </a>
                @endcan
                @can('create hotel')
                    <a href="#" data-url="{{ route('hotel.create') }}" data-ajax-popup="true"
                        data-title="{{ __('Create New Hotel') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                        class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i>
                    </a>
                @endcan
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body table-border-style">
                            {{-- Search --}}
                            <div class="search-filter-wrapper">
                                <div class="search-box">
                                    <i class="ti ti-search search-icon"></i>
                                    <input type="text" id="liveSearchInput" placeholder="{{ __('Search by name, address...') }}" autocomplete="off">
                                    <span class="search-clear" id="clearSearch"><i class="ti ti-x"></i></span>
                                </div>
                            </div>
                            
                            {{-- Results Count --}}
                            <div class="results-count">
                                {{ __('Found') }}: <strong id="resultsCount">{{ count($hotels) }}</strong> {{ __('hotels') }}
                            </div>
                            
                            @if($hotels->isEmpty() && Auth::user()->isCurator())
                                <div class="text-center py-5">
                                    <i class="ti ti-building-community" style="font-size: 48px; opacity: 0.3;"></i>
                                    <h5 class="mt-3">{{ __('No Assigned Hotels') }}</h5>
                                    <p class="text-muted">{{ __('You have no assigned hotels. Contact your manager.') }}</p>
                                </div>
                            @else
                            <div class="table-responsive">
                                <table class="table" id="hotels-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Name') }}</th>
                                            <th>{{ __('Address') }}</th>
                                            <th>{{ __('Capacity') }}</th>
                                            <th>{{ __('Contacts') }}</th>
                                            <th width="200px">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="font-style">
                                        @foreach ($hotels as $hotel)
                                            @php
                                                $totalCapacity = $hotel->rooms->sum('capacity');
                                                $totalOccupied = $hotel->rooms->sum(function ($room) {
                                                    return $room->currentAssignments->count();
                                                });
                                                $percentage =
                                                    $totalCapacity > 0 ? ($totalOccupied / $totalCapacity) * 100 : 0;
                                                $color =
                                                    $percentage < 50
                                                        ? 'bg-danger'
                                                        : ($percentage < 100
                                                            ? 'bg-warning'
                                                            : 'bg-success');
                                            @endphp
                                            <tr data-name="{{ strtolower($hotel->name) }}" data-address="{{ strtolower($hotel->address ?? '') }}">
                                                <td>
                                                    <a href="{{ route('hotel.rooms', $hotel->id) }}" class="text-primary fw-medium">
                                                        {{ $hotel->name }}
                                                    </a>
                                                </td>
                                                <td>{{ $hotel->address }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="me-2">{{ $totalOccupied }} /
                                                            {{ $totalCapacity }}</span>
                                                        <div class="progress w-100" style="height: 6px;">
                                                            <div class="progress-bar {{ $color }}"
                                                                role="progressbar" style="width: {{ $percentage }}%;"
                                                                aria-valuenow="{{ $percentage }}" aria-valuemin="0"
                                                                aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    @if ($hotel->phone)
                                                        <div><i class="ti ti-phone"></i> {{ $hotel->phone }}</div>
                                                    @endif
                                                    @if ($hotel->email)
                                                        <div><i class="ti ti-mail"></i> {{ $hotel->email }}</div>
                                                    @endif
                                                    @if (!$hotel->phone && !$hotel->email)
                                                        <span class="text-muted">{{ __('No data') }}</span>
                                                    @endif
                                                </td>

                                                <td class="Action">
                                                    <span>
                                                        @can('edit hotel')
                                                            <div class="action-btn me-2">

                                                                <a href="#"
                                                                    data-url="{{ URL::to('hotel/' . $hotel->id . '/edit') }}"
                                                                    data-ajax-popup="true"
                                                                    data-title="{{ __('Edit Hotel') }}"
                                                                    class="mx-3 btn btn-sm align-items-center bg-info"
                                                                    data-bs-toggle="tooltip" title="{{ __('Edit') }}"
                                                                    data-original-title="{{ __('Edit') }}">
                                                                    <i class="ti ti-pencil text-white"></i></a>
                                                            </div>
                                                        @endcan
                                                        @can('delete hotel')
                                                            <div class="action-btn ">
                                                                {!! Form::open([
                                                                    'method' => 'DELETE',
                                                                    'route' => ['hotel.destroy', $hotel->id],
                                                                    'id' => 'delete-form-' . $hotel->id,
                                                                ]) !!}


                                                                <a href="#"
                                                                    class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger"
                                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                    data-original-title="{{ __('Delete') }}"
                                                                    data-confirm="{{ __('Are you sure?') . '|' . __('This action cannot be undone. Do you want to continue?') }}"
                                                                    data-confirm-yes="document.getElementById('delete-form-{{ $hotel->id }}').submit();"><i
                                                                        class="ti ti-trash text-white"></i></a>
                                                                {!! Form::close() !!}
                                                            </div>
                                                        @endcan
                                                        <div class="action-btn ms-2">
                                                            <a href="{{ route('hotel.rooms', $hotel->id) }}"
                                                                class="mx-3 btn btn-sm align-items-center bg-warning"
                                                                data-bs-toggle="tooltip"
                                                                title="{{ __('View Rooms') }}">
                                                                <i class="ti ti-eye text-white"></i>
                                                            </a>
                                                        </div>
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
    var rows = document.querySelectorAll('#hotels-table tbody tr');

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
