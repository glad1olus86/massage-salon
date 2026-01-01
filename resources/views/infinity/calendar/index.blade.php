@extends('layouts.infinity')

@section('page-title')
    {{ __('Календарь') }}
@endsection

@section('content')
<section class="calendar-page">
    @if($selectedBranch)
    <div class="card calendar-card">
        <div class="calendar-header">
            <button type="button" class="calendar-nav-btn" onclick="navigateMonth(-1)" title="{{ __('Предыдущий месяц') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
            <div class="calendar-header-center">
                <h2 class="calendar-month-title" id="monthTitle">
                    {{ $startDate->translatedFormat('F Y') }}
                </h2>
                @if($branches->count() > 1)
                <select id="branchSelect" class="calendar-branch-select" onchange="changeBranch(this.value)">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $selectedBranch && $selectedBranch->id == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @else
                <span class="calendar-branch-badge">{{ $selectedBranch->name }}</span>
                @endif
            </div>
            <button type="button" class="calendar-nav-btn" onclick="navigateMonth(1)" title="{{ __('Следующий месяц') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
        </div>

        <div class="calendar-grid" id="calendarGrid">
            <div class="calendar-weekdays">
                <div class="weekday">{{ __('Понедельник') }}</div>
                <div class="weekday">{{ __('Вторник') }}</div>
                <div class="weekday">{{ __('Среда') }}</div>
                <div class="weekday">{{ __('Четверг') }}</div>
                <div class="weekday">{{ __('Пятница') }}</div>
                <div class="weekday weekend">{{ __('Суббота') }}</div>
                <div class="weekday weekend">{{ __('Воскресенье') }}</div>
            </div>

            <div class="calendar-days" id="calendarDays">
                @php
                    $firstDayOfMonth = $startDate->copy()->startOfMonth();
                    $startDayOfWeek = $firstDayOfMonth->dayOfWeekIso;
                    $daysInMonth = $startDate->daysInMonth;
                @endphp

                {{-- Empty cells before first day --}}
                @for($i = 1; $i < $startDayOfWeek; $i++)
                    <div class="calendar-day calendar-day--empty"></div>
                @endfor

                {{-- Days of month --}}
                @foreach($calendarData as $dateKey => $dayData)
                    <div class="calendar-day {{ $dayData['isToday'] ? 'calendar-day--today' : '' }} {{ $dayData['isWeekend'] ? 'calendar-day--weekend' : '' }}"
                         onclick="openDayDetails('{{ $dateKey }}')"
                         data-date="{{ $dateKey }}">
                        <div class="day-number">{{ $dayData['day'] }}</div>
                        
                        @if($dayData['bookingsCount'] > 0)
                        <div class="day-bookings">
                            <span class="bookings-indicator">
                                {{ __('резервации') }}
                                @for($i = 0; $i < min($dayData['bookingsCount'], 5); $i++)
                                    <span class="booking-dot"></span>
                                @endfor
                            </span>
                        </div>
                        @endif

                        @if($dayData['duty'])
                            @php $duty = $dayData['duty']; @endphp
                            <div class="day-duty {{ $duty->status === 'completed' ? 'duty--completed' : 'duty--pending' }}">
                                <span class="duty-icon">
                                    <img src="{{ asset('infinity/assets/icons/cleaning-icon.svg') }}" alt="" width="16" height="16">
                                </span>
                                <span class="duty-name">{{ $duty->user->name ?? 'N/A' }}</span>
                            </div>
                            <div class="day-cleaning-status">
                                @php
                                    $cleanCount = $duty->cleaningStatuses->where('status', 'clean')->count();
                                    $totalCount = $duty->cleaningStatuses->count();
                                @endphp
                                @if($totalCount > 0)
                                    @if($cleanCount == $totalCount)
                                        <span class="cleaning-badge cleaning-badge--clean">
                                            <svg viewBox="0 0 20 20" fill="none" width="12" height="12">
                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="currentColor"/>
                                            </svg>
                                        </span>
                                    @elseif($cleanCount > 0)
                                        <span class="cleaning-badge cleaning-badge--partial">{{ $cleanCount }}/{{ $totalCount }}</span>
                                    @else
                                        <span class="cleaning-badge cleaning-badge--dirty">
                                            <svg viewBox="0 0 20 20" fill="none" width="12" height="12">
                                                <circle cx="10" cy="10" r="4" fill="currentColor"/>
                                            </svg>
                                        </span>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="calendar-legend">
            <div class="legend-item">
                <span class="booking-dot"></span>
                <span>{{ __('резервации') }}</span>
            </div>
            <div class="legend-item">
                <span class="duty-icon">
                    <img src="{{ asset('infinity/assets/icons/cleaning-icon.svg') }}" alt="" width="16" height="16">
                </span>
                <span>{{ __('Дежурный') }}</span>
            </div>
            <div class="legend-item">
                <span class="cleaning-badge cleaning-badge--clean">
                    <svg viewBox="0 0 20 20" fill="none" width="12" height="12">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="currentColor"/>
                    </svg>
                </span>
                <span>{{ __('Уборка выполнена') }}</span>
            </div>
            <div class="legend-item">
                <span class="cleaning-badge cleaning-badge--dirty">
                    <svg viewBox="0 0 20 20" fill="none" width="12" height="12">
                        <circle cx="10" cy="10" r="4" fill="currentColor"/>
                    </svg>
                </span>
                <span>{{ __('Ожидает уборки') }}</span>
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="empty-state">
            <p>{{ __('Выберите филиал для просмотра календаря') }}</p>
        </div>
    </div>
    @endif
</section>

{{-- Day Details Modal --}}
<div id="dayDetailsModal" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-content--large">
        <div class="modal-header">
            <h3 id="dayDetailsTitle">{{ __('Детали дня') }}</h3>
            <button type="button" class="modal-close" onclick="closeDayDetailsModal()">&times;</button>
        </div>
        <div class="modal-body" id="dayDetailsContent">
            <div class="loading">{{ __('Загрузка...') }}</div>
        </div>
    </div>
</div>

{{-- Add Booking Modal --}}
<div id="addBookingModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>{{ __('Новое бронирование') }}</h3>
            <button type="button" class="modal-close" onclick="closeAddBookingModal()">&times;</button>
        </div>
        <form id="addBookingForm" onsubmit="submitBooking(event)">
            @csrf
            <input type="hidden" name="branch_id" value="{{ $selectedBranch?->id }}">
            <input type="hidden" name="booking_date" id="bookingDate">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">{{ __('Комната') }} <span class="required">*</span></label>
                    <select name="room_id" id="bookingRoom" class="form-input" required>
                        <option value="">{{ __('Выберите комнату') }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Сотрудник') }} <span class="required">*</span></label>
                    <select name="user_id" id="bookingUser" class="form-input" required>
                        <option value="">{{ __('Выберите сотрудника') }}</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">{{ __('Начало') }} <span class="required">*</span></label>
                        <input type="time" name="start_time" id="bookingStartTime" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Конец') }} <span class="required">*</span></label>
                        <input type="time" name="end_time" id="bookingEndTime" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Примечания') }}</label>
                    <textarea name="notes" class="form-input" rows="2"></textarea>
                </div>
                <div id="availabilityMessage" class="availability-message"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeAddBookingModal()">{{ __('Отмена') }}</button>
                <button type="submit" class="btn btn--brand">{{ __('Создать') }}</button>
            </div>
        </form>
    </div>
</div>

{{-- Change Duty Modal --}}
<div id="changeDutyModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>{{ __('Сменить дежурного') }}</h3>
            <button type="button" class="modal-close" onclick="closeChangeDutyModal()">&times;</button>
        </div>
        <form id="changeDutyForm" onsubmit="submitChangeDuty(event)">
            @csrf
            @method('PUT')
            <input type="hidden" name="duty_id" id="changeDutyId">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">{{ __('Новый дежурный') }} <span class="required">*</span></label>
                    <select name="user_id" id="newDutyPerson" class="form-input" required>
                        <option value="">{{ __('Выберите сотрудника') }}</option>
                    </select>
                </div>
                <div id="employeesWithPoints" class="employees-points-list"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeChangeDutyModal()">{{ __('Отмена') }}</button>
                <button type="submit" class="btn btn--brand">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection


@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/calendar.css') }}">
@endpush

@push('scripts')
<script>
let currentMonth = {{ $month }};
let currentYear = {{ $year }};
let currentBranchId = {{ $selectedBranch?->id ?? 'null' }};
let currentDate = null;

function changeBranch(branchId) {
    currentBranchId = branchId;
    loadMonthData();
}

function navigateMonth(direction) {
    currentMonth += direction;
    if (currentMonth > 12) {
        currentMonth = 1;
        currentYear++;
    } else if (currentMonth < 1) {
        currentMonth = 12;
        currentYear--;
    }
    loadMonthData();
}

function loadMonthData() {
    const calendarDays = document.getElementById('calendarDays');
    calendarDays.innerHTML = '<div class="loading" style="grid-column: 1/-1; padding: 60px;">{{ __("Загрузка...") }}</div>';
    
    fetch('{{ route("infinity.calendar.month-data") }}?branch_id=' + currentBranchId + '&month=' + currentMonth + '&year=' + currentYear, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('monthTitle').textContent = data.monthName;
        renderCalendarDays(data.calendarData);
        // Update URL without reload
        history.pushState({}, '', '{{ route("infinity.calendar") }}?branch_id=' + currentBranchId + '&month=' + currentMonth + '&year=' + currentYear);
    })
    .catch(error => {
        calendarDays.innerHTML = '<div class="error" style="grid-column: 1/-1; padding: 60px; text-align: center; color: #ef4444;">{{ __("Ошибка загрузки") }}</div>';
    });
}

function renderCalendarDays(calendarData) {
    const calendarDays = document.getElementById('calendarDays');
    let html = '';
    
    // Calculate empty cells before first day
    const firstDate = Object.keys(calendarData)[0];
    if (firstDate) {
        const firstDay = new Date(firstDate);
        let dayOfWeek = firstDay.getDay();
        dayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek; // Convert Sunday from 0 to 7
        for (let i = 1; i < dayOfWeek; i++) {
            html += '<div class="calendar-day calendar-day--empty"></div>';
        }
    }
    
    // Render days
    for (const [dateKey, dayData] of Object.entries(calendarData)) {
        const isToday = dayData.isToday ? 'calendar-day--today' : '';
        const isWeekend = dayData.isWeekend ? 'calendar-day--weekend' : '';
        
        html += '<div class="calendar-day ' + isToday + ' ' + isWeekend + '" onclick="openDayDetails(\'' + dateKey + '\')" data-date="' + dateKey + '">';
        html += '<div class="day-number">' + dayData.day + '</div>';
        
        // Bookings indicator
        if (dayData.bookingsCount > 0) {
            html += '<div class="day-bookings"><span class="bookings-indicator">{{ __("резервации") }}';
            for (let i = 0; i < Math.min(dayData.bookingsCount, 5); i++) {
                html += '<span class="booking-dot"></span>';
            }
            html += '</span></div>';
        }
        
        // Duty info
        if (dayData.duty) {
            const dutyStatus = dayData.duty.status === 'completed' ? 'duty--completed' : 'duty--pending';
            const dutyName = dayData.duty.user ? dayData.duty.user.name : 'N/A';
            html += '<div class="day-duty ' + dutyStatus + '">';
            html += '<span class="duty-icon"><img src="{{ asset("infinity/assets/icons/cleaning-icon.svg") }}" alt="" width="16" height="16"></span>';
            html += '<span class="duty-name">' + dutyName + '</span>';
            html += '</div>';
            
            // Cleaning status badge
            if (dayData.duty.cleaning_statuses && dayData.duty.cleaning_statuses.length > 0) {
                const cleanCount = dayData.duty.cleaning_statuses.filter(s => s.status === 'clean').length;
                const totalCount = dayData.duty.cleaning_statuses.length;
                html += '<div class="day-cleaning-status">';
                if (cleanCount === totalCount) {
                    html += '<span class="cleaning-badge cleaning-badge--clean"><svg viewBox="0 0 20 20" fill="none" width="12" height="12"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="currentColor"/></svg></span>';
                } else if (cleanCount > 0) {
                    html += '<span class="cleaning-badge cleaning-badge--partial">' + cleanCount + '/' + totalCount + '</span>';
                } else {
                    html += '<span class="cleaning-badge cleaning-badge--dirty"><svg viewBox="0 0 20 20" fill="none" width="12" height="12"><circle cx="10" cy="10" r="4" fill="currentColor"/></svg></span>';
                }
                html += '</div>';
            }
        }
        
        html += '</div>';
    }
    
    calendarDays.innerHTML = html;
}

function openDayDetails(date) {
    currentDate = date;
    document.getElementById('dayDetailsModal').style.display = 'flex';
    document.getElementById('dayDetailsContent').innerHTML = '<div class="loading">{{ __("Загрузка...") }}</div>';
    
    fetch('{{ url("infinitycrm/calendar/day") }}/' + date + '?branch_id=' + currentBranchId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('dayDetailsTitle').textContent = data.formatted_date;
        renderDayDetails(data);
    })
    .catch(error => {
        document.getElementById('dayDetailsContent').innerHTML = '<div class="error">{{ __("Ошибка загрузки") }}</div>';
    });
}

function renderDayDetails(data) {
    let html = '';
    
    // Bookings section
    html += '<div class="day-details-section">';
    html += '<h4>{{ __("Бронирования") }} <button class="btn btn--brand btn--sm" onclick="openAddBookingModal(\'' + data.date + '\', ' + JSON.stringify(data.rooms).replace(/"/g, '&quot;') + ', ' + JSON.stringify(data.branchEmployees || []).replace(/"/g, '&quot;') + ')">+ {{ __("Добавить") }}</button></h4>';
    if (data.bookings && data.bookings.length > 0) {
        html += '<div class="bookings-list">';
        data.bookings.forEach(function(booking) {
            let roomNumber = booking.room && booking.room.room_number ? booking.room.room_number : 'N/A';
            html += '<div class="booking-item">';
            html += '<span class="booking-time">' + booking.start_time.substring(0,5) + ' - ' + booking.end_time.substring(0,5) + '</span>';
            html += '<span class="booking-room">{{ __("Комната") }} ' + roomNumber + '</span>';
            html += '<span class="booking-user">' + (booking.user ? booking.user.name : 'N/A') + '</span>';
            html += '<div class="booking-actions">';
            html += '<button class="btn btn--outline-danger btn--sm" onclick="cancelBooking(' + booking.id + ')">{{ __("Отмена") }}</button>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
    } else {
        html += '<div class="no-duty-message">{{ __("Нет бронирований на этот день") }}</div>';
    }
    html += '</div>';
    
    // Duty section
    html += '<div class="day-details-section">';
    html += '<h4>{{ __("Дежурный") }}</h4>';
    if (data.duty) {
        html += '<div class="duty-info">';
        html += '<div class="duty-person">';
        html += '<div class="duty-avatar">' + (data.duty.user ? data.duty.user.name.substring(0,2).toUpperCase() : '?') + '</div>';
        html += '<div class="duty-details">';
        html += '<div class="duty-details-name">' + (data.duty.user ? data.duty.user.name : 'N/A') + '</div>';
        html += '<div class="duty-details-status">' + (data.duty.status === 'completed' ? '<svg viewBox="0 0 20 20" fill="none" width="12" height="12" style="vertical-align: middle;"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="#22c55e"/></svg> {{ __("Выполнено") }}' : '<svg viewBox="0 0 20 20" fill="none" width="12" height="12" style="vertical-align: middle;"><circle cx="10" cy="10" r="4" fill="#f59e0b"/></svg> {{ __("Ожидает выполнения") }}') + '</div>';
        html += '</div>';
        html += '</div>';
        html += '<div class="duty-actions">';
        html += '<button class="btn btn--secondary btn--sm" onclick="openChangeDutyModal(' + data.duty.id + ', ' + JSON.stringify(data.employees).replace(/"/g, '&quot;') + ')">{{ __("Сменить") }}</button>';
        if (data.duty.status !== 'completed') {
            html += '<button class="btn btn--brand btn--sm" onclick="completeDuty(' + data.duty.id + ')"><svg viewBox="0 0 20 20" fill="none" width="12" height="12" style="vertical-align: middle;"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="currentColor"/></svg> {{ __("Выполнено") }}</button>';
        }
        html += '</div>';
        html += '</div>';
    } else {
        html += '<div class="no-duty-message">{{ __("Дежурный не назначен на этот день") }}</div>';
    }
    html += '</div>';
    
    // Cleaning statuses
    if (data.duty && data.duty.cleaning_statuses && data.duty.cleaning_statuses.length > 0) {
        html += '<div class="day-details-section">';
        html += '<h4>{{ __("Статус уборки комнат") }}</h4>';
        html += '<div class="cleaning-statuses">';
        data.duty.cleaning_statuses.forEach(function(status) {
            let roomNumber = status.room && status.room.room_number ? status.room.room_number : 'N/A';
            let name = status.area_type === 'common_area' ? '{{ __("Общая зона") }}' : '{{ __("Комната") }} ' + roomNumber;
            let statusClass = status.status === 'clean' ? 'clean' : 'dirty';
            let statusIcon = status.status === 'clean' 
                ? '<svg viewBox="0 0 20 20" fill="none" width="12" height="12" style="vertical-align: middle;"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="currentColor"/></svg> {{ __("Чисто") }}'
                : '<svg viewBox="0 0 20 20" fill="none" width="12" height="12" style="vertical-align: middle;"><circle cx="10" cy="10" r="4" fill="currentColor"/></svg> {{ __("Грязно") }}';
            html += '<div class="cleaning-status-item">';
            html += '<span class="cleaning-status-name">' + name + '</span>';
            html += '<button class="cleaning-status-toggle ' + statusClass + '" onclick="toggleCleaningStatus(' + status.id + ', \'' + status.status + '\')">' + statusIcon + '</button>';
            html += '</div>';
        });
        html += '</div>';
        html += '</div>';
    }
    
    document.getElementById('dayDetailsContent').innerHTML = html;
}

function closeDayDetailsModal() {
    document.getElementById('dayDetailsModal').style.display = 'none';
}

function openAddBookingModal(date, rooms, employees) {
    document.getElementById('bookingDate').value = date;
    document.getElementById('bookingStartTime').value = '';
    document.getElementById('bookingEndTime').value = '';
    document.getElementById('availabilityMessage').innerHTML = '';
    document.getElementById('availabilityMessage').className = 'availability-message';
    
    // Заполняем комнаты
    let roomSelect = document.getElementById('bookingRoom');
    roomSelect.innerHTML = '<option value="">{{ __("Выберите комнату") }}</option>';
    if (rooms && rooms.length > 0) {
        rooms.forEach(function(room) {
            roomSelect.innerHTML += '<option value="' + room.id + '">{{ __("Комната") }} ' + room.room_number + '</option>';
        });
    }
    
    // Заполняем сотрудников
    let userSelect = document.getElementById('bookingUser');
    userSelect.innerHTML = '<option value="">{{ __("Выберите сотрудника") }}</option>';
    if (employees && employees.length > 0) {
        employees.forEach(function(emp) {
            userSelect.innerHTML += '<option value="' + emp.id + '">' + emp.name + '</option>';
        });
    }
    
    document.getElementById('addBookingModal').style.display = 'flex';
}

function closeAddBookingModal() {
    document.getElementById('addBookingModal').style.display = 'none';
    document.getElementById('addBookingForm').reset();
}

// Debounce function for availability check
let availabilityTimeout = null;
function checkSlotAvailability() {
    const roomId = document.getElementById('bookingRoom').value;
    const date = document.getElementById('bookingDate').value;
    const startTime = document.getElementById('bookingStartTime').value;
    const endTime = document.getElementById('bookingEndTime').value;
    
    if (!roomId || !date || !startTime || !endTime) {
        document.getElementById('availabilityMessage').innerHTML = '';
        return;
    }
    
    // Clear previous timeout
    if (availabilityTimeout) clearTimeout(availabilityTimeout);
    
    // Debounce the request
    availabilityTimeout = setTimeout(function() {
        fetch('{{ route("infinity.bookings.check-availability") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                room_id: roomId,
                booking_date: date,
                start_time: startTime,
                end_time: endTime
            })
        })
        .then(response => response.json())
        .then(data => {
            const msgEl = document.getElementById('availabilityMessage');
            msgEl.textContent = data.message;
            msgEl.className = 'availability-message ' + (data.available ? 'available' : 'unavailable');
        })
        .catch(error => {
            console.error('Availability check error:', error);
        });
    }, 300);
}

// Add event listeners for availability check
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('bookingRoom');
    const startTime = document.getElementById('bookingStartTime');
    const endTime = document.getElementById('bookingEndTime');
    
    if (roomSelect) roomSelect.addEventListener('change', checkSlotAvailability);
    if (startTime) startTime.addEventListener('change', checkSlotAvailability);
    if (endTime) endTime.addEventListener('change', checkSlotAvailability);
});

function submitBooking(e) {
    e.preventDefault();
    let form = document.getElementById('addBookingForm');
    let formData = new FormData(form);
    
    fetch('{{ route("infinity.bookings.store") }}', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeAddBookingModal();
            // Refresh day details and calendar without page reload
            if (currentDate) {
                openDayDetails(currentDate);
            }
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    })
    .catch(error => alert('{{ __("Ошибка") }}'));
}

function cancelBooking(bookingId) {
    if (!confirm('{{ __("Отменить бронирование?") }}')) return;
    
    fetch('{{ url("infinitycrm/bookings") }}/' + bookingId, {
        method: 'DELETE',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh day details and calendar without page reload
            if (currentDate) {
                openDayDetails(currentDate);
            }
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    });
}

function openChangeDutyModal(dutyId, employees) {
    document.getElementById('changeDutyId').value = dutyId;
    let select = document.getElementById('newDutyPerson');
    select.innerHTML = '<option value="">{{ __("Выберите сотрудника") }}</option>';
    
    let pointsList = '';
    employees.forEach(function(emp) {
        select.innerHTML += '<option value="' + emp.user_id + '">' + emp.user.name + '</option>';
        pointsList += '<div class="employee-points-item"><span>' + emp.user.name + '</span><span class="employee-points">' + emp.points + ' {{ __("баллов") }}</span></div>';
    });
    document.getElementById('employeesWithPoints').innerHTML = pointsList;
    document.getElementById('changeDutyModal').style.display = 'flex';
}

function closeChangeDutyModal() {
    document.getElementById('changeDutyModal').style.display = 'none';
}

function submitChangeDuty(e) {
    e.preventDefault();
    let dutyId = document.getElementById('changeDutyId').value;
    let userId = document.getElementById('newDutyPerson').value;
    
    fetch('{{ url("infinitycrm/duties") }}/' + dutyId + '/change', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ user_id: userId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeChangeDutyModal();
            // Refresh day details and calendar without page reload
            if (currentDate) {
                openDayDetails(currentDate);
            }
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    });
}

function completeDuty(dutyId) {
    if (!confirm('{{ __("Отметить дежурство как выполненное?") }}')) return;
    
    fetch('{{ url("infinitycrm/duties") }}/' + dutyId + '/complete', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh day details and calendar without page reload
            if (currentDate) {
                openDayDetails(currentDate);
            }
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    });
}

function toggleCleaningStatus(statusId, currentStatus) {
    let newStatus = currentStatus === 'clean' ? 'dirty' : 'clean';
    
    fetch('{{ url("infinitycrm/cleaning-status") }}/' + statusId, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Refresh day details without page reload
            if (currentDate) {
                openDayDetails(currentDate);
            }
            // Also refresh calendar to update badges
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    })
    .catch(error => {
        alert('{{ __("Ошибка") }}');
    });
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>
@endpush
