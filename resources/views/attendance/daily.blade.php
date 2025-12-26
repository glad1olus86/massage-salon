@extends('layouts.admin')

@section('page-title')
    {{ __('Daily Attendance') }} - {{ $carbonDate->format('d') }} {{ __($carbonDate->format('F')) }} {{ $carbonDate->format('Y') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">{{ __('Attendance') }}</a></li>
    <li class="breadcrumb-item">{{ __('Daily') }}</li>
@endsection

@push('css-page')
<style>
    .date-nav {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .date-title {
        font-size: 16px;
        font-weight: 600;
        min-width: 180px;
        text-align: center;
    }
    .stats-row {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .stat-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 12px 20px;
        text-align: center;
        min-width: 100px;
    }
    .stat-card.present { background: #d4edda; }
    .stat-card.late { background: #fff3cd; }
    .stat-card.absent { background: #f8d7da; }
    .stat-card.sick { background: #cce5ff; }
    .stat-card.vacation { background: #e2e3e5; }
    .stat-number {
        font-size: 24px;
        font-weight: 700;
    }
    .stat-label {
        font-size: 12px;
        color: #666;
    }
    .attendance-table th, .attendance-table td {
        vertical-align: middle;
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-present { background: #d4edda; color: #155724; }
    .status-late { background: #fff3cd; color: #856404; }
    .status-absent { background: #f8d7da; color: #721c24; }
    .status-sick { background: #cce5ff; color: #004085; }
    .status-vacation { background: #e2e3e5; color: #383d41; }
    .time-display {
        font-family: monospace;
        font-size: 14px;
    }
    .hours-display {
        font-weight: 600;
        color: #28a745;
    }
    .filter-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        flex-wrap: wrap;
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
                            <select id="shiftFilter" class="form-select" style="min-width: 150px;">
                                <option value="">{{ __('All Shifts') }}</option>
                                @foreach($shiftTemplates as $template)
                                    <option value="{{ $template->id }}" {{ $selectedShiftId == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="date-nav">
                            <a href="{{ route('attendance.daily', ['date' => $carbonDate->copy()->subDay()->format('Y-m-d')]) }}" 
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-chevron-left"></i>
                            </a>
                            <input type="date" id="datePicker" class="form-control" value="{{ $date }}" style="width: auto;">
                            <a href="{{ route('attendance.daily', ['date' => $carbonDate->copy()->addDay()->format('Y-m-d')]) }}" 
                               class="btn btn-outline-secondary btn-sm">
                                <i class="ti ti-chevron-right"></i>
                            </a>
                            <a href="{{ route('attendance.daily') }}" class="btn btn-outline-primary btn-sm">
                                {{ __('Today') }}
                            </a>
                        </div>
                        
                        @can('attendance_mark')
                        <form action="{{ route('attendance.mark-bulk') }}" method="POST" id="markAllForm">
                            @csrf
                            <input type="hidden" name="date" value="{{ $date }}">
                            <input type="hidden" name="worker_ids" id="allWorkerIds" value="">
                            <button type="submit" class="btn btn-success btn-sm" {{ $scheduledWorkers->count() == 0 ? 'disabled' : '' }}>
                                <i class="ti ti-checks"></i> {{ __('Mark All Present') }}
                            </button>
                        </form>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-number">{{ $stats['total'] }}</div>
                            <div class="stat-label">{{ __('Scheduled') }}</div>
                        </div>
                        <div class="stat-card present">
                            <div class="stat-number">{{ $stats['present'] }}</div>
                            <div class="stat-label">{{ __('Present') }}</div>
                        </div>
                        <div class="stat-card late">
                            <div class="stat-number">{{ $stats['late'] }}</div>
                            <div class="stat-label">{{ __('Late') }}</div>
                        </div>
                        <div class="stat-card absent">
                            <div class="stat-number">{{ $stats['absent'] }}</div>
                            <div class="stat-label">{{ __('Absent') }}</div>
                        </div>
                        <div class="stat-card sick">
                            <div class="stat-number">{{ $stats['sick'] }}</div>
                            <div class="stat-label">{{ __('Sick') }}</div>
                        </div>
                        <div class="stat-card vacation">
                            <div class="stat-number">{{ $stats['vacation'] }}</div>
                            <div class="stat-label">{{ __('Vacation') }}</div>
                        </div>
                    </div>

                    @if($scheduledWorkers->count() > 0)
                        <div class="table-responsive">
                            <table class="table attendance-table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Worker') }}</th>
                                        <th>{{ __('Shift') }}</th>
                                        <th>{{ __('Planned') }}</th>
                                        <th>{{ __('Actual') }}</th>
                                        <th>{{ __('Hours') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th width="120">{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($scheduledWorkers as $item)
                                        <tr>
                                            <td>
                                                <strong>{{ $item['worker']->first_name }} {{ $item['worker']->last_name }}</strong>
                                                @if($item['worker']->currentWorkAssignment?->workPlace)
                                                    <br><small class="text-muted">{{ $item['worker']->currentWorkAssignment->workPlace->name }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge" style="background-color: {{ $item['shift_template']->color }}; color: #fff;">
                                                    {{ $item['shift_template']->name }}
                                                </span>
                                            </td>
                                            <td class="time-display">
                                                {{ \Carbon\Carbon::parse($item['shift_template']->start_time)->format('H:i') }} - 
                                                {{ \Carbon\Carbon::parse($item['shift_template']->end_time)->format('H:i') }}
                                            </td>
                                            @php
                                                $shiftStart = \Carbon\Carbon::parse($item['shift_template']->start_time);
                                                $shiftEnd = \Carbon\Carbon::parse($item['shift_template']->end_time);
                                                // Если конец раньше начала - ночная смена
                                                if ($shiftEnd->lt($shiftStart)) {
                                                    $shiftEnd->addDay();
                                                }
                                                $totalMinutes = $shiftStart->diffInMinutes($shiftEnd);
                                                $breakMinutes = $item['shift_template']->break_minutes ?? 0;
                                                $plannedHours = round(($totalMinutes - $breakMinutes) / 60, 1);
                                            @endphp
                                            <td class="time-display">
                                                @if($item['attendance'])
                                                    @if(in_array($item['attendance']->status, ['sick', 'vacation', 'absent']))
                                                        <span class="text-muted">— - —</span>
                                                    @else
                                                        {{ $item['attendance']->check_in ? \Carbon\Carbon::parse($item['attendance']->check_in)->format('H:i') : $shiftStart->format('H:i') }}
                                                        -
                                                        {{ $item['attendance']->check_out ? \Carbon\Carbon::parse($item['attendance']->check_out)->format('H:i') : $shiftEnd->format('H:i') }}
                                                    @endif
                                                @else
                                                    {{ $shiftStart->format('H:i') }} - {{ $shiftEnd->format('H:i') }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($item['attendance'])
                                                    @if(in_array($item['attendance']->status, ['sick', 'vacation', 'absent']))
                                                        <span class="text-muted">0{{ __('h') }}</span>
                                                    @elseif($item['attendance']->worked_hours)
                                                        <span class="hours-display">{{ number_format($item['attendance']->worked_hours, 1) }}{{ __('h') }}</span>
                                                    @else
                                                        <span class="hours-display">{{ number_format($plannedHours, 1) }}{{ __('h') }}</span>
                                                    @endif
                                                @else
                                                    <span class="hours-display">{{ number_format($plannedHours, 1) }}{{ __('h') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($item['attendance'])
                                                    <span class="status-badge status-{{ $item['attendance']->status }}">
                                                        {{ __($item['attendance']->status) }}
                                                    </span>
                                                @else
                                                    <span class="status-badge status-present">{{ __('present') }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @can('attendance_mark')
                                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#markModal"
                                                            data-worker-id="{{ $item['worker']->id }}"
                                                            data-worker-name="{{ $item['worker']->first_name }} {{ $item['worker']->last_name }}"
                                                            data-shift-start="{{ \Carbon\Carbon::parse($item['shift_template']->start_time)->format('H:i') }}"
                                                            data-shift-end="{{ \Carbon\Carbon::parse($item['shift_template']->end_time)->format('H:i') }}"
                                                            data-check-in="{{ $item['attendance']?->check_in ? \Carbon\Carbon::parse($item['attendance']->check_in)->format('H:i') : '' }}"
                                                            data-check-out="{{ $item['attendance']?->check_out ? \Carbon\Carbon::parse($item['attendance']->check_out)->format('H:i') : '' }}"
                                                            data-status="{{ $item['attendance']?->status ?? 'present' }}"
                                                            data-notes="{{ $item['attendance']?->notes ?? '' }}">
                                                        <i class="ti ti-edit"></i> {{ __('Mark') }}
                                                    </button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="ti ti-calendar-off" style="font-size: 48px; opacity: 0.3;"></i>
                            <h5 class="mt-3">{{ __('No Scheduled Workers') }}</h5>
                            <p class="text-muted">{{ __('No workers are scheduled for this date.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('attendance.partials.mark_form')
@endsection

@push('script-page')
<script>
document.getElementById('datePicker').addEventListener('change', function() {
    window.location.href = '{{ route("attendance.daily") }}/' + this.value;
});

document.getElementById('workPlaceFilter').addEventListener('change', function() {
    var url = new URL(window.location.href);
    if (this.value) {
        url.searchParams.set('work_place_id', this.value);
    } else {
        url.searchParams.delete('work_place_id');
    }
    window.location.href = url.toString();
});

document.getElementById('shiftFilter').addEventListener('change', function() {
    var url = new URL(window.location.href);
    if (this.value) {
        url.searchParams.set('shift_template_id', this.value);
    } else {
        url.searchParams.delete('shift_template_id');
    }
    window.location.href = url.toString();
});

// Collect all worker IDs for bulk mark
var workerIds = [];
@foreach($scheduledWorkers as $item)
workerIds.push({{ $item['worker']->id }});
@endforeach
document.getElementById('allWorkerIds').value = JSON.stringify(workerIds);
</script>
@endpush
