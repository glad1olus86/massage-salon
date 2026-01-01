@extends('layouts.operator')

@section('page-title')
    {{ __('Календарь') }}
@endsection

@section('content')
<section class="calendar-page">
    @if($selectedBranch)
    <div class="card calendar-card">
        <div class="calendar-header">
            <button type="button" class="calendar-nav-btn" onclick="navigateMonth(-1)" title="{{ __('Предыдущий месяц') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </button>
            <div class="calendar-header-center">
                <h2 class="calendar-month-title" id="monthTitle">
                    {{ $startDate->translatedFormat('F Y') }}
                </h2>
                <span class="calendar-branch-badge">{{ $selectedBranch->name }}</span>
            </div>
            <button type="button" class="calendar-nav-btn" onclick="navigateMonth(1)" title="{{ __('Следующий месяц') }}">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
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
                @endphp

                @for($i = 1; $i < $startDayOfWeek; $i++)
                    <div class="calendar-day calendar-day--empty"></div>
                @endfor

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
                                        <span class="cleaning-badge cleaning-badge--clean">✓</span>
                                    @elseif($cleanCount > 0)
                                        <span class="cleaning-badge cleaning-badge--partial">{{ $cleanCount }}/{{ $totalCount }}</span>
                                    @else
                                        <span class="cleaning-badge cleaning-badge--dirty">○</span>
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
                <span class="cleaning-badge cleaning-badge--clean">✓</span>
                <span>{{ __('Уборка выполнена') }}</span>
            </div>
            <div class="legend-item">
                <span class="cleaning-badge cleaning-badge--dirty">○</span>
                <span>{{ __('Ожидает уборки') }}</span>
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="empty-state" style="padding: 60px 20px; text-align: center; color: #888;">
            <p>{{ __('Вам не назначен филиал') }}</p>
            <p style="font-size: 14px; margin-top: 10px;">{{ __('Обратитесь к администратору для назначения филиала') }}</p>
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
<style>
.calendar-branch-badge {
    display: inline-block;
    padding: 4px 12px;
    background: var(--brand-color);
    color: white;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
}
</style>
@endpush

@push('scripts')
<script>
let currentMonth = {{ $month }};
let currentYear = {{ $year }};
let currentDate = null;

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
    
    fetch('{{ route("operator.calendar.month-data") }}?month=' + currentMonth + '&year=' + currentYear, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('monthTitle').textContent = data.monthName;
        renderCalendarDays(data.calendarData);
        history.pushState({}, '', '{{ route("operator.calendar") }}?month=' + currentMonth + '&year=' + currentYear);
    })
    .catch(error => {
        calendarDays.innerHTML = '<div class="error" style="grid-column: 1/-1; padding: 60px; text-align: center; color: #ef4444;">{{ __("Ошибка загрузки") }}</div>';
    });
}

function renderCalendarDays(calendarData) {
    const calendarDays = document.getElementById('calendarDays');
    let html = '';
    
    const firstDate = Object.keys(calendarData)[0];
    if (firstDate) {
        const firstDay = new Date(firstDate);
        let dayOfWeek = firstDay.getDay();
        dayOfWeek = dayOfWeek === 0 ? 7 : dayOfWeek;
        for (let i = 1; i < dayOfWeek; i++) {
            html += '<div class="calendar-day calendar-day--empty"></div>';
        }
    }
    
    for (const [dateKey, dayData] of Object.entries(calendarData)) {
        const isToday = dayData.isToday ? 'calendar-day--today' : '';
        const isWeekend = dayData.isWeekend ? 'calendar-day--weekend' : '';
        
        html += '<div class="calendar-day ' + isToday + ' ' + isWeekend + '" onclick="openDayDetails(\'' + dateKey + '\')" data-date="' + dateKey + '">';
        html += '<div class="day-number">' + dayData.day + '</div>';
        
        if (dayData.bookingsCount > 0) {
            html += '<div class="day-bookings"><span class="bookings-indicator">{{ __("резервации") }}';
            for (let i = 0; i < Math.min(dayData.bookingsCount, 5); i++) {
                html += '<span class="booking-dot"></span>';
            }
            html += '</span></div>';
        }
        
        if (dayData.duty) {
            const dutyStatus = dayData.duty.status === 'completed' ? 'duty--completed' : 'duty--pending';
            const dutyName = dayData.duty.user ? dayData.duty.user.name : 'N/A';
            html += '<div class="day-duty ' + dutyStatus + '">';
            html += '<span class="duty-icon"><img src="{{ asset("infinity/assets/icons/cleaning-icon.svg") }}" alt="" width="16" height="16"></span>';
            html += '<span class="duty-name">' + dutyName + '</span>';
            html += '</div>';
        }
        
        html += '</div>';
    }
    
    calendarDays.innerHTML = html;
}

function openDayDetails(date) {
    currentDate = date;
    document.getElementById('dayDetailsModal').style.display = 'flex';
    document.getElementById('dayDetailsContent').innerHTML = '<div class="loading">{{ __("Загрузка...") }}</div>';
    
    fetch('{{ url("operator/calendar/day") }}/' + date, {
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
        html += '<div class="duty-details-status">' + (data.duty.status === 'completed' ? '<span style="color:#22c55e">✓</span> {{ __("Выполнено") }}' : '<span style="color:#f59e0b">○</span> {{ __("Ожидает выполнения") }}') + '</div>';
        html += '</div>';
        html += '</div>';
        html += '<div class="duty-actions">';
        html += '<button class="btn btn--secondary btn--sm" onclick="openChangeDutyModal(' + data.duty.id + ', ' + JSON.stringify(data.employees).replace(/"/g, '&quot;') + ')">{{ __("Сменить") }}</button>';
        if (data.duty.status !== 'completed') {
            html += '<button class="btn btn--brand btn--sm" onclick="completeDuty(' + data.duty.id + ')">✓ {{ __("Выполнено") }}</button>';
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
            let statusText = status.status === 'clean' ? '✓ {{ __("Чисто") }}' : '○ {{ __("Грязно") }}';
            html += '<div class="cleaning-status-item">';
            html += '<span class="cleaning-status-name">' + name + '</span>';
            html += '<button class="cleaning-status-toggle ' + statusClass + '" onclick="toggleCleaningStatus(' + status.id + ', \'' + status.status + '\')">' + statusText + '</button>';
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

// Booking functions
function openAddBookingModal(date, rooms, employees) {
    document.getElementById('bookingDate').value = date;
    document.getElementById('bookingStartTime').value = '';
    document.getElementById('bookingEndTime').value = '';
    
    let roomSelect = document.getElementById('bookingRoom');
    roomSelect.innerHTML = '<option value="">{{ __("Выберите комнату") }}</option>';
    if (rooms && rooms.length > 0) {
        rooms.forEach(function(room) {
            roomSelect.innerHTML += '<option value="' + room.id + '">{{ __("Комната") }} ' + room.room_number + '</option>';
        });
    }
    
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
            if (currentDate) openDayDetails(currentDate);
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
            if (currentDate) openDayDetails(currentDate);
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    })
    .catch(error => alert('{{ __("Ошибка") }}'));
}

// Duty functions
function openChangeDutyModal(dutyId, employees) {
    document.getElementById('changeDutyId').value = dutyId;
    
    let select = document.getElementById('newDutyPerson');
    select.innerHTML = '<option value="">{{ __("Выберите сотрудника") }}</option>';
    
    let pointsList = '';
    if (employees && employees.length > 0) {
        employees.forEach(function(emp) {
            let userName = emp.user ? emp.user.name : 'N/A';
            let userId = emp.user ? emp.user.id : emp.user_id;
            select.innerHTML += '<option value="' + userId + '">' + userName + '</option>';
            pointsList += '<div class="employee-points-item"><span>' + userName + '</span><span>' + emp.points + ' {{ __("баллов") }}</span></div>';
        });
    }
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
            if (currentDate) openDayDetails(currentDate);
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    })
    .catch(error => alert('{{ __("Ошибка") }}'));
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
            if (currentDate) openDayDetails(currentDate);
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    })
    .catch(error => alert('{{ __("Ошибка") }}'));
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
            if (currentDate) openDayDetails(currentDate);
            loadMonthData();
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    })
    .catch(error => alert('{{ __("Ошибка") }}'));
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.style.display = 'none';
    }
});
</script>
@endpush
