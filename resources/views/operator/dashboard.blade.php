@extends('layouts.operator')

@section('page-title')
    {{ __('Панель Оператора') }}
@endsection

@section('content')
<!-- Orders Section -->
<section class="new-orders card">
    <div class="block-header block-header--dark-red">
        <div class="block-title">
            <span class="block-title__numbers">{{ $ordersCount }}</span>
            {{ __('новых заказов за') }}
        </div>
        <div class="header-actions">
            <div class="dropdown" data-dropdown>
                <button type="button" class="dropdown__trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="dropdown__value" data-dropdown-value>
                        @switch($period ?? 'week')
                            @case('day') {{ __('день') }} @break
                            @case('week') {{ __('неделю') }} @break
                            @case('month') {{ __('месяц') }} @break
                        @endswitch
                    </span>
                    <div class="arrow-button">
                        <svg viewBox="0 0 7 5" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0 1.98734V0L3.37859 2.7877L6.75719 0V1.98734L3.37859 4.77504L0 1.98734Z" fill="white" />
                        </svg>
                    </div>
                </button>
                <div class="dropdown__menu" role="listbox" aria-label="{{ __('Период') }}">
                    <button type="button" class="dropdown__option" role="option" data-value="day" onclick="changePeriod('day')" {{ ($period ?? '') == 'day' ? 'aria-selected=true' : '' }}>{{ __('день') }}</button>
                    <button type="button" class="dropdown__option" role="option" data-value="week" onclick="changePeriod('week')" {{ ($period ?? 'week') == 'week' ? 'aria-selected=true' : '' }}>{{ __('неделю') }}</button>
                    <button type="button" class="dropdown__option" role="option" data-value="month" onclick="changePeriod('month')" {{ ($period ?? '') == 'month' ? 'aria-selected=true' : '' }}>{{ __('месяц') }}</button>
                </div>
            </div>
        </div>
    </div>
    <div class="new-orders__content">
        <div class="new-orders__table-wrapper">
            <table class="table table--spacious orders-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Клиент') }}</th>
                        <th scope="col">{{ __('Дата') }}</th>
                        <th scope="col">{{ __('Услуга') }}</th>
                        <th scope="col">{{ __('Сотрудник') }}</th>
                        <th scope="col">{{ __('Длительность') }}</th>
                        <th scope="col">{{ __('Статус') }}</th>
                        <th scope="col">{{ __('Стоимость') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                    <tr>
                        <td>{{ $order->client?->full_name ?? $order->client_name ?? 'N/A' }}</td>
                        <td>{{ $order->order_date->format('d.m.Y') }}</td>
                        <td>{{ $order->service?->name ?? $order->service_name ?? 'N/A' }}</td>
                        <td><a class="text-link text-link--brand" href="#">{{ $order->employee?->name ?? 'N/A' }}</a></td>
                        <td>{{ $order->duration ?? '-' }} min</td>
                        <td>
                            <div class="status-dropdown" data-order-id="{{ $order->id }}">
                                <span class="order-status order-status--{{ $order->status }} order-status--clickable" onclick="toggleStatusDropdown(this)">
                                    {{ __(\App\Models\MassageOrder::getStatuses()[$order->status] ?? $order->status) }}
                                </span>
                                <div class="status-dropdown__menu">
                                    @foreach(\App\Models\MassageOrder::getStatuses() as $statusKey => $statusLabel)
                                        <button type="button" 
                                                class="status-dropdown__option status-dropdown__option--{{ $statusKey }} {{ $order->status === $statusKey ? 'is-active' : '' }}"
                                                onclick="changeOrderStatus({{ $order->id }}, '{{ $statusKey }}')">
                                            {{ __($statusLabel) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </td>
                        <td class="semibold"><span class="text-link--brand">{{ number_format($order->amount, 0) }}</span> Kč</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #888;">
                            {{ __('Нет заказов за выбранный период') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="table-footer">
                <div class="card-actions">
                    <button type="button" class="btn btn--dark sm-button sm-button--dark">{{ __('Новый заказ') }}</button>
                    <button type="button" class="btn btn--outlined-dark sm-button sm-button--outlined-dark">{{ __('Полная статистика заказов') }}</button>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Duty and Income Section -->
<section class="duty-income-wrapper">
    <!-- Duty Card -->
    <div class="duty card duty--fixed">
        <div class="block-header">
            <div class="block-title">
                {{ __('дежурные недели:') }}
                <span class="block-title__numbers">{{ $dutyCount }}</span>
            </div>
            <img src="{{ asset('infinity/assets/icons/cleaning-icon.svg') }}" alt="" class="block-title__icon">
        </div>

        <div class="duty__content duty__content--compact">
            @forelse($dutyEmployees ?? [] as $duty)
            <div class="duty-row">
                <div class="duty-row__employee">
                    @if($duty->has_avatar ?? false)
                        <img class="avatar avatar--xs" src="{{ $duty->avatar }}" alt="">
                    @else
                        <div class="avatar avatar--xs avatar--placeholder">{{ $duty->initials ?? '?' }}</div>
                    @endif
                    <span class="duty-row__name">{{ $duty->name ?? 'N/A' }}</span>
                </div>
                <div class="duty-row__days">
                    @foreach($duty->week_days as $dayIndex => $day)
                        <span class="duty-day {{ $day->has_duty ? ($day->is_completed ? 'duty-day--done' : 'duty-day--pending') : 'duty-day--empty' }}" 
                              title="{{ $day->date->format('d.m') }}{{ $day->has_duty ? ($day->is_completed ? ' ✓' : ' ○') : '' }}"
                              onclick="openDayModal('{{ $day->date->format('Y-m-d') }}', '{{ $duty->user_id }}', '{{ $duty->branch_id ?? '' }}')"
                              style="cursor: pointer;">
                        </span>
                    @endforeach
                </div>
                <div class="duty-row__branch">{{ $duty->branch ?? 'N/A' }}</div>
            </div>
            @empty
            <div class="duty-empty">
                {{ __('Нет дежурных на этой неделе') }}
            </div>
            @endforelse
        </div>
    </div>

    <!-- Operator Income Card -->
    <div class="income card income--fixed">
        <div class="block-header">
            <div class="block-title">
                {{ __('Доход оператора') }}
            </div>
            <svg class="block-title__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
        </div>

        <div class="income__total">
            <span class="income__label">{{ __('За текущий месяц:') }}</span>
            <span class="income__amount">{{ number_format($monthlyOperatorIncome, 0, ',', ' ') }} Kč</span>
        </div>

        <div class="income__orders">
            <span class="income__orders-title">{{ __('Последние заказы:') }}</span>
            @forelse($recentIncomeOrders as $order)
            <div class="income-order">
                <div class="income-order__info">
                    <span class="income-order__service">{{ $order->service?->name ?? 'N/A' }}</span>
                    <span class="income-order__employee">{{ $order->employee?->name ?? 'N/A' }}</span>
                </div>
                <span class="income-order__share">+{{ number_format($order->operator_share, 0) }} Kč</span>
            </div>
            @empty
            <div class="income-empty">{{ __('Нет заказов за этот месяц') }}</div>
            @endforelse
        </div>
    </div>
</section>

<!-- Performance Section -->
<div class="performance card">
    <div class="block-header block-header--wrap">
        <div class="block-title block-title--inline">
            <span class="block-title__numbers">TOP 10</span>
            <span class="block-title__text">{{ __('сотрудников по эффективности за') }} <div class="dropdown dropdown--inline" data-dropdown>
                <button type="button" class="dropdown__trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="dropdown__value" data-dropdown-value>
                        @if($performancePeriod == 'day') {{ __('день') }}
                        @elseif($performancePeriod == 'week') {{ __('неделю') }}
                        @else {{ __('месяц') }}
                        @endif
                    </span>
                    <div class="arrow-button" aria-hidden="true">
                        <svg viewBox="0 0 7 5" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0 1.98734V0L3.37859 2.7877L6.75719 0V1.98734L3.37859 4.77504L0 1.98734Z" fill="white" />
                        </svg>
                    </div>
                </button>
                <div class="dropdown__menu" role="listbox" aria-label="{{ __('Период') }}">
                    <button type="button" class="dropdown__option" role="option" onclick="changePerformancePeriod('day')">{{ __('день') }}</button>
                    <button type="button" class="dropdown__option" role="option" onclick="changePerformancePeriod('week')">{{ __('неделю') }}</button>
                    <button type="button" class="dropdown__option" role="option" onclick="changePerformancePeriod('month')">{{ __('месяц') }}</button>
                </div>
            </div></span>
        </div>
    </div>

    <div class="performance__content">
        <table class="table table--compact table--performance" aria-label="{{ __('Топ сотрудников') }}">
            <thead>
                <tr>
                    <th scope="col">{{ __('Сотрудник') }}</th>
                    <th scope="col">{{ __('Заказов') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topEmployees as $employee)
                <tr>
                    <td>
                        <div class="person performance-person">
                            @if($employee->has_avatar)
                                <img class="avatar avatar--sm" src="{{ $employee->avatar_url }}" alt="">
                            @else
                                <div class="avatar avatar--sm avatar--placeholder">{{ $employee->initials }}</div>
                            @endif
                            <a class="text-link text-link--brand text-link--lg semibold" href="#">{{ $employee->name }}</a>
                        </div>
                    </td>
                    <td class="lg-table-number">{{ $employee->orders_count }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="2" style="text-align: center; padding: 40px; color: #888;">
                        {{ __('Нет подопечных сотрудников') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

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

{{-- Add Booking Modal --}}
<div id="addBookingModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>{{ __('Добавить бронирование') }}</h3>
            <button type="button" class="modal-close" onclick="closeAddBookingModal()">&times;</button>
        </div>
        <form id="addBookingForm" onsubmit="submitBooking(event)">
            @csrf
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
                    <div class="form-group form-group--half">
                        <label class="form-label">{{ __('Время начала') }} <span class="required">*</span></label>
                        <input type="time" name="start_time" id="bookingStartTime" class="form-input" required>
                    </div>
                    <div class="form-group form-group--half">
                        <label class="form-label">{{ __('Время окончания') }} <span class="required">*</span></label>
                        <input type="time" name="end_time" id="bookingEndTime" class="form-input" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeAddBookingModal()">{{ __('Отмена') }}</button>
                <button type="submit" class="btn btn--brand">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </div>
</div>

@push('css-page')
<style>
/* Header actions с dropdown */
.header-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

/* Dropdown styles */
.dropdown {
    position: relative;
    display: inline-flex;
}

.dropdown__trigger {
    border: 0;
    cursor: pointer;
    background-color: var(--accent-color);
    color: #fff;
    font-size: 16px;
    border-radius: 10px;
    padding: 8px 16px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.dropdown__menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 150px;
    padding: 8px;
    border-radius: 10px;
    z-index: 10;
    background: var(--accent-color);
    display: none;
}

.dropdown.is-open .dropdown__menu {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.dropdown__option {
    border: 0;
    cursor: pointer;
    border-radius: 6px;
    padding: 8px 12px;
    text-align: left;
    background: transparent;
    color: #fff;
    font-size: 14px;
    text-decoration: none;
    display: block;
}

.dropdown__option:hover,
.dropdown__option[aria-selected="true"] {
    background: rgba(255, 255, 255, 0.1);
}

/* Inline dropdown в заголовке (для performance) */
.dropdown--inline {
    display: inline-flex;
    vertical-align: middle;
}

.dropdown--inline .dropdown__trigger {
    padding: 5px 10px;
    font-size: 14px;
    gap: 6px;
}

.block-title--inline {
    display: block;
}

.block-title--inline .block-title__text {
    display: block;
    margin-top: 4px;
}

.block-header--wrap {
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    /* Компактная кнопка на мобилке */
    .dropdown--inline .dropdown__trigger {
        padding: 4px 8px;
        font-size: 12px;
        gap: 4px;
    }
    
    .dropdown--inline .arrow-button svg {
        width: 7px;
        height: 5px;
    }
}

/* Avatar placeholder styles */
.avatar--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #8B1538 0%, #A91D4D 100%);
    color: white;
    font-weight: 600;
    font-size: 12px;
    border-radius: 50%;
}
.avatar--sm {
    width: 40px;
    height: 40px;
}

/* Duty card fixed height */
.duty--fixed {
    min-height: 420px;
    max-height: 420px;
    display: flex;
    flex-direction: column;
}

/* Duty and Income wrapper - два блока рядом */
.duty-income-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

@media (max-width: 900px) {
    .duty-income-wrapper {
        grid-template-columns: 1fr;
    }
}

/* Income card styles */
.income--fixed {
    min-height: 420px;
    max-height: 420px;
    display: flex;
    flex-direction: column;
}

.income__total {
    padding: 20px;
    background: linear-gradient(135deg, var(--brand-color) 0%, #A91D4D 100%);
    border-radius: 12px;
    margin: 16px;
    text-align: center;
}

.income__label {
    display: block;
    color: rgba(255,255,255,0.8);
    font-size: 14px;
    margin-bottom: 8px;
}

.income__amount {
    display: block;
    color: #fff;
    font-size: 32px;
    font-weight: 700;
}

.income__orders {
    flex: 1;
    overflow-y: auto;
    padding: 0 16px 16px;
}

.income__orders-title {
    display: block;
    font-size: 13px;
    color: #888;
    margin-bottom: 10px;
}

.income-order {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.income-order:last-child {
    border-bottom: none;
}

.income-order__info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.income-order__service {
    font-size: 14px;
    font-weight: 600;
    color: var(--accent-color);
}

.income-order__employee {
    font-size: 12px;
    color: #888;
}

.income-order__share {
    font-size: 16px;
    font-weight: 700;
    color: #22c55e;
}

.income-empty {
    text-align: center;
    padding: 20px;
    color: #888;
    font-size: 14px;
}

.duty__content--compact {
    flex: 1;
    overflow-y: auto;
    padding: 12px 16px;
}

/* Duty row styles */
.duty-row {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 10px 0;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.duty-row:last-child {
    border-bottom: none;
}
.duty-row__employee {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 130px;
    flex-shrink: 0;
}
.duty-row__name {
    font-size: 15px;
    font-weight: 600;
    color: var(--brand-color);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100px;
}
.duty-row__days {
    display: flex;
    gap: 6px;
    flex: 1;
    justify-content: center;
}
.duty-row__branch {
    font-size: 13px;
    color: var(--brand-color);
    min-width: 100px;
    text-align: right;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Duty day indicators */
.duty-day {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: inline-block;
    transition: transform 0.2s;
}
.duty-day:hover {
    transform: scale(1.2);
}
.duty-day--empty {
    background: #e5e7eb;
}
.duty-day--pending {
    background: #fbbf24;
}
.duty-day--done {
    background: #22c55e;
}

/* Avatar xs size */
.avatar--xs {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}
.avatar--xs.avatar--placeholder {
    font-size: 11px;
}

/* Duty empty state */
.duty-empty {
    text-align: center;
    padding: 40px 20px;
    color: #888;
    font-size: 14px;
}

/* Order status badges */
.order-status {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    white-space: nowrap;
}
.order-status--pending {
    background: rgba(234, 179, 8, 0.15);
    color: #ca8a04;
}
.order-status--confirmed {
    background: rgba(59, 130, 246, 0.15);
    color: #2563eb;
}
.order-status--completed {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
}
.order-status--cancelled {
    background: rgba(239, 68, 68, 0.15);
    color: #dc2626;
}

/* Status Dropdown */
.status-dropdown {
    position: relative;
    display: inline-block;
    z-index: 50;
}

.status-dropdown.is-open {
    z-index: 1000;
}

.order-status--clickable {
    cursor: pointer;
    transition: all 0.2s;
}

.order-status--clickable:hover {
    opacity: 0.8;
    transform: scale(1.05);
}

.status-dropdown__menu {
    position: fixed;
    min-width: 140px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    padding: 6px;
    z-index: 9999;
    display: none;
}

.status-dropdown.is-open .status-dropdown__menu {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.status-dropdown__option {
    border: none;
    background: transparent;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-align: left;
    transition: all 0.15s;
}

.status-dropdown__option:hover {
    background: rgba(0, 0, 0, 0.05);
}

.status-dropdown__option.is-active {
    background: rgba(177, 32, 84, 0.1);
}

.status-dropdown__option--completed { color: #16a34a; }
.status-dropdown__option--pending { color: #ca8a04; }
.status-dropdown__option--confirmed { color: #2563eb; }
.status-dropdown__option--cancelled { color: #dc2626; }

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 20px;
}

.modal-content {
    background: #fff;
    border-radius: 20px;
    width: 100%;
    max-width: 480px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalSlideIn 0.25s ease-out;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-content--large {
    max-width: 600px;
}

@keyframes modalSlideIn {
    from { opacity: 0; transform: translateY(-30px) scale(0.95); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    background: linear-gradient(135deg, var(--brand-color) 0%, #9a1c4a 100%);
    border-radius: 20px 20px 0 0;
}

.modal-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 700;
    color: #fff;
}

.modal-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    font-size: 24px;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
    line-height: 1;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.modal-body {
    padding: 24px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid #eee;
    background: #f9f9f9;
    border-radius: 0 0 20px 20px;
}

/* Day Details Content */
.day-details-section {
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.day-details-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.day-details-section h4 {
    font-size: 14px;
    font-weight: 700;
    color: var(--accent-color);
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--brand-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.bookings-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.booking-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: #fff;
    border: 2px solid rgba(177, 32, 84, 0.15);
    border-radius: 12px;
    flex-wrap: wrap;
}

.booking-time {
    font-weight: 700;
    font-size: 15px;
    color: var(--brand-color);
    min-width: 110px;
}

.booking-room {
    background: var(--brand-color);
    color: #fff;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.booking-user {
    flex: 1;
    font-weight: 500;
    font-size: 14px;
    color: #333;
}

/* Duty Info */
.duty-info {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(177, 32, 84, 0.08) 0%, rgba(177, 32, 84, 0.03) 100%);
    border: 2px solid rgba(177, 32, 84, 0.15);
    border-radius: 14px;
    flex-wrap: wrap;
}

.duty-person {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
}

.duty-avatar {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: var(--brand-color);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(177, 32, 84, 0.3);
}

.duty-details {
    flex: 1;
}

.duty-details-name {
    font-weight: 700;
    font-size: 17px;
    color: var(--accent-color);
    margin-bottom: 4px;
}

.duty-details-status {
    font-size: 13px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 6px;
}

.duty-actions-modal {
    display: flex;
    gap: 10px;
}

.no-duty-message {
    text-align: center;
    padding: 30px 20px;
    color: #888;
    background: #f9f9f9;
    border-radius: 12px;
}

/* Cleaning Statuses */
.cleaning-statuses {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 10px;
}

.cleaning-status-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    background: #fff;
    border: 2px solid #eee;
    border-radius: 10px;
}

.cleaning-status-name {
    font-weight: 600;
    font-size: 13px;
    color: #333;
}

.cleaning-status-toggle {
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}

.cleaning-status-toggle.clean {
    background: rgba(34, 197, 94, 0.15);
    color: #16a34a;
}

.cleaning-status-toggle.dirty {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

/* Employees Points List */
.employees-points-list {
    margin-top: 16px;
}

.employee-points-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f9f9f9;
    border-radius: 6px;
    margin-bottom: 6px;
    font-size: 13px;
}

.employee-points {
    font-weight: 600;
    color: var(--brand-color);
}

/* Modal Form Styles */
.form-group {
    margin-bottom: 16px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 6px;
}

.form-label .required {
    color: #ef4444;
}

.form-input {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--brand-color);
}

.form-row {
    display: flex;
    gap: 16px;
}

.form-group--half {
    flex: 1;
}

.btn--brand {
    background: linear-gradient(135deg, var(--brand-color) 0%, #9a1c4a 100%);
    color: #fff;
    padding: 11px 22px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    border: none;
}

.btn--secondary {
    background: #f3f4f6;
    color: #374151;
    border: 2px solid #e5e7eb;
    padding: 11px 22px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
}

.btn--sm {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 8px;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #888;
}
</style>
@endpush

@push('scripts')
<script>
function changePeriod(period) {
    const url = new URL(window.location.href);
    url.searchParams.set('period', period);
    window.location.href = url.toString();
}

function changePerformancePeriod(period) {
    const url = new URL(window.location.href);
    url.searchParams.set('performance_period', period);
    window.location.href = url.toString();
}

// Dropdown toggle
document.querySelectorAll('[data-dropdown]').forEach(dropdown => {
    const trigger = dropdown.querySelector('.dropdown__trigger');
    if (trigger) {
        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            // Закрываем все другие дропдауны
            document.querySelectorAll('[data-dropdown].is-open').forEach(d => {
                if (d !== dropdown) d.classList.remove('is-open');
            });
            dropdown.classList.toggle('is-open');
        });
    }
});

// Закрытие при клике вне дропдауна
document.addEventListener('click', function(e) {
    if (!e.target.closest('[data-dropdown]') && !e.target.closest('.status-dropdown')) {
        document.querySelectorAll('[data-dropdown].is-open').forEach(d => {
            d.classList.remove('is-open');
        });
        document.querySelectorAll('.status-dropdown.is-open').forEach(d => {
            d.classList.remove('is-open');
        });
    }
});

// Status dropdown functions
function toggleStatusDropdown(element) {
    const dropdown = element.closest('.status-dropdown');
    const menu = dropdown.querySelector('.status-dropdown__menu');
    
    // Закрываем все другие дропдауны
    document.querySelectorAll('.status-dropdown.is-open').forEach(d => {
        if (d !== dropdown) {
            d.classList.remove('is-open');
        }
    });
    
    // Переключаем текущий
    dropdown.classList.toggle('is-open');
    
    // Позиционируем меню
    if (dropdown.classList.contains('is-open')) {
        const rect = element.getBoundingClientRect();
        menu.style.top = (rect.bottom + 6) + 'px';
        menu.style.left = (rect.left + rect.width / 2 - 70) + 'px';
    }
}

function changeOrderStatus(orderId, status) {
    fetch('{{ url("operator/orders") }}/' + orderId + '/status', {
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
            window.location.reload();
        } else {
            alert(data.message || '{{ __("Ошибка при изменении статуса") }}');
        }
    })
    .catch(error => {
        alert('{{ __("Ошибка соединения") }}');
    });
}

// Day Details Modal Functions
let currentDate = null;
let currentBranchId = null;

function openDayModal(date, userId, branchId) {
    currentDate = date;
    currentBranchId = branchId;
    document.getElementById('dayDetailsModal').style.display = 'flex';
    document.getElementById('dayDetailsContent').innerHTML = '<div class="loading">{{ __("Загрузка...") }}</div>';
    
    // Используем тот же endpoint что и календарь оператора
    fetch('{{ url("operator/calendar/day") }}/' + date, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('dayDetailsTitle').textContent = data.formatted_date;
        renderDayDetails(data);
    })
    .catch(error => {
        document.getElementById('dayDetailsContent').innerHTML = '<div class="error" style="text-align: center; padding: 40px; color: #ef4444;">{{ __("Ошибка загрузки") }}</div>';
    });
}

function renderDayDetails(data) {
    let html = '';
    
    // Bookings section
    html += '<div class="day-details-section">';
    html += '<h4>{{ __("Бронирования") }} <button class="btn btn--brand btn--sm" onclick="openAddBookingModal(\'' + data.date + '\', ' + JSON.stringify(data.rooms || []).replace(/"/g, '&quot;') + ', ' + JSON.stringify(data.branchEmployees || []).replace(/"/g, '&quot;') + ')">+ {{ __("Добавить") }}</button></h4>';
    if (data.bookings && data.bookings.length > 0) {
        html += '<div class="bookings-list">';
        data.bookings.forEach(function(booking) {
            let roomNumber = booking.room && booking.room.room_number ? booking.room.room_number : 'N/A';
            html += '<div class="booking-item">';
            html += '<span class="booking-time">' + booking.start_time.substring(0,5) + ' - ' + booking.end_time.substring(0,5) + '</span>';
            html += '<span class="booking-room">{{ __("Комната") }} ' + roomNumber + '</span>';
            html += '<span class="booking-user">' + (booking.user ? booking.user.name : 'N/A') + '</span>';
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
        html += '<div class="duty-actions-modal">';
        html += '<button class="btn btn--secondary btn--sm" onclick="openChangeDutyModal(' + data.duty.id + ', ' + JSON.stringify(data.employees || []).replace(/"/g, '&quot;') + ')">{{ __("Сменить") }}</button>';
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
    let cleaningStatuses = data.duty && data.duty.cleaning_statuses ? data.duty.cleaning_statuses : [];
    if (cleaningStatuses.length > 0) {
        html += '<div class="day-details-section">';
        html += '<h4>{{ __("Статус уборки комнат") }}</h4>';
        html += '<div class="cleaning-statuses">';
        cleaningStatuses.forEach(function(status) {
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

function openChangeDutyModal(dutyId, employees) {
    document.getElementById('changeDutyId').value = dutyId;
    let select = document.getElementById('newDutyPerson');
    select.innerHTML = '<option value="">{{ __("Выберите сотрудника") }}</option>';
    
    let pointsList = '';
    if (employees && employees.length > 0) {
        employees.forEach(function(emp) {
            let userId = emp.user_id || emp.id;
            let userName = emp.user ? emp.user.name : emp.name;
            let points = emp.points || 0;
            select.innerHTML += '<option value="' + userId + '">' + userName + '</option>';
            pointsList += '<div class="employee-points-item"><span>' + userName + '</span><span class="employee-points">' + points + ' {{ __("баллов") }}</span></div>';
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
            if (currentDate) {
                openDayModal(currentDate, '', currentBranchId);
            }
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
            if (currentDate) {
                openDayModal(currentDate, '', currentBranchId);
            }
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
            if (currentDate) {
                openDayModal(currentDate, '', currentBranchId);
            }
        } else {
            alert(data.message || '{{ __("Ошибка") }}');
        }
    });
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
            if (currentDate) {
                openDayModal(currentDate, '', currentBranchId);
            }
        } else {
            alert(data.message || '{{ __("Ошибка при создании бронирования") }}');
        }
    })
    .catch(error => {
        alert('{{ __("Ошибка соединения") }}');
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
