@extends('layouts.masseuse')

@section('page-title')
    {{ __('Мой дашборд') }}
@endsection

@section('content')
<section class="masseuse-dashboard-grid">
    <!-- Primary Actions (mobile-first) -->
    <div class="primary-actions-row">
        <button type="button" class="action-tile action-tile--clickable" onclick="openClientArrivedModal()">
            <span class="action-tile__icon action-tile__icon--green">
                <img src="{{ asset('infinity/assets/icons/nav-clients-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('КЛИЕНТ ПРИШЁЛ') }}</span>
            @if($pendingTodayOrders->count() > 0)
            <span class="action-tile__badge">{{ $pendingTodayOrders->count() }}</span>
            @endif
        </button>
        
        <button type="button" class="action-tile action-tile--clickable" onclick="openCompleteOrderModal()">
            <span class="action-tile__icon action-tile__icon--blue">
                <img src="{{ asset('infinity/assets/icons/nav-orders-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('ЗАВЕРШИТЬ ЗАКАЗ') }}</span>
            @if($confirmedTodayOrders->count() > 0)
            <span class="action-tile__badge action-tile__badge--blue">{{ $confirmedTodayOrders->count() }}</span>
            @endif
        </button>
    </div>

    <!-- Stats Cards Row -->
    <div class="stats-row stats-row--4">
        <!-- Active Bookings Card -->
        <div class="stat-card stat-card--bookings card">
            <div class="block-header">
                <div class="block-title">
                    <span class="block-title__numbers">{{ $activeBookings->count() }}</span>
                    {{ __('бронирований комнат') }}
                </div>
                <img src="{{ asset('infinity/assets/icons/nav-calendar-icon.svg') }}" alt="" class="block-title__icon">
            </div>
            <div class="stat-card__content">
                @if($activeBookings->count() > 0)
                    <div class="bookings-list">
                        @foreach($activeBookings as $booking)
                        <div class="booking-badge">
                            <span class="booking-badge__room">{{ $booking->room->room_number ?? 'Room' }}</span>
                            <span class="booking-badge__time">{{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($booking->end_time)->format('H:i') }}</span>
                            <span class="booking-badge__date">{{ $booking->booking_date->format('d.m') }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <span class="stat-card__label stat-card__label--muted">{{ __('Нет активных') }}</span>
                @endif
            </div>
        </div>
        
        <!-- Weekly Orders Card -->
        <div class="stat-card stat-card--orders card">
            <div class="block-header">
                <div class="block-title">
                    <span class="block-title__numbers">{{ $weeklyOrders->count() }}</span>
                    {{ __('заказов') }}
                </div>
                <img src="{{ asset('infinity/assets/icons/nav-orders-icon.svg') }}" alt="" class="block-title__icon">
            </div>
            <div class="stat-card__content">
                @if($weeklyOrders->count() > 0)
                    <div class="orders-list">
                        @foreach($weeklyOrders as $order)
                        <div class="order-badge-mini order-badge-mini--{{ $order->status }}">
                            <span class="order-badge-mini__service">{{ $order->service->name ?? $order->service_name ?? 'Услуга' }}</span>
                            <span class="order-badge-mini__time">{{ $order->order_time ? \Carbon\Carbon::parse($order->order_time)->format('H:i') : '' }}</span>
                            <span class="order-badge-mini__date">{{ $order->order_date->format('d.m') }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <span class="stat-card__label stat-card__label--muted">{{ __('Нет заказов') }}</span>
                @endif
            </div>
        </div>
        
        <!-- My Clients Card -->
        <div class="stat-card stat-card--clients card">
            <div class="block-header">
                <div class="block-title">
                    <span class="block-title__numbers">{{ $clientsCount }}</span>
                    {{ __('клиентов') }}
                </div>
                <img src="{{ asset('infinity/assets/icons/nav-clients-icon.svg') }}" alt="" class="block-title__icon">
            </div>
            <div class="stat-card__content">
                @if($recentClientsData->count() > 0)
                    <div class="clients-list">
                        @foreach($recentClientsData as $item)
                        <a href="{{ route('masseuse.clients.show', $item->client) }}" class="client-badge">
                            <span class="client-badge__name">{{ $item->client->full_name }}</span>
                            <span class="client-badge__date">{{ $item->last_visit->format('d.m') }}</span>
                        </a>
                        @endforeach
                    </div>
                    <a href="{{ route('masseuse.clients.index') }}" class="text-link text-link--brand">{{ __('Смотреть всех') }} →</a>
                @else
                    <a href="{{ route('masseuse.clients.index') }}" class="text-link text-link--brand">{{ __('Смотреть всех') }} →</a>
                @endif
            </div>
        </div>
        
        <!-- Upcoming Duty Card -->
        <div class="stat-card card">
            <div class="block-header {{ $upcomingDuty ? 'block-header--warning' : '' }}">
                <div class="block-title">
                    @if($upcomingDuty)
                        <span class="block-title__numbers">{{ $upcomingDuty->duty_date->format('d.m') }}</span>
                        {{ __('дежурство') }}
                    @else
                        {{ __('Нет дежурств') }}
                    @endif
                </div>
                <img src="{{ asset('infinity/assets/icons/cleaning-icon.svg') }}" alt="" class="block-title__icon">
            </div>
            <div class="stat-card__content">
                @if($upcomingDuty)
                    <span class="stat-card__label">{{ $upcomingDuty->branch->name ?? '' }}</span>
                @else
                    <span class="stat-card__label stat-card__label--muted">{{ __('Расслабьтесь') }}</span>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions-row">
        <!-- Эти две кнопки показываются только на десктопе (на мобилке они сверху) -->
        <button type="button" class="action-tile action-tile--clickable action-tile--desktop-only" onclick="openClientArrivedModal()">
            <span class="action-tile__icon action-tile__icon--green">
                <img src="{{ asset('infinity/assets/icons/nav-clients-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('КЛИЕНТ ПРИШЁЛ') }}</span>
            @if($pendingTodayOrders->count() > 0)
            <span class="action-tile__badge">{{ $pendingTodayOrders->count() }}</span>
            @endif
        </button>
        
        <button type="button" class="action-tile action-tile--clickable action-tile--desktop-only" onclick="openCompleteOrderModal()">
            <span class="action-tile__icon action-tile__icon--blue">
                <img src="{{ asset('infinity/assets/icons/nav-orders-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('ЗАВЕРШИТЬ ЗАКАЗ') }}</span>
            @if($confirmedTodayOrders->count() > 0)
            <span class="action-tile__badge action-tile__badge--blue">{{ $confirmedTodayOrders->count() }}</span>
            @endif
        </button>
        
        <a href="{{ route('masseuse.schedule') }}" class="action-tile">
            <span class="action-tile__icon">
                <img src="{{ asset('infinity/assets/icons/nav-calendar-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('МОЁ РАСПИСАНИЕ') }}</span>
        </a>
        
        <a href="{{ route('masseuse.clients.create') }}" class="action-tile">
            <span class="action-tile__icon">
                <img src="{{ asset('infinity/assets/icons/nav-clients-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('ДОБАВИТЬ КЛИЕНТА') }}</span>
        </a>
        
        <a href="{{ route('masseuse.bookings.create') }}" class="action-tile">
            <span class="action-tile__icon">
                <img src="{{ asset('infinity/assets/icons/nav-branches-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('ЗАБРОНИРОВАТЬ КОМНАТУ') }}</span>
        </a>
    </div>
</section>

<!-- Modal: Клиент пришёл -->
<div id="clientArrivedModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>{{ __('Клиент пришёл') }}</h3>
            <button type="button" class="modal-close" onclick="closeClientArrivedModal()">&times;</button>
        </div>
        <div class="modal-body">
            @if($pendingTodayOrders->count() > 0)
                <div class="orders-modal-list">
                    @foreach($pendingTodayOrders as $order)
                    <div class="order-modal-item" id="pending-order-{{ $order->id }}">
                        <div class="order-modal-info">
                            <span class="order-modal-time">{{ $order->order_time ? \Carbon\Carbon::parse($order->order_time)->format('H:i') : '--:--' }}</span>
                            <span class="order-modal-service">{{ $order->service->name ?? $order->service_name ?? __('Услуга') }}</span>
                            <span class="order-modal-client">{{ $order->client->full_name ?? $order->client_name ?? __('Клиент') }}</span>
                        </div>
                        <div class="order-modal-actions">
                            <button type="button" class="btn btn--success btn--sm" data-order-id="{{ $order->id }}" data-status="confirmed" data-message="{{ __('Подтвердить визит клиента?') }}" onclick="confirmOrderStatus(this)">
                                {{ __('Пришёл') }}
                            </button>
                            <button type="button" class="btn btn--danger btn--sm" data-order-id="{{ $order->id }}" data-status="cancelled" data-message="{{ __('Отменить заказ?') }}" onclick="confirmOrderStatus(this)">
                                {{ __('Не придёт') }}
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="modal-empty">
                    <p>{{ __('Нет заказов в ожидании на сегодня') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal: Завершить заказ -->
<div id="completeOrderModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header modal-header--blue">
            <h3>{{ __('Завершить заказ') }}</h3>
            <button type="button" class="modal-close" onclick="closeCompleteOrderModal()">&times;</button>
        </div>
        <div class="modal-body">
            @if($confirmedTodayOrders->count() > 0)
                <div class="orders-modal-list">
                    @foreach($confirmedTodayOrders as $order)
                    <div class="order-modal-item" id="confirmed-order-{{ $order->id }}">
                        <div class="order-modal-info">
                            <span class="order-modal-time">{{ $order->order_time ? \Carbon\Carbon::parse($order->order_time)->format('H:i') : '--:--' }}</span>
                            <span class="order-modal-service">{{ $order->service->name ?? $order->service_name ?? __('Услуга') }}</span>
                            <span class="order-modal-client">{{ $order->client->full_name ?? $order->client_name ?? __('Клиент') }}</span>
                        </div>
                        <div class="order-modal-actions">
                            <button type="button" class="btn btn--brand btn--sm" data-order-id="{{ $order->id }}" data-status="completed" data-message="{{ __('Завершить этот заказ?') }}" onclick="confirmOrderStatus(this)">
                                {{ __('Завершить') }}
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="modal-empty">
                    <p>{{ __('Нет подтверждённых заказов на сегодня') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Modal: Подтверждение действия -->
<div id="confirmModal" class="modal-overlay confirm-modal-overlay" style="display: none;">
    <div class="modal-content confirm-modal-content">
        <div class="modal-header confirm-modal-header">
            <h3 id="confirmModalTitle">{{ __('Подтверждение') }}</h3>
            <button type="button" class="modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body confirm-modal-body">
            <p id="confirmModalMessage"></p>
        </div>
        <div class="modal-footer confirm-modal-footer">
            <button type="button" class="btn btn--outlined-dark" onclick="closeConfirmModal()">{{ __('Отмена') }}</button>
            <button type="button" class="btn btn--brand" id="confirmModalBtn" onclick="executeConfirmedAction()">{{ __('Подтвердить') }}</button>
        </div>
    </div>
</div>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse.css') }}">
@endpush

@push('scripts')
<script>
// Переменные для confirm modal
let pendingOrderId = null;
let pendingStatus = null;

function openClientArrivedModal() {
    document.getElementById('clientArrivedModal').style.display = 'flex';
}

function closeClientArrivedModal() {
    document.getElementById('clientArrivedModal').style.display = 'none';
}

function openCompleteOrderModal() {
    document.getElementById('completeOrderModal').style.display = 'flex';
}

function closeCompleteOrderModal() {
    document.getElementById('completeOrderModal').style.display = 'none';
}

function confirmOrderStatus(btn) {
    pendingOrderId = btn.dataset.orderId;
    pendingStatus = btn.dataset.status;
    const message = btn.dataset.message;
    
    document.getElementById('confirmModalMessage').textContent = message;
    document.getElementById('confirmModal').style.display = 'flex';
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
    pendingOrderId = null;
    pendingStatus = null;
}

function executeConfirmedAction() {
    if (!pendingOrderId || !pendingStatus) return;
    
    const orderId = pendingOrderId;
    const status = pendingStatus;
    
    closeConfirmModal();
    
    fetch('{{ url("masseuse/orders") }}/' + orderId + '/status', {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Удаляем элемент из списка
            const pendingEl = document.getElementById('pending-order-' + orderId);
            const confirmedEl = document.getElementById('confirmed-order-' + orderId);
            if (pendingEl) pendingEl.remove();
            if (confirmedEl) confirmedEl.remove();
            
            // Проверяем остались ли элементы
            checkEmptyModals();
            
            // Обновляем бейджи
            updateBadges();
        } else {
            showNotification(data.message || '{{ __("Ошибка") }}', 'error');
        }
    })
    .catch(error => {
        showNotification('{{ __("Ошибка соединения") }}', 'error');
    });
}

function updateBadges() {
    // Обновляем счётчики на кнопках
    const pendingCount = document.querySelectorAll('#clientArrivedModal .order-modal-item').length;
    const confirmedCount = document.querySelectorAll('#completeOrderModal .order-modal-item').length;
    
    document.querySelectorAll('.action-tile__badge').forEach(badge => {
        const parent = badge.closest('.action-tile');
        if (parent && parent.onclick && parent.onclick.toString().includes('ClientArrived')) {
            if (pendingCount > 0) {
                badge.textContent = pendingCount;
            } else {
                badge.style.display = 'none';
            }
        }
    });
}

function showNotification(message, type) {
    // Простое уведомление (можно заменить на toast)
    alert(message);
}

function checkEmptyModals() {
    const pendingList = document.querySelector('#clientArrivedModal .orders-modal-list');
    const confirmedList = document.querySelector('#completeOrderModal .orders-modal-list');
    
    if (pendingList && pendingList.children.length === 0) {
        pendingList.innerHTML = '<div class="modal-empty"><p>{{ __("Нет заказов в ожидании на сегодня") }}</p></div>';
    }
    
    if (confirmedList && confirmedList.children.length === 0) {
        confirmedList.innerHTML = '<div class="modal-empty"><p>{{ __("Нет подтверждённых заказов на сегодня") }}</p></div>';
    }
}

// Закрытие модалок по клику на overlay
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>
@endpush
