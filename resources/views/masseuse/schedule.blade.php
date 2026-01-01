@extends('layouts.masseuse')

@section('page-title')
    {{ __('Моё расписание') }}
@endsection

@section('content')
<div class="schedule-page">
    <!-- Week Navigation -->
    <div class="week-nav">
        <a href="{{ route('masseuse.schedule', ['week' => $weekStart->copy()->subWeek()->toDateString()]) }}" class="week-nav__btn">
            ← {{ __('Предыдущая') }}
        </a>
        <div class="week-nav__center">
            <div class="week-nav__current">
                {{ $weekStart->translatedFormat('d M') }} - {{ $weekEnd->translatedFormat('d M Y') }}
            </div>
            @if(!$weekStart->isCurrentWeek())
                <a href="{{ route('masseuse.schedule') }}" class="week-nav__today">{{ __('Сегодня') }}</a>
            @endif
        </div>
        <a href="{{ route('masseuse.schedule', ['week' => $weekStart->copy()->addWeek()->toDateString()]) }}" class="week-nav__btn">
            {{ __('Следующая') }} →
        </a>
    </div>
    
    <!-- Week Grid -->
    <div class="week-grid">
        @foreach($weekDays as $dateKey => $day)
            <div class="day-column {{ $day['isToday'] ? 'day-column--today' : '' }}">
                <div class="day-column__header">
                    <div class="day-column__name">{{ $day['dayName'] }}</div>
                    <div class="day-column__date">{{ $day['dayNumber'] }}</div>
                </div>
                
                <div class="day-column__bookings">
                    @if($day['duty'])
                        <div class="duty-badge {{ $day['duty']->status === 'completed' ? 'duty-badge--completed' : 'duty-badge--pending' }}" 
                             onclick="openDutyModal('{{ $dateKey }}', {{ json_encode($day['duty']) }})"
                             data-duty-id="{{ $day['duty']->id }}">
                            <img src="{{ asset('infinity/assets/icons/cleaning-icon.svg') }}" alt="" class="duty-badge__icon">
                            <span class="duty-badge__text">
                                @if($day['duty']->status === 'completed')
                                    {{ __('Уборка выполнена') }}
                                @else
                                    {{ __('Дежурство') }}
                                @endif
                            </span>
                        </div>
                    @endif
                    
                    @forelse($day['bookings'] as $booking)
                        <div class="schedule-booking">
                            <div class="schedule-booking__time">
                                {{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('H:i') }}
                            </div>
                            <div class="schedule-booking__room">
                                {{ $booking->room->name ?? __('Комната') }}
                            </div>
                            @if($booking->client)
                                <div class="schedule-booking__client">
                                    {{ $booking->client->full_name }}
                                </div>
                            @endif
                            @if($booking->booking_date >= today())
                                <form action="{{ route('masseuse.bookings.destroy', $booking) }}" method="POST" class="schedule-booking__cancel">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('{{ __('Отменить?') }}')" title="{{ __('Отменить') }}">✕</button>
                                </form>
                            @endif
                        </div>
                    @empty
                        @if(!$day['duty'])
                        <div class="day-column__empty">
                            {{ __('Нет бронирований') }}
                        </div>
                        @endif
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
    
    <!-- Add Booking Button -->
    <div class="schedule-actions">
        <a href="{{ route('masseuse.bookings.create') }}" class="btn btn--dark">+ {{ __('Забронировать комнату') }}</a>
    </div>
</div>

<!-- Duty Modal -->
<div class="modal-overlay" id="dutyModal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">{{ __('Дежурство') }} - <span id="dutyDate"></span></h3>
            <button type="button" class="modal-close" onclick="closeDutyModal()">×</button>
        </div>
        <div class="modal-body">
            <div id="dutyStatusList"></div>
        </div>
        <div class="modal-footer" id="dutyFooter" style="display: none;">
            <span class="duty-completed-text">
                <svg viewBox="0 0 20 20" fill="none" width="16" height="16" style="vertical-align: middle;">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="currentColor"/>
                </svg>
                {{ __('Все зоны убраны!') }}
            </span>
        </div>
    </div>
</div>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse.css') }}">
<style>
/* Duty Badge */
.duty-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 13px;
    font-weight: 500;
}
.duty-badge--pending {
    background: rgba(234, 179, 8, 0.15);
    border: 1px solid rgba(234, 179, 8, 0.3);
    color: #b45309;
}
.duty-badge--pending .duty-badge__icon {
    filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(18deg) brightness(95%) contrast(98%);
}
.duty-badge--pending:hover {
    background: rgba(234, 179, 8, 0.25);
}
.duty-badge--completed {
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #16a34a;
}
.duty-badge--completed .duty-badge__icon {
    filter: invert(48%) sepia(79%) saturate(2476%) hue-rotate(86deg) brightness(95%) contrast(98%);
}
.duty-badge__icon { width: 18px; height: 18px; }

/* Modal */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-content {
    background: white;
    border-radius: 16px;
    width: 90%;
    max-width: 450px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: var(--brand-color, #8B1538);
    color: white;
}
.modal-title { margin: 0; font-size: 18px; font-weight: 600; }
.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    line-height: 1;
}
.modal-body { padding: 20px; max-height: 400px; overflow-y: auto; }
.modal-footer {
    padding: 16px 20px;
    background: rgba(34, 197, 94, 0.1);
    text-align: center;
}
.duty-completed-text { color: #16a34a; font-weight: 600; }

/* Cleaning Status Item */
.cleaning-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border: 1px solid #e5e5e5;
    border-radius: 10px;
    margin-bottom: 10px;
}
.cleaning-item--clean {
    background: rgba(34, 197, 94, 0.08);
    border-color: rgba(34, 197, 94, 0.3);
}
.cleaning-item__info { display: flex; align-items: center; gap: 10px; }
.cleaning-item__icon { width: 24px; height: 24px; color: var(--brand-color, #8B1538); display: flex; align-items: center; }
.cleaning-item__name { font-weight: 500; }
.cleaning-item__status {
    font-size: 12px;
    color: #666;
}
.cleaning-item__btn {
    padding: 6px 14px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}
.cleaning-item__btn--mark {
    background: var(--brand-color, #8B1538);
    color: white;
}
.cleaning-item__btn--mark:hover { opacity: 0.9; }
.cleaning-item__btn--done {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
    cursor: default;
}
.cleaning-item__btn--disabled {
    background: #f3f4f6;
    color: #9ca3af;
    cursor: not-allowed;
    font-size: 11px;
}
</style>
@endpush

@push('scripts')
<script>
let currentDutyId = null;
let currentDutyDate = null;
let cleaningStatuses = [];
const todayDate = '{{ now()->toDateString() }}';

function openDutyModal(dateKey, duty) {
    currentDutyId = duty.id;
    currentDutyDate = dateKey;
    cleaningStatuses = duty.cleaning_statuses || [];
    
    document.getElementById('dutyDate').textContent = dateKey;
    renderCleaningStatuses();
    document.getElementById('dutyModal').style.display = 'flex';
}

function closeDutyModal() {
    document.getElementById('dutyModal').style.display = 'none';
}

function renderCleaningStatuses() {
    const container = document.getElementById('dutyStatusList');
    const footer = document.getElementById('dutyFooter');
    const isToday = currentDutyDate === todayDate;
    
    if (cleaningStatuses.length === 0) {
        container.innerHTML = '<p style="text-align:center;color:#888;">{{ __("Нет зон для уборки") }}</p>';
        return;
    }
    
    let html = '';
    let allClean = true;
    
    cleaningStatuses.forEach(status => {
        const isClean = status.status === 'clean';
        if (!isClean) allClean = false;
        
        let name, iconSvg;
        if (status.area_type === 'common_area') {
            name = '{{ __("Общая зона") }}';
            iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>';
        } else {
            const roomNumber = status.room && status.room.room_number ? status.room.room_number : 'N/A';
            name = '{{ __("Комната") }} ' + roomNumber;
            iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="9" y1="3" x2="9" y2="21"></line></svg>';
        }
        
        let actionButton;
        if (isClean) {
            actionButton = '<button class="cleaning-item__btn cleaning-item__btn--done"><svg viewBox="0 0 20 20" fill="none" width="14" height="14" style="vertical-align: middle;"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="currentColor"/></svg> {{ __("Готово") }}</button>';
        } else if (isToday) {
            actionButton = `<button class="cleaning-item__btn cleaning-item__btn--mark" onclick="markClean(${status.id})">{{ __("Убрано") }}</button>`;
        } else {
            actionButton = '<span class="cleaning-item__btn cleaning-item__btn--disabled">{{ __("Только сегодня") }}</span>';
        }
        
        html += `
            <div class="cleaning-item ${isClean ? 'cleaning-item--clean' : ''}">
                <div class="cleaning-item__info">
                    <span class="cleaning-item__icon">${iconSvg}</span>
                    <div>
                        <div class="cleaning-item__name">${name}</div>
                        <div class="cleaning-item__status">${isClean ? '{{ __("Убрано") }}' : '{{ __("Ожидает уборки") }}'}</div>
                    </div>
                </div>
                ${actionButton}
            </div>
        `;
    });
    
    container.innerHTML = html;
    footer.style.display = allClean ? 'block' : 'none';
    
    // Обновляем бейдж если всё убрано
    if (allClean) {
        const badge = document.querySelector(`[data-duty-id="${currentDutyId}"]`);
        if (badge) {
            badge.classList.remove('duty-badge--pending');
            badge.classList.add('duty-badge--completed');
            badge.querySelector('.duty-badge__text').textContent = '{{ __("Уборка выполнена") }}';
        }
    }
}

function markClean(statusId) {
    fetch(`/masseuse/cleaning-status/${statusId}/mark-clean`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Перезагружаем страницу чтобы обновить данные
            window.location.reload();
        }
    })
    .catch(err => console.error(err));
}

// Закрытие по клику вне модалки
document.getElementById('dutyModal').addEventListener('click', function(e) {
    if (e.target === this) closeDutyModal();
});
</script>
@endpush
