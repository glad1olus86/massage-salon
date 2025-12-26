@extends('layouts.admin')

@section('page-title')
    {{ __('Shift Templates') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">{{ __('Attendance') }}</a></li>
    <li class="breadcrumb-item">{{ __('Shift Templates') }}</li>
@endsection

@section('action-btn')
@endsection

@push('css-page')
<style>
    .shift-card {
        border-radius: 12px;
        border: 1px solid #e0e0e0;
        padding: 16px;
        margin-bottom: 16px;
        transition: all 0.2s;
    }
    .shift-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .shift-color-badge {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .shift-time {
        font-size: 18px;
        font-weight: 600;
        color: #333;
    }
    .shift-duration {
        font-size: 13px;
        color: #888;
    }
    .shift-pay {
        font-size: 13px;
        color: #666;
        margin-top: 8px;
    }
    .workplace-section {
        margin-bottom: 32px;
    }
    .workplace-header {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #f0f0f0;
    }
    .no-shifts {
        text-align: center;
        padding: 40px;
        color: #888;
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="my-3 d-flex justify-content-between align-items-center">
                <div>
                    <select id="workPlaceFilter" class="form-select" style="min-width: 200px;">
                        <option value="">{{ __('All Work Places') }}</option>
                        @foreach($workPlaces as $wp)
                            <option value="{{ $wp->id }}" {{ $selectedWorkPlaceId == $wp->id ? 'selected' : '' }}>
                                {{ $wp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @can('attendance_manage_shifts')
                    <a href="{{ route('attendance.shifts.create', ['work_place_id' => $selectedWorkPlaceId]) }}" 
                       class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i> {{ __('New Shift Template') }}
                    </a>
                @endcan
            </div>

            @forelse($workPlaces as $workPlace)
                @if(!$selectedWorkPlaceId || $selectedWorkPlaceId == $workPlace->id)
                    @if($workPlace->shiftTemplates->count() > 0)
                        <div class="workplace-section" data-workplace="{{ $workPlace->id }}">
                            <div class="workplace-header">
                                <i class="ti ti-building"></i> {{ $workPlace->name }}
                            </div>
                            <div class="row">
                                @foreach($workPlace->shiftTemplates as $template)
                                    <div class="col-md-4 col-lg-3">
                                        <div class="shift-card">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <span class="shift-color-badge" style="background-color: {{ $template->color }}"></span>
                                                    <strong>{{ $template->name }}</strong>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-link p-0" data-bs-toggle="dropdown">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        @can('attendance_manage_shifts')
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('attendance.shifts.edit', $template) }}">
                                                                    <i class="ti ti-pencil me-2"></i>{{ __('Edit') }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <form action="{{ route('attendance.shifts.destroy', $template) }}" method="POST" 
                                                                      onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger">
                                                                        <i class="ti ti-trash me-2"></i>{{ __('Delete') }}
                                                                    </button>
                                                                </form>
                                                            </li>
                                                        @endcan
                                                    </ul>
                                                </div>
                                            </div>
                                            <div class="shift-time">
                                                {{ \Carbon\Carbon::parse($template->start_time)->format('H:i') }} - 
                                                {{ \Carbon\Carbon::parse($template->end_time)->format('H:i') }}
                                            </div>
                                            <div class="shift-duration">
                                                {{ $template->getDurationInHours() }} {{ __('hours') }}
                                                @if($template->break_minutes > 0)
                                                    ({{ __('break') }}: {{ $template->break_minutes }} {{ __('min') }})
                                                @endif
                                            </div>
                                            <div class="shift-pay">
                                                @if($template->pay_rate)
                                                    <i class="ti ti-currency-dollar"></i>
                                                    {{ number_format($template->pay_rate, 0) }}
                                                    {{ $template->pay_type == 'hourly' ? __('/hour') : __('/shift') }}
                                                    @if($template->night_bonus_enabled)
                                                        <span class="badge bg-info ms-1">+{{ $template->night_bonus_percent }}% {{ __('night') }}</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted">{{ __('No pay rate set') }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
            @empty
                <div class="no-shifts">
                    <i class="ti ti-clock-off" style="font-size: 48px; opacity: 0.3;"></i>
                    <h5 class="mt-3">{{ __('No Shift Templates') }}</h5>
                    <p class="text-muted">{{ __('Create your first shift template to get started.') }}</p>
                </div>
            @endforelse

            @if($workPlaces->sum(fn($wp) => $wp->shiftTemplates->count()) == 0)
                <div class="no-shifts">
                    <i class="ti ti-clock-off" style="font-size: 48px; opacity: 0.3;"></i>
                    <h5 class="mt-3">{{ __('No Shift Templates') }}</h5>
                    <p class="text-muted">{{ __('Create your first shift template to get started.') }}</p>
                </div>
            @endif
        </div>
    </div>
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
