@extends('layouts.masseuse')

@section('page-title')
    {{ __('Мой дашборд') }}
@endsection

@section('content')
<section class="masseuse-dashboard-grid">
    <!-- Stats Cards Row -->
    <div class="stats-row stats-row--4">
        <!-- Today's Bookings Card -->
        <div class="stat-card card">
            <div class="block-header">
                <div class="block-title">
                    <span class="block-title__numbers">{{ $todayBookings->count() }}</span>
                    {{ __('бронирований') }}
                </div>
                <img src="{{ asset('infinity/assets/icons/nav-calendar-icon.svg') }}" alt="" class="block-title__icon">
            </div>
            <div class="stat-card__content">
                <span class="stat-card__label">{{ __('на сегодня') }}</span>
            </div>
        </div>
        
        <!-- Today's Orders Card -->
        <div class="stat-card card">
            <div class="block-header">
                <div class="block-title">
                    <span class="block-title__numbers">{{ $todayOrdersCount }}</span>
                    {{ __('заказов') }}
                </div>
                <img src="{{ asset('infinity/assets/icons/nav-orders-icon.svg') }}" alt="" class="block-title__icon">
            </div>
            <div class="stat-card__content">
                <span class="stat-card__label">{{ __('на сегодня') }}</span>
            </div>
        </div>
        
        <!-- My Clients Card -->
        <div class="stat-card card">
            <div class="block-header">
                <div class="block-title">
                    <span class="block-title__numbers">{{ $clientsCount }}</span>
                    {{ __('клиентов') }}
                </div>
                <img src="{{ asset('infinity/assets/icons/nav-clients-icon.svg') }}" alt="" class="block-title__icon">
            </div>
            <div class="stat-card__content">
                <a href="{{ route('masseuse.clients.index') }}" class="text-link text-link--brand">{{ __('Смотреть всех') }} →</a>
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
    
    <!-- Recent Orders Section -->
    @if($recentOrders->count() > 0)
    <div class="orders-card card">
        <div class="block-header">
            <div class="block-title">
                {{ __('Последние заказы') }}
            </div>
        </div>
        <div class="orders-card__content">
            <div class="orders-badges">
                @foreach($recentOrders as $order)
                <div class="order-badge">
                    <div class="order-badge__date">{{ $order->order_date->format('d.m') }}</div>
                    <div class="order-badge__info">
                        <span class="order-badge__client">{{ $order->client->full_name ?? __('Клиент') }}</span>
                        <span class="order-badge__service">{{ $order->service->name ?? __('Услуга') }}</span>
                    </div>
                    <div class="order-badge__price">{{ number_format($order->total_price, 0, ',', ' ') }} {{ __('CZK') }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    
    <!-- Today's Bookings Table -->
    <div class="bookings-card card">
        <div class="block-header">
            <div class="block-title">
                {{ __('Сегодняшние бронирования') }}
            </div>
            <a href="{{ route('masseuse.bookings.create') }}" class="dropdown__trigger" style="text-decoration: none;">
                <span>+ {{ __('Забронировать') }}</span>
            </a>
        </div>
        
        @if($todayBookings->count() > 0)
            <div class="bookings-card__content">
                <table class="table table--spacious">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Время') }}</th>
                            <th scope="col">{{ __('Комната') }}</th>
                            <th scope="col">{{ __('Клиент') }}</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todayBookings as $booking)
                        <tr>
                            <td>
                                <span class="booking-time">
                                    {{ \Carbon\Carbon::parse($booking->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($booking->end_time)->format('H:i') }}
                                </span>
                            </td>
                            <td>{{ $booking->room->room_number ?? __('Комната') }}</td>
                            <td>
                                @if($booking->client)
                                    <span class="text-link text-link--brand">{{ $booking->client->full_name }}</span>
                                @else
                                    <span class="text-muted">{{ __('Без клиента') }}</span>
                                @endif
                            </td>
                            <td class="booking-actions">
                                @if($booking->booking_date >= today())
                                    <form action="{{ route('masseuse.bookings.destroy', $booking) }}" method="POST" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-cancel" onclick="return confirm('{{ __('Отменить бронирование?') }}')" title="{{ __('Отменить') }}">
                                            <img src="{{ asset('infinity/assets/icons/cross-dark-icon.svg') }}" alt="{{ __('Отменить') }}">
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <p>{{ __('На сегодня нет бронирований') }}</p>
                <a href="{{ route('masseuse.bookings.create') }}" class="btn btn--dark">{{ __('Забронировать комнату') }}</a>
            </div>
        @endif
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions-row">
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
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse.css') }}">
@endpush
