@extends('layouts.admin')

@section('page-title')
    {{ __('Attendance Schedule') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Attendance') }}</li>
@endsection

@push('css-page')
<style>
    .schedule-calendar {
        overflow-x: auto;
    }
    .schedule-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }
    .schedule-table th, .schedule-table td {
        border: 1px solid #e0e0e0;
        padding: 8px;
        text-align: center;
        vertical-align: middle;
    }
    .schedule-table th {
        background: #f8f9fa;
        font-weight: 600;
        font-size: 13px;
    }
    .schedule-table th.today {
        background: #fff3cd;
    }
    .worker-cell {
        text-align: left;
        min-width: 180px;
        white-space: nowrap;
    }
    .worker-name {
        font-weight: 500;
    }
    .worker-workplace {
        font-size: 11px;
        color: #888;
    }
    .shift-cell {
        min-width: 80px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .shift-cell:hover {
        background: #f0f0f0;
    }
    .shift-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 500;
        color: #fff;
    }
    .shift-time {
        font-size: 10px;
        color: #666;
        margin-top: 2px;
    }
    .week-nav {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .week-nav .btn {
        padding: 6px 12px;
    }
    .week-title {
        font-size: 16px;
        font-weight: 600;
        min-width: 200px;
        text-align: center;
    }
    .filter-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
    }
    .action-buttons {
        display: flex;
        gap: 8px;
    }
    .no-workers {
        text-align: center;
        padding: 60px 20px;
        color: #888;
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="filter-bar">
                            <select id="workPlaceFilter" class="form-select" style="min-width: 180px;">
                                <option value="">{{ __('All Work Places') }}</option>
                                @foreach($workPlaces as $wp)
                                    <option value="{{ $wp->id }}" {{ $selectedWorkPlaceId == $wp->id ? 'selected' : '' }}>
                                        {{ $wp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="week-nav">
                            <a href="{{ route('attendance.index', ['start_date' => $weekStart->copy()->subWeek()->format('Y-m-d'), 'work_place_id' => $selectedWorkPlaceId]) }}" 
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-chevron-left"></i>
                            </a>
                            <span class="week-title">
                                {{ $weekStart->format('d') }} {{ __($weekStart->format('M')) }} - {{ $weekEnd->format('d') }} {{ __($weekEnd->format('M')) }} {{ $weekEnd->format('Y') }}
                            </span>
                            <a href="{{ route('attendance.index', ['start_date' => $weekStart->copy()->addWeek()->format('Y-m-d'), 'work_place_id' => $selectedWorkPlaceId]) }}" 
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-chevron-right"></i>
                            </a>
                        </div>
                        
                        <div class="action-buttons">
                            <a href="{{ route('attendance.daily') }}" class="btn btn-info btn-sm">
                                <i class="ti ti-calendar-event"></i> {{ __('Today') }}
                            </a>
                            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkAssignModal">
                                <i class="ti ti-users-plus"></i> {{ __('Bulk Assign') }}
                            </button>
                            <a href="{{ route('attendance.shifts.index') }}" class="btn btn-secondary btn-sm">
                                <i class="ti ti-settings"></i> {{ __('Shifts') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(count($scheduleData) > 0)
                        <div class="schedule-calendar">
                            @include('attendance.partials.calendar')
                        </div>
                    @else
                        <div class="no-workers">
                            <i class="ti ti-users-minus" style="font-size: 48px; opacity: 0.3;"></i>
                            <h5 class="mt-3">{{ __('No Workers Found') }}</h5>
                            <p class="text-muted">{{ __('No workers match the selected filters.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('attendance.partials.bulk_assign')
@endsection

@push('script-page')
<script>
document.getElementById('workPlaceFilter').addEventListener('change', function() {
    var url = new URL(window.location.href);
    if (this.value) {
        url.searchParams.set('work_place_id', this.value);
    } else {
        url.searchParams.delete('work_place_id');
    }
    window.location.href = url.toString();
});
</script>
@endpush
