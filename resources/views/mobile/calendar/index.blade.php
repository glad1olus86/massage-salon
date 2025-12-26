@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <button type="button" class="mobile-header-btn" onclick="openSidebar()">
                    <img src="{{ asset('fromfigma/menu_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><line x1=3 y1=6 x2=21 y2=6></line><line x1=3 y1=12 x2=21 y2=12></line><line x1=3 y1=18 x2=21 y2=18></line></svg>'">
                </button>
                <a href="{{ route('mobile.notifications.index') }}" class="mobile-header-btn">
                    <img src="{{ asset('fromfigma/bell_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\'></path><path d=\'M13.73 21a2 2 0 0 1-3.46 0\'></path></svg>'">
                </a>
            </div>
            <div class="mobile-header-right">
                <div class="mobile-logo">
                    <img src="{{ asset('fromfigma/jobsi_mobile.png') }}" alt="JOBSI">
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-content">
        {{-- Page Title --}}
        <div class="mobile-section-title">
            <div class="mobile-section-title-left">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>{{ __('Audit Calendar') }}</span>
            </div>
            <a href="{{ route('mobile.audit.index') }}" class="mobile-add-btn">
                <i class="ti ti-list" style="font-size: 20px; color: #FF0049;"></i>
            </a>
        </div>

        {{-- Month Navigation --}}
        <div class="mobile-card mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="prevMonth">
                    <i class="ti ti-chevron-left"></i>
                </button>
                <h5 class="mb-0 fw-bold" id="currentMonthYear"></h5>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="nextMonth">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>
        </div>

        {{-- Calendar Grid --}}
        <div class="mobile-card mb-3">
            {{-- Day Headers --}}
            <div class="calendar-header">
                <div class="calendar-day-name">{{ __('Mon') }}</div>
                <div class="calendar-day-name">{{ __('Tue') }}</div>
                <div class="calendar-day-name">{{ __('Wed') }}</div>
                <div class="calendar-day-name">{{ __('Thu') }}</div>
                <div class="calendar-day-name">{{ __('Fri') }}</div>
                <div class="calendar-day-name weekend">{{ __('Sat') }}</div>
                <div class="calendar-day-name weekend">{{ __('Sun') }}</div>
            </div>
            {{-- Calendar Days --}}
            <div class="calendar-grid" id="calendarGrid">
                {{-- Days will be rendered by JS --}}
            </div>
        </div>

        {{-- Legend --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>{{ __('Event Types') }}</h6>
            <div class="d-flex flex-wrap gap-2">
                <span class="legend-item"><span class="legend-dot" style="background: #28a745;"></span>{{ __('Created') }}</span>
                <span class="legend-item"><span class="legend-dot" style="background: #17a2b8;"></span>{{ __('Updated') }}</span>
                <span class="legend-item"><span class="legend-dot" style="background: #007bff;"></span>{{ __('Check-in') }}</span>
                <span class="legend-item"><span class="legend-dot" style="background: #fd7e14;"></span>{{ __('Check-out') }}</span>
                <span class="legend-item"><span class="legend-dot" style="background: #6f42c1;"></span>{{ __('Employment') }}</span>
                <span class="legend-item"><span class="legend-dot" style="background: #dc3545;"></span>{{ __('Dismissal') }}</span>
            </div>
        </div>

        {{-- Today's Events --}}
        <div class="mobile-card mb-3" id="todayEventsCard">
            <h6 class="mb-3"><i class="ti ti-calendar-event me-2 text-primary"></i>{{ __("Today's Events") }}</h6>
            <div id="todayEventsList">
                <div class="text-center py-3">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Day Details Modal --}}
    <div class="modal fade" id="dayDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="dayDetailsTitle">{{ __('Events for the day') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="dayDetailsBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mobile-btn-primary {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
            color: #fff !important;
        }
        .mobile-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .text-primary {
            color: #FF0049 !important;
        }
        .mobile-add-btn {
            border: none !important;
            background: transparent;
        }
        
        /* Calendar Styles */
        .calendar-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            margin-bottom: 8px;
        }
        .calendar-day-name {
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            color: #666;
            padding: 8px 0;
        }
        .calendar-day-name.weekend {
            color: #FF0049;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }
        .calendar-day {
            aspect-ratio: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            position: relative;
            min-height: 44px;
            transition: all 0.2s;
        }
        .calendar-day:active {
            transform: scale(0.95);
        }
        .calendar-day.other-month {
            opacity: 0.3;
        }
        .calendar-day.today {
            background: linear-gradient(135deg, #FF0049 0%, #ff4d7a 100%);
            color: #fff;
        }
        .calendar-day.today .day-number {
            color: #fff;
        }
        .calendar-day.has-events {
            background: #f0f9ff;
            border: 1px solid #e0f0ff;
        }
        .calendar-day.has-events.today {
            background: linear-gradient(135deg, #FF0049 0%, #ff4d7a 100%);
        }
        .day-number {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }
        .event-dots {
            display: flex;
            gap: 2px;
            margin-top: 2px;
        }
        .event-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
        }
        .event-count {
            font-size: 9px;
            color: #666;
        }
        .calendar-day.today .event-count {
            color: rgba(255,255,255,0.8);
        }
        
        /* Legend */
        .legend-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
            color: #666;
        }
        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        /* Event List */
        .event-item {
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .event-item:last-child {
            border-bottom: none;
        }
        .event-type-badge {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        @media (max-width: 576px) {
            .modal-fullscreen-sm-down .modal-content {
                height: 100%;
                border: 0;
                border-radius: 0;
            }
            .modal-fullscreen-sm-down .modal-dialog {
                width: 100%;
                max-width: none;
                height: 100%;
                margin: 0;
            }
        }
    </style>
@endsection

@push('scripts')
<script>
var currentYear = new Date().getFullYear();
var currentMonth = new Date().getMonth();
var monthNames = [
    '{{ __("January") }}', '{{ __("February") }}', '{{ __("March") }}', '{{ __("April") }}',
    '{{ __("May") }}', '{{ __("June") }}', '{{ __("July") }}', '{{ __("August") }}',
    '{{ __("September") }}', '{{ __("October") }}', '{{ __("November") }}', '{{ __("December") }}'
];
var eventsData = {};

document.addEventListener('DOMContentLoaded', function() {
    renderCalendar(currentYear, currentMonth);
    loadTodayEvents();
    
    document.getElementById('prevMonth').addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar(currentYear, currentMonth);
    });
    
    document.getElementById('nextMonth').addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar(currentYear, currentMonth);
    });
});

function renderCalendar(year, month) {
    document.getElementById('currentMonthYear').textContent = monthNames[month] + ' ' + year;
    
    var grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';
    
    var firstDay = new Date(year, month, 1);
    var lastDay = new Date(year, month + 1, 0);
    var prevLastDay = new Date(year, month, 0);
    
    var startDayOfWeek = firstDay.getDay();
    startDayOfWeek = startDayOfWeek === 0 ? 6 : startDayOfWeek - 1;
    
    // Previous month days
    for (var i = startDayOfWeek - 1; i >= 0; i--) {
        var day = prevLastDay.getDate() - i;
        grid.appendChild(createDayElement(day, true));
    }
    
    // Current month days
    var today = new Date();
    for (var day = 1; day <= lastDay.getDate(); day++) {
        var isToday = (day === today.getDate() && month === today.getMonth() && year === today.getFullYear());
        grid.appendChild(createDayElement(day, false, isToday));
    }
    
    // Next month days
    var totalCells = grid.children.length;
    var remainingCells = (Math.ceil(totalCells / 7) * 7) - totalCells;
    for (var day = 1; day <= remainingCells; day++) {
        grid.appendChild(createDayElement(day, true));
    }
    
    // Load events for this month
    loadMonthEvents(year, month);
}

function createDayElement(day, isOtherMonth, isToday) {
    var div = document.createElement('div');
    div.className = 'calendar-day';
    if (isOtherMonth) div.classList.add('other-month');
    if (isToday) div.classList.add('today');
    
    var dayNumber = document.createElement('div');
    dayNumber.className = 'day-number';
    dayNumber.textContent = day;
    div.appendChild(dayNumber);
    
    var eventDots = document.createElement('div');
    eventDots.className = 'event-dots';
    div.appendChild(eventDots);
    
    if (!isOtherMonth) {
        div.dataset.day = day;
        div.addEventListener('click', function() {
            showDayDetails(currentYear, currentMonth, day);
        });
    }
    
    return div;
}

function loadMonthEvents(year, month) {
    fetch('{{ url("/audit/calendar") }}/' + year + '/' + (month + 1))
        .then(response => response.json())
        .then(data => {
            eventsData = data.days || {};
            Object.keys(eventsData).forEach(function(day) {
                var dayData = eventsData[day];
                var dayElement = document.querySelector('.calendar-day[data-day="' + day + '"]');
                if (dayElement) {
                    dayElement.classList.add('has-events');
                    var dotsContainer = dayElement.querySelector('.event-dots');
                    
                    var eventTypes = Object.keys(dayData.events);
                    var maxDots = 3;
                    for (var i = 0; i < Math.min(eventTypes.length, maxDots); i++) {
                        var dot = document.createElement('span');
                        dot.className = 'event-dot';
                        dot.style.backgroundColor = getEventColor(eventTypes[i]);
                        dotsContainer.appendChild(dot);
                    }
                    
                    if (dayData.total > maxDots) {
                        var count = document.createElement('span');
                        count.className = 'event-count';
                        count.textContent = '+' + (dayData.total - maxDots);
                        dotsContainer.appendChild(count);
                    }
                }
            });
        })
        .catch(function(error) {
            console.error('Error loading events:', error);
        });
}

function loadTodayEvents() {
    var today = new Date();
    var dateStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');
    
    fetch('{{ url("/audit/day") }}/' + dateStr)
        .then(response => response.text())
        .then(html => {
            document.getElementById('todayEventsList').innerHTML = html || '<p class="text-muted text-center mb-0">{{ __("No events today") }}</p>';
        })
        .catch(function(error) {
            document.getElementById('todayEventsList').innerHTML = '<p class="text-muted text-center mb-0">{{ __("No events today") }}</p>';
        });
}

function showDayDetails(year, month, day) {
    var modal = new bootstrap.Modal(document.getElementById('dayDetailsModal'));
    var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
    
    document.getElementById('dayDetailsTitle').textContent = '{{ __("Events for") }} ' + day + ' ' + monthNames[month] + ' ' + year;
    document.getElementById('dayDetailsBody').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div></div>';
    
    modal.show();
    
    fetch('{{ url("/audit/day") }}/' + dateStr)
        .then(response => response.text())
        .then(html => {
            document.getElementById('dayDetailsBody').innerHTML = html || '<p class="text-muted text-center">{{ __("No events for this day") }}</p>';
        })
        .catch(function(error) {
            document.getElementById('dayDetailsBody').innerHTML = '<div class="alert alert-danger">{{ __("Error loading data") }}</div>';
        });
}

function getEventColor(eventType) {
    var colors = {
        'worker.created': '#28a745',
        'worker.updated': '#17a2b8',
        'worker.deleted': '#6c757d',
        'worker.checked_in': '#007bff',
        'worker.checked_out': '#fd7e14',
        'worker.hired': '#6f42c1',
        'worker.dismissed': '#dc3545',
        'room.created': '#20c997',
        'room.updated': '#17a2b8',
        'room.deleted': '#6c757d',
        'work_place.created': '#20c997',
        'work_place.updated': '#17a2b8',
        'work_place.deleted': '#6c757d',
        'hotel.created': '#28a745',
        'hotel.updated': '#17a2b8',
        'hotel.deleted': '#6c757d'
    };
    return colors[eventType] || '#6c757d';
}
</script>
@endpush
