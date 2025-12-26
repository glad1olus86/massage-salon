@extends('layouts.admin')

@section('page-title')
    {{ __('System Audit') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Audit') }}</li>
@endsection

@push('css-page')
<style>
    /* Force visibility of all parent containers */
    #pills-tabContent {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        overflow: visible !important;
        height: auto !important;
        min-height: 600px !important;
    }
    #pills-calendar {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        min-height: 600px !important;
        height: auto !important;
        overflow: visible !important;
        background: #fff;
        padding: 20px;
        position: relative !important;
    }
    #pills-calendar.hidden-tab {
        display: none !important;
    }
    #pills-list.hidden-tab {
        display: none !important;
    }
    .calendar-container {
        display: block !important;
        visibility: visible !important;
        min-height: 500px !important;
        height: auto !important;
        overflow: visible !important;
    }
    .calendar-grid {
        display: grid !important;
        visibility: visible !important;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
        min-height: 400px !important;
        height: auto !important;
    }
    .calendar-day-header {
        text-align: center;
        font-weight: bold;
        padding: 10px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }
    .calendar-day {
        min-height: 120px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 10px;
        cursor: pointer;
        transition: all 0.2s;
        background-color: #fff;
        position: relative;
    }
    .calendar-day:hover {
        border-color: #0d6efd;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .calendar-day.empty {
        background-color: transparent;
        border: none;
        cursor: default;
    }
    .calendar-day.today {
        border: 2px solid #0d6efd;
    }
    .day-number {
        font-weight: bold;
        margin-bottom: 5px;
        display: block;
    }
    .event-dots {
        display: flex;
        flex-wrap: wrap;
        gap: 3px;
    }
    .event-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }
    .more-events {
        font-size: 10px;
        color: #6c757d;
        margin-top: 2px;
        display: block;
    }
</style>
@endpush

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    {{-- Filters --}}
                    <form action="{{ route('audit.index') }}" method="GET" class="mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="start_date" class="form-label">{{ __('Date From') }}</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date"
                                        value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="end_date" class="form-label">{{ __('Date To') }}</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date"
                                        value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="user_id" class="form-label">{{ __('User') }}</label>
                                    <select name="user_id" id="user_id" class="form-control select2">
                                        <option value="">{{ __('All Users') }}</option>
                                        @foreach ($users as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ request('user_id') == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="worker_id" class="form-label">{{ __('Worker') }}</label>
                                    <select name="worker_id" id="worker_id" class="form-control select2">
                                        <option value="">{{ __('All Workers') }}</option>
                                        @foreach ($workers as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ request('worker_id') == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="event_type" class="form-label">{{ __('Event Type') }}</label>
                                    <select name="event_type" id="event_type" class="form-control select2">
                                        <option value="">{{ __('All Events') }}</option>
                                        @foreach ($eventTypes as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ request('event_type') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        {{-- Buttons in separate row aligned right --}}
                        <div class="row mt-2">
                            <div class="col-md-12 d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary" style="min-width: 140px;">
                                    <i class="ti ti-filter"></i> {{ __('Apply') }}
                                </button>
                                <a href="{{ route('audit.index') }}" class="btn btn-secondary" style="min-width: 140px;">
                                    <i class="ti ti-refresh"></i> {{ __('Reset') }}
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- View mode switcher aligned right --}}
                    <div class="row mb-5">
                        <div class="col-md-12 d-flex justify-content-end">
                            <ul class="nav nav-pills gap-2" id="pills-tab">
                                <li class="nav-item">
                                    <button class="nav-link {{ request('tab') == 'list' ? 'active' : '' }}"
                                        style="min-width: 140px;" id="pills-list-tab" type="button">
                                        <i class="ti ti-list me-1"></i>{{ __('List') }}
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link {{ request('tab') != 'list' ? 'active' : '' }}"
                                        style="min-width: 140px;" id="pills-calendar-tab" type="button">
                                        <i class="ti ti-calendar me-1"></i>{{ __('Calendar') }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div id="pills-tabContent">
                        {{-- List mode --}}
                        <div id="pills-list" class="{{ request('tab') != 'list' ? 'hidden-tab' : '' }}">
                            @include('audit_log.partials.list_view_table')
                        </div>

                        {{-- Calendar mode --}}
                        <div id="pills-calendar" class="{{ request('tab') == 'list' ? 'hidden-tab' : '' }}">
                            @include('audit_log.partials.calendar_view')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Modals outside of card to prevent HTML structure issues --}}
    @include('audit_log.partials.list_view_modals')
@endsection

@push('script-page')
<script src="{{ asset('js/audit-calendar.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('[AuditPage] DOMContentLoaded');
    
    const listTab = document.getElementById('pills-list-tab');
    const calendarTab = document.getElementById('pills-calendar-tab');
    const listPane = document.getElementById('pills-list');
    const calendarPane = document.getElementById('pills-calendar');
    
    // Function to switch to list view
    function showList() {
        listTab.classList.add('active');
        calendarTab.classList.remove('active');
        listPane.classList.remove('hidden-tab');
        calendarPane.classList.add('hidden-tab');
        
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'list');
        window.history.replaceState({}, '', url);
    }
    
    // Function to switch to calendar view
    function showCalendar() {
        console.log('[AuditPage] showCalendar called');
        calendarTab.classList.add('active');
        listTab.classList.remove('active');
        calendarPane.classList.remove('hidden-tab');
        listPane.classList.add('hidden-tab');
        
        const url = new URL(window.location.href);
        url.searchParams.delete('tab');
        window.history.replaceState({}, '', url);
        
        // Initialize and load calendar data
        initAndLoadCalendar();
    }
    
    function initAndLoadCalendar() {
        console.log('[AuditPage] initAndLoadCalendar called');
        if (window.AuditCalendar) {
            // Reset initialized flag to force re-init if needed
            if (!window.AuditCalendar.initialized) {
                window.AuditCalendar.init();
            }
            // Always try to load
            window.AuditCalendar.load();
        } else {
            console.log('[AuditPage] AuditCalendar not available!');
        }
    }
    
    // Click handlers
    if (listTab) {
        listTab.addEventListener('click', function(e) {
            e.preventDefault();
            showList();
        });
    }
    
    if (calendarTab) {
        calendarTab.addEventListener('click', function(e) {
            e.preventDefault();
            showCalendar();
        });
    }
    
    // On page load - calendar is default, load it unless list is selected
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    
    console.log('[AuditPage] Current tab param:', tab);
    
    if (tab !== 'list') {
        // Calendar is default - load calendar data
        console.log('[AuditPage] Loading calendar on page load');
        // Small delay to ensure DOM is fully ready
        setTimeout(function() {
            initAndLoadCalendar();
        }, 100);
    }
});
</script>
@endpush
