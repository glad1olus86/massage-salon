@extends('layouts.operator')

@section('page-title')
    {{ __('Панель Оператора') }}
@endsection

@section('content')
<!-- Orders Section -->
<section class="new-orders card">
    <div class="block-header block-header--dark-red" style="justify-content: end;">
        <div class="block-title">
            <span class="block-title__numbers">{{ $ordersCount }}</span>
            {{ __('новых заказов за') }}
        </div>
        <div class="dropdown" data-dropdown>
            <button type="button" class="dropdown__trigger" aria-haspopup="listbox" aria-expanded="false">
                <span class="dropdown__value" data-dropdown-value>
                    @if($period == 'day') {{ __('актуальный день') }}
                    @elseif($period == 'week') {{ __('актуальную неделю') }}
                    @else {{ __('актуальный месяц') }}
                    @endif
                </span>
                <div class="arrow-button" aria-hidden="true">
                    <svg viewBox="0 0 7 5" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M0 1.98734V0L3.37859 2.7877L6.75719 0V1.98734L3.37859 4.77504L0 1.98734Z" fill="white" />
                    </svg>
                </div>
            </button>
            <div class="dropdown__menu" role="listbox" aria-label="{{ __('Период') }}">
                <button type="button" class="dropdown__option" role="option" data-value="day" onclick="changePeriod('day')">{{ __('актуальный день') }}</button>
                <button type="button" class="dropdown__option" role="option" data-value="week" onclick="changePeriod('week')">{{ __('актуальную неделю') }}</button>
                <button type="button" class="dropdown__option" role="option" data-value="month" onclick="changePeriod('month')">{{ __('актуальный месяц') }}</button>
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
                        <th scope="col">{{ __('Заработок') }}</th>
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
                        <td class="semibold"><span class="text-link--brand">{{ number_format($order->amount, 0) }}</span> Kč</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #888;">
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
                <span class="semibold table-sum">{{ __('ИТОГО ДОХОД ОПЕРАТОРА:') }} <span class="text-link--brand">{{ number_format($operatorCommission, 0) }} Kč</span></span>
            </div>
        </div>
    </div>
</section>

<!-- Duty Section -->
<section class="duty-wrapper">
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
                    @foreach($duty->week_days as $day)
                        <span class="duty-day {{ $day->has_duty ? ($day->is_completed ? 'duty-day--done' : 'duty-day--pending') : 'duty-day--empty' }}" 
                              title="{{ $day->date->format('d.m') }}{{ $day->has_duty ? ($day->is_completed ? ' ✓' : ' ○') : '' }}">
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
</section>

<!-- Performance Section -->
<div class="performance card">
    <div class="block-header">
        <div class="block-title">
            <span class="block-title__numbers">TOP 10</span>
            {{ __('сотрудников по эффективности за') }}
        </div>

        <div class="dropdown" data-dropdown>
            <button type="button" class="dropdown__trigger" aria-haspopup="listbox" aria-expanded="false">
                <span class="dropdown__value" data-dropdown-value>
                    @if($performancePeriod == 'day') {{ __('актуальный день') }}
                    @elseif($performancePeriod == 'week') {{ __('актуальную неделю') }}
                    @else {{ __('актуальный месяц') }}
                    @endif
                </span>
                <div class="arrow-button" aria-hidden="true">
                    <svg viewBox="0 0 7 5" fill="none">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M0 1.98734V0L3.37859 2.7877L6.75719 0V1.98734L3.37859 4.77504L0 1.98734Z" fill="white" />
                    </svg>
                </div>
            </button>
            <div class="dropdown__menu" role="listbox" aria-label="{{ __('Период') }}">
                <button type="button" class="dropdown__option" role="option" onclick="changePerformancePeriod('day')">{{ __('актуальный день') }}</button>
                <button type="button" class="dropdown__option" role="option" onclick="changePerformancePeriod('week')">{{ __('актуальную неделю') }}</button>
                <button type="button" class="dropdown__option" role="option" onclick="changePerformancePeriod('month')">{{ __('актуальный месяц') }}</button>
            </div>
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

@push('css-page')
<style>
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
    min-height: 280px;
    max-height: 280px;
    display: flex;
    flex-direction: column;
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
</script>
@endpush
