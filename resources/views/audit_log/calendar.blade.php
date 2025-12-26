@extends('layouts.admin')

@section('page-title')
    {{ __('Audit Calendar') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('audit.index') }}">{{ __('Audit') }}</a></li>
    <li class="breadcrumb-item">{{ __('Calendar') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    {{-- Calendar Header with Navigation --}}
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <button class="btn btn-lg btn-outline-primary" id="prev-month">
                            <i class="ti ti-chevron-left fs-3"></i>
                        </button>

                        <div class="calendar-header-title" style="cursor: pointer;" id="month-year-selector">
                            <h3 class="mb-0 text-center" id="calendar-month-year"></h3>
                        </div>

                        <button class="btn btn-lg btn-outline-primary" id="next-month">
                            <i class="ti ti-chevron-right fs-3"></i>
                        </button>
                    </div>

                    {{-- Month/Year Picker Modal --}}
                    <div class="collapse mb-3" id="monthYearPicker">
                        <div class="card card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Year') }}</label>
                                    <select class="form-select" id="year-select">
                                        @for ($y = date('Y') - 5; $y <= date('Y') + 2; $y++)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">{{ __('Month') }}</label>
                                    <select class="form-select" id="month-select">
                                        <option value="0">{{ __('January') }}</option>
                                        <option value="1">{{ __('February') }}</option>
                                        <option value="2">{{ __('March') }}</option>
                                        <option value="3">{{ __('April') }}</option>
                                        <option value="4">{{ __('May') }}</option>
                                        <option value="5">{{ __('June') }}</option>
                                        <option value="6">{{ __('July') }}</option>
                                        <option value="7">{{ __('August') }}</option>
                                        <option value="8">{{ __('September') }}</option>
                                        <option value="9">{{ __('October') }}</option>
                                        <option value="10">{{ __('November') }}</option>
                                        <option value="11">{{ __('December') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Calendar Grid --}}
                    <div class="calendar-grid-wrapper">
                        <div class="calendar-grid" id="calendar-grid">
                            {{-- Days will be rendered here via JS --}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal for Day Details --}}
    <div class="modal fade" id="day-details-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="day-details-title">{{ __('Events for the day') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="day-details-body">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 12px;
            margin-top: 20px;
        }

        .calendar-day-header {
            text-align: center;
            font-weight: 600;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            font-size: 14px;
            text-transform: uppercase;
        }

        .calendar-day {
            min-height: 100px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .calendar-day:hover:not(.empty):not(.other-month) {
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
            transform: translateY(-2px);
        }

        .calendar-day.empty {
            background-color: transparent;
            border: none;
            cursor: default;
        }

        .calendar-day.other-month {
            background-color: #f8f9fa;
            opacity: 0.5;
            cursor: default;
        }

        .calendar-day.today {
            border: 3px solid #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .calendar-day.has-events {
            border-color: #28a745;
        }

        .day-number {
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 8px;
            color: #495057;
        }

        .calendar-day.today .day-number {
            color: #667eea;
        }

        .event-dots {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            margin-top: auto;
            padding-top: 8px;
        }

        .event-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .event-count {
            font-size: 11px;
            color: #6c757d;
            font-weight: 600;
            margin-top: 4px;
        }

        .calendar-header-title h3 {
            font-weight: 600;
            min-width: 300px;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .calendar-header-title:hover h3 {
            background-color: #f8f9fa;
        }

        @media (max-width: 768px) {
            .calendar-grid {
                gap: 6px;
            }

            .calendar-day {
                min-height: 80px;
                padding: 8px;
            }

            .day-number {
                font-size: 14px;
            }

            .event-dot {
                width: 8px;
                height: 8px;
            }
        }
    </style>

    <script>
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize
            renderCalendar(currentYear, currentMonth);

            // Navigation buttons
            document.getElementById('prev-month').addEventListener('click', () => {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
                renderCalendar(currentYear, currentMonth);
            });

            document.getElementById('next-month').addEventListener('click', () => {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
                renderCalendar(currentYear, currentMonth);
            });

            // Month/Year selector toggle
            document.getElementById('month-year-selector').addEventListener('click', () => {
                const picker = document.getElementById('monthYearPicker');
                new bootstrap.Collapse(picker, {
                    toggle: true
                });
            });

            // Month/Year selection
            document.getElementById('year-select').addEventListener('change', updateFromPicker);
            document.getElementById('month-select').addEventListener('change', updateFromPicker);

            function updateFromPicker() {
                currentYear = parseInt(document.getElementById('year-select').value);
                currentMonth = parseInt(document.getElementById('month-select').value);
                renderCalendar(currentYear, currentMonth);

                // Hide picker
                const picker = document.getElementById('monthYearPicker');
                bootstrap.Collapse.getInstance(picker)?.hide();
            }
        });

        function renderCalendar(year, month) {
            // Update header
            document.getElementById('calendar-month-year').textContent = `${monthNames[month]} ${year}`;

            // Update selectors
            document.getElementById('year-select').value = year;
            document.getElementById('month-select').value = month;

            // Clear grid
            const grid = document.getElementById('calendar-grid');
            grid.innerHTML = '';

            // Add day headers
            dayNames.forEach(day => {
                const header = document.createElement('div');
                header.className = 'calendar-day-header';
                header.textContent = day;
                grid.appendChild(header);
            });

            // Calculate days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const prevLastDay = new Date(year, month, 0);

            let startDayOfWeek = firstDay.getDay();
            startDayOfWeek = startDayOfWeek === 0 ? 6 : startDayOfWeek - 1; // Adjust for Monday start

            // Previous month days
            for (let i = startDayOfWeek - 1; i >= 0; i--) {
                const day = prevLastDay.getDate() - i;
                const dayDiv = createDayElement(day, true);
                grid.appendChild(dayDiv);
            }

            // Current month days
            for (let day = 1; day <= lastDay.getDate(); day++) {
                const dayDiv = createDayElement(day, false);
                grid.appendChild(dayDiv);
            }

            // Next month days to complete grid
            const totalCells = grid.children.length - 7; // Exclude headers
            const remainingCells = (Math.ceil((totalCells) / 7) * 7) - totalCells;
            for (let day = 1; day <= remainingCells; day++) {
                const dayDiv = createDayElement(day, true);
                grid.appendChild(dayDiv);
            }

            // Fetch events for this month
            fetchMonthEvents(year, month);
        }

        function createDayElement(day, isOtherMonth) {
            const div = document.createElement('div');
            div.className = 'calendar-day';

            if (isOtherMonth) {
                div.classList.add('other-month');
            } else {
                // Check if today
                const today = new Date();
                if (day === today.getDate() && currentMonth === today.getMonth() && currentYear === today.getFullYear()) {
                    div.classList.add('today');
                }
            }

            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = day;
            div.appendChild(dayNumber);

            const eventDots = document.createElement('div');
            eventDots.className = 'event-dots';
            div.appendChild(eventDots);

            // Store day for later
            if (!isOtherMonth) {
                div.dataset.day = day;
                div.addEventListener('click', () => showDayDetails(currentYear, currentMonth, day));
            }

            return div;
        }

        function fetchMonthEvents(year, month) {
            fetch(`/audit/calendar/${year}/${month + 1}`)
                .then(response => response.json())
                .then(data => {
                    if (data.days) {
                        Object.keys(data.days).forEach(day => {
                            const dayData = data.days[day];
                            const dayElement = document.querySelector(`.calendar-day[data-day="${day}"]`);

                            if (dayElement) {
                                dayElement.classList.add('has-events');
                                const dotsContainer = dayElement.querySelector('.event-dots');

                                // Create dots for different event types
                                const eventTypes = Object.keys(dayData.events);
                                const maxDots = 6;
                                const dotsToShow = Math.min(eventTypes.length, maxDots);

                                for (let i = 0; i < dotsToShow; i++) {
                                    const dot = document.createElement('span');
                                    dot.className = 'event-dot';
                                    dot.style.backgroundColor = getEventColor(eventTypes[i]);
                                    dot.title = eventTypes[i];
                                    dotsContainer.appendChild(dot);
                                }

                                // Add count if more events
                                if (dayData.total > maxDots) {
                                    const count = document.createElement('span');
                                    count.className = 'event-count';
                                    count.textContent = `+${dayData.total - maxDots}`;
                                    dotsContainer.appendChild(count);
                                }
                            }
                        });
                    }
                })
                .catch(error => console.error('Error fetching events:', error));
        }

        function getEventColor(eventType) {
            const colors = {
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
                'hotel.deleted': '#6c757d',
            };
            return colors[eventType] || '#6c757d';
        }

        function showDayDetails(year, month, day) {
            const modal = new bootstrap.Modal(document.getElementById('day-details-modal'));
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

            // Update title
            document.getElementById('day-details-title').textContent =
                `Events for ${monthNames[month]} ${day}, ${year}`;

            // Show loading
            document.getElementById('day-details-body').innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            modal.show();

            // Fetch day details
            fetch(`/audit/day/${dateStr}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('day-details-body').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('day-details-body').innerHTML = `
                        <div class="alert alert-danger">
                            Error loading data: ${error.message}
                        </div>
                    `;
                });
        }
    </script>
@endsection
