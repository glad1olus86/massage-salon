@extends('layouts.infinity')

@section('page-title')
    {{ __('Панель администратора') }}
@endsection

@section('content')
<section class="dashboard-grid">
    <!-- New Orders Card -->
    <div class="new-orders card">
        <div class="block-header">
            <div class="block-title">
                <span class="block-title__numbers">{{ $ordersCount ?? 0 }}</span>
                {{ __('новых заказов за') }}
            </div>
            <div class="dropdown" data-dropdown data-period-dropdown>
                <button type="button" class="dropdown__trigger" aria-haspopup="listbox" aria-expanded="false">
                    <span class="dropdown__value" data-dropdown-value>
                        @if(($period ?? 'week') === 'day')
                            {{ __('день') }}
                        @elseif(($period ?? 'week') === 'month')
                            {{ __('месяц') }}
                        @else
                            {{ __('неделю') }}
                        @endif
                    </span>
                    <div class="arrow-button">
                        <svg viewBox="0 0 7 5" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M0 1.98734V0L3.37859 2.7877L6.75719 0V1.98734L3.37859 4.77504L0 1.98734Z" fill="white"/>
                        </svg>
                    </div>
                </button>
                <div class="dropdown__menu" role="listbox">
                    <button type="button" class="dropdown__option" role="option" data-value="day" {{ ($period ?? 'week') === 'day' ? 'aria-selected=true' : '' }} onclick="changePeriod('day')">{{ __('день') }}</button>
                    <button type="button" class="dropdown__option" role="option" data-value="week" {{ ($period ?? 'week') === 'week' ? 'aria-selected=true' : '' }} onclick="changePeriod('week')">{{ __('неделю') }}</button>
                    <button type="button" class="dropdown__option" role="option" data-value="month" {{ ($period ?? 'week') === 'month' ? 'aria-selected=true' : '' }} onclick="changePeriod('month')">{{ __('месяц') }}</button>
                </div>
            </div>
        </div>
        <div class="new-orders__content">
            <table class="table table--spacious orders-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Клиент') }}</th>
                        <th scope="col">{{ __('Дата') }}</th>
                        <th scope="col">{{ __('Услуга') }}</th>
                        <th scope="col">{{ __('Сотрудник') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders ?? [] as $order)
                    <tr>
                        <td>{{ $order->client_display_name }}</td>
                        <td>{{ $order->formatted_date }}</td>
                        <td>{{ $order->service_display_name }}</td>
                        <td><a class="text-link text-link--brand" href="{{ route('infinity.employees.edit', $order->employee_id ?? 0) }}">{{ $order->employee_display_name }}</a></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 40px; color: #888;">
                            {{ __('Нет заказов') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-actions card-actions--bottom">
            <a href="{{ route('infinity.orders.create') }}" class="btn btn--dark sm-button sm-button--dark">{{ __('Новый заказ') }}</a>
            <a href="{{ route('infinity.orders.index') }}" class="btn btn--outlined-dark sm-button sm-button--outlined-dark">{{ __('Полная статистика заказов') }}</a>
        </div>
    </div>
    
    <!-- Right Column -->
    <div class="duty-wrapper">
        <!-- Duty Card -->
        <div class="duty card duty--fixed">
            <div class="block-header">
                <div class="block-title">
                    {{ __('дежурные недели:') }}
                    <span class="block-title__numbers">{{ $dutyCount ?? 0 }}</span>
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

        <!-- Action Tiles -->
        <a href="#" class="action-tile">
            <span class="action-tile__icon">
                <img src="{{ asset('infinity/assets/icons/control-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('КОНТРОЛЬ УБОРКИ') }}</span>
        </a>

        <a href="{{ route('infinity.branches.index') }}" class="action-tile">
            <span class="action-tile__icon">
                <img src="{{ asset('infinity/assets/icons/nav-branches-icon.svg') }}" alt="">
            </span>
            <span class="action-tile__label">{{ __('УПРАВЛЕНИЕ ФИЛИАЛАМИ') }}</span>
        </a>
    </div>
</section>

<!-- Calendar Section -->
<section class="calendar-section">
    <div class="calendar-section-header">
        <h2 class="main-content__title">{{ __('Календарь') }}</h2>
        <a href="{{ route('infinity.calendar') }}" class="calendar-link">{{ __('Открыть полный календарь') }} →</a>
    </div>

    @if($selectedBranch && count($calendarData) > 0)
    <div class="calendar card" aria-label="Календарь">
        <div class="calendar__inner">
            <div class="calendar__header">
                <span class="calendar__month-title">{{ $startDate->translatedFormat('F Y') }}</span>
                @if($branches->count() > 1)
                <select id="dashboardBranchSelect" class="calendar__branch-select" onchange="changeDashboardBranch(this.value)">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ $selectedBranch && $selectedBranch->id == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
                @else
                <span class="calendar__branch-name">{{ $selectedBranch->name }}</span>
                @endif
            </div>
            
            <div class="calendar__weekdays" aria-hidden="true">
                <div class="calendar__weekday">{{ __('Понедельник') }}</div>
                <div class="calendar__weekday">{{ __('Вторник') }}</div>
                <div class="calendar__weekday">{{ __('Среда') }}</div>
                <div class="calendar__weekday">{{ __('Четверг') }}</div>
                <div class="calendar__weekday">{{ __('Пятница') }}</div>
                <div class="calendar__weekday">{{ __('Суббота') }}</div>
                <div class="calendar__weekday">{{ __('Воскресенье') }}</div>
            </div>

            <div class="calendar__grid" role="grid">
                @php
                    $firstDayOfMonth = $startDate->copy()->startOfMonth();
                    $startDayOfWeek = $firstDayOfMonth->dayOfWeekIso;
                @endphp

                {{-- Empty cells before first day --}}
                @for($i = 1; $i < $startDayOfWeek; $i++)
                    <div class="calendar__cell calendar__cell--inactive" role="gridcell"></div>
                @endfor

                {{-- Days of month --}}
                @foreach($calendarData as $dateKey => $dayData)
                    <div class="calendar__cell {{ $dayData['isToday'] ? 'calendar__cell--today' : '' }} {{ $dayData['isWeekend'] ? 'calendar__cell--weekend' : '' }}" role="gridcell">
                        <div class="calendar__date {{ $dayData['isToday'] ? 'calendar__date--today' : '' }}">{{ $dayData['day'] }}</div>
                        
                        <div class="calendar__meta">
                            @if($dayData['bookingsCount'] > 0)
                            <div class="calendar__label">{{ __('резервации') }}</div>
                            <div class="calendar__dots" aria-hidden="true">
                                @for($d = 0; $d < min($dayData['bookingsCount'], 5); $d++)
                                    <span class="calendar__dot"></span>
                                @endfor
                            </div>
                            @endif
                        </div>

                        @if($dayData['duty'])
                            @php $duty = $dayData['duty']; @endphp
                            <ul class="calendar__items" aria-label="События">
                                <li class="calendar__item">
                                    <span class="calendar__item-icon calendar__item-icon--cleaning" aria-hidden="true"></span>
                                    <span class="calendar__item-text">{{ $duty->user->name ?? 'N/A' }}</span>
                                    <span class="calendar__item-status calendar__item-status--{{ $duty->status === 'completed' ? 'ok' : 'bad' }}" aria-hidden="true">
                                        @if($duty->status === 'completed')
                                        <svg viewBox="0 0 20 20" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="white"/>
                                        </svg>
                                        @else
                                        <svg viewBox="0 0 20 20" fill="none">
                                            <circle cx="10" cy="10" r="4" fill="white"/>
                                        </svg>
                                        @endif
                                    </span>
                                </li>
                                @php
                                    $cleanCount = $duty->cleaningStatuses->where('status', 'clean')->count();
                                    $totalCount = $duty->cleaningStatuses->count();
                                @endphp
                                @if($totalCount > 0)
                                <li class="calendar__item">
                                    <span class="calendar__item-icon calendar__item-icon--control" aria-hidden="true"></span>
                                    <span class="calendar__item-text">{{ __('Уборка') }} {{ $cleanCount }}/{{ $totalCount }}</span>
                                    <span class="calendar__item-status calendar__item-status--{{ $cleanCount == $totalCount ? 'ok' : 'bad' }}" aria-hidden="true">
                                        @if($cleanCount == $totalCount)
                                        <svg viewBox="0 0 20 20" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M16.7071 6.29289C17.0976 6.68342 17.0976 7.31658 16.7071 7.70711L9.70711 14.7071C9.31658 15.0976 8.68342 15.0976 8.29289 14.7071L4.79289 11.2071C4.40237 10.8166 4.40237 10.1834 4.79289 9.79289C5.18342 9.40237 5.81658 9.40237 6.20711 9.79289L9 12.5858L15.2929 6.29289C15.6834 5.90237 16.3166 5.90237 16.7071 6.29289Z" fill="white"/>
                                        </svg>
                                        @else
                                        <svg viewBox="0 0 20 20" fill="none">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M6.29289 6.29289C6.68342 5.90237 7.31658 5.90237 7.70711 6.29289L10 8.58579L12.2929 6.29289C12.6834 5.90237 13.3166 5.90237 13.7071 6.29289C14.0976 6.68342 14.0976 7.31658 13.7071 7.70711L11.4142 10L13.7071 12.2929C14.0976 12.6834 14.0976 13.3166 13.7071 13.7071C13.3166 14.0976 12.6834 14.0976 12.2929 13.7071L10 11.4142L7.70711 13.7071C7.31658 14.0976 6.68342 14.0976 6.29289 13.7071C5.90237 13.3166 5.90237 12.6834 6.29289 12.2929L8.58579 10L6.29289 7.70711C5.90237 7.31658 5.90237 6.68342 6.29289 6.29289Z" fill="white"/>
                                        </svg>
                                        @endif
                                    </span>
                                </li>
                                @endif
                            </ul>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @else
    <div class="card">
        <div class="empty-state" style="padding: 60px 20px; text-align: center; color: #888;">
            <p>{{ __('Создайте филиал для отображения календаря') }}</p>
            <a href="{{ route('infinity.branches.create') }}" class="btn btn--dark" style="margin-top: 16px;">{{ __('Создать филиал') }}</a>
        </div>
    </div>
    @endif
</section>

<!-- Performance Section -->
<section class="performance-section">
    <h2 class="main-content__title">{{ __('Эффективность сотрудников') }}</h2>

    <div class="performance-grid">
        <div class="performance card">
            <div class="block-header">
                <div class="block-title">
                    <span class="block-title__numbers">TOP 10</span>
                    {{ __('сотрудников по эффективности за') }}
                </div>

                <div class="dropdown" data-dropdown>
                    <button type="button" class="dropdown__trigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="dropdown__value" data-dropdown-value>{{ __('неделю') }}</span>
                        <div class="arrow-button" aria-hidden="true">
                            <svg viewBox="0 0 7 5" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M0 1.98734V0L3.37859 2.7877L6.75719 0V1.98734L3.37859 4.77504L0 1.98734Z" fill="white"/>
                            </svg>
                        </div>
                    </button>
                    <div class="dropdown__menu" role="listbox" aria-label="Период">
                        <button type="button" class="dropdown__option" role="option" data-value="день">{{ __('день') }}</button>
                        <button type="button" class="dropdown__option" role="option" data-value="неделю" aria-selected="true">{{ __('неделю') }}</button>
                        <button type="button" class="dropdown__option" role="option" data-value="месяц">{{ __('месяц') }}</button>
                    </div>
                </div>
            </div>

            <div class="performance__content">
                <table class="table table--compact table--performance" aria-label="Топ сотрудников">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Сотрудник') }}</th>
                            <th scope="col">{{ __('Заказов') }}</th>
                            <th scope="col">{{ __('Уборка (коэфф.)') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topEmployees ?? [] as $employee)
                        <tr>
                            <td>
                                <div class="person performance-person">
                                    @if($employee->has_avatar)
                                        <img class="avatar avatar--sm" src="{{ $employee->avatar_url }}" alt="{{ $employee->name ?? '' }}">
                                    @else
                                        <div class="avatar avatar--sm avatar--placeholder">{{ $employee->initials ?? '?' }}</div>
                                    @endif
                                    <a class="text-link text-link--brand text-link--lg semibold" href="{{ route('infinity.employees.edit', $employee->id ?? 0) }}">{{ $employee->name ?? 'N/A' }}</a>
                                </div>
                            </td>
                            <td class="performance__metric">{{ $employee->orders_count ?? 0 }}</td>
                            <td class="performance__metric">{{ $employee->cleaning_coeff ?? 0 }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: #888;">
                                {{ __('Нет массажисток') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="leader card">
            <div class="leader__header">{{ __('Лидер') }}<br>{{ __('заказов') }}</div>
            <div class="leader__content">
                @if($leaderEmployee->has_avatar ?? false)
                    <img class="leader__image" src="{{ $leaderEmployee->avatar_url }}" alt="{{ __('Лидер заказов') }}">
                @else
                    <div class="leader__image leader__image--placeholder">{{ $leaderEmployee->initials ?? '-' }}</div>
                @endif
                <div class="leader__name">{{ strtoupper($leaderEmployee->name ?? '-') }}</div>
                @if(isset($leaderEmployee->orders_count) && $leaderEmployee->orders_count > 0)
                <div class="leader__stats">{{ $leaderEmployee->orders_count }} {{ __('заказов') }}</div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/dashboard.css') }}">
<style>
.duty-status-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    font-size: 10px;
    margin-left: 6px;
}
.duty-status-badge--done {
    background: rgba(34, 197, 94, 0.2);
    color: #16a34a;
}
.leader__stats {
    font-size: 14px;
    color: #666;
    margin-top: 4px;
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
.leader__image--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #8B1538 0%, #A91D4D 100%);
    color: white;
    font-weight: 700;
    font-size: 36px;
    border-radius: 8px;
    margin: 0 auto;
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

/* Calendar branch select */
.calendar__branch-select {
    padding: 6px 28px 6px 12px;
    font-size: 14px;
    font-weight: 500;
    color: var(--brand-color);
    background-color: white;
    border: 2px solid var(--brand-color);
    border-radius: 8px;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23B12054' stroke-width='2.5'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.calendar__branch-select:hover {
    border-color: var(--accent-color);
}
.calendar__branch-select:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(177, 32, 84, 0.15);
}
.calendar__branch-select option {
    color: var(--accent-color);
    background: white;
}
</style>
@endpush

@push('scripts')
<script>
function changePeriod(period) {
    window.location.href = '{{ route("infinity.dashboard") }}?period=' + period;
}

function changeDashboardBranch(branchId) {
    const url = new URL(window.location.href);
    url.searchParams.set('branch_id', branchId);
    window.location.href = url.toString();
}
</script>
@endpush
