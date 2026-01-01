@extends('layouts.infinity')

@section('page-title')
    {{ __('Профиль клиента') }}
@endsection

@section('content')
<div class="client-profile-page">
    <!-- Back Link -->
    <a href="{{ route('infinity.clients.index') }}" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 12H5M12 19l-7-7 7-7"/>
        </svg>
        {{ __('Назад к клиентам') }}
    </a>

    <div class="client-profile-grid">
        <!-- Client Info Card -->
        <div class="card client-info-card">
            <div class="client-header">
                <div class="client-avatar client-avatar--large">
                    {{ mb_substr($client->first_name, 0, 1) }}{{ mb_substr($client->last_name ?? '', 0, 1) }}
                </div>
                <div class="client-header__info">
                    <h1 class="client-name">{{ $client->full_name }}</h1>
                    <span class="client-status client-status--{{ $client->status }}">
                        {{ \App\Models\MassageClient::getStatuses()[$client->status] ?? $client->status }}
                    </span>
                </div>
                <a href="{{ route('infinity.clients.edit', $client) }}" class="client-edit-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <span>{{ __('Редактировать') }}</span>
                </a>
            </div>

            <div class="client-details">
                @if($client->phone)
                <div class="client-detail">
                    <span class="client-detail__label">{{ __('Телефон') }}</span>
                    <a href="tel:{{ $client->phone }}" class="client-detail__value client-detail__value--link">{{ $client->phone }}</a>
                </div>
                @endif

                @if($client->email)
                <div class="client-detail">
                    <span class="client-detail__label">{{ __('Email') }}</span>
                    <a href="mailto:{{ $client->email }}" class="client-detail__value client-detail__value--link">{{ $client->email }}</a>
                </div>
                @endif

                @if($client->dob)
                <div class="client-detail">
                    <span class="client-detail__label">{{ __('Дата рождения') }}</span>
                    <span class="client-detail__value">{{ $client->dob->format('d.m.Y') }} ({{ $client->dob->age }} {{ __('лет') }})</span>
                </div>
                @endif

                @if($client->nationality)
                <div class="client-detail">
                    <span class="client-detail__label">{{ __('Национальность') }}</span>
                    <span class="client-detail__value">{{ $client->nationality }}</span>
                </div>
                @endif

                @if($client->registration_date)
                <div class="client-detail">
                    <span class="client-detail__label">{{ __('Клиент с') }}</span>
                    <span class="client-detail__value">{{ $client->registration_date->format('d.m.Y') }}</span>
                </div>
                @endif
            </div>

            @if($client->notes)
            <div class="client-notes">
                <span class="client-notes__label">{{ __('Заметки') }}</span>
                <p class="client-notes__text">{{ $client->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Stats Cards -->
        <div class="client-stats-grid">
            <div class="stat-mini-card">
                <span class="stat-mini-card__value">{{ $stats['total_orders'] }}</span>
                <span class="stat-mini-card__label">{{ __('заказов') }}</span>
            </div>
            <div class="stat-mini-card">
                <span class="stat-mini-card__value">{{ number_format($stats['total_spent'], 0, ',', ' ') }}</span>
                <span class="stat-mini-card__label">{{ __('CZK потрачено') }}</span>
            </div>
            <div class="stat-mini-card">
                <span class="stat-mini-card__value">{{ number_format($stats['total_tips'], 0, ',', ' ') }}</span>
                <span class="stat-mini-card__label">{{ __('CZK чаевых') }}</span>
            </div>
            <div class="stat-mini-card">
                <span class="stat-mini-card__value">{{ number_format($stats['avg_order'], 0, ',', ' ') }}</span>
                <span class="stat-mini-card__label">{{ __('CZK средний чек') }}</span>
            </div>
        </div>
    </div>

    <!-- Favorite Services -->
    @if($favoriteServices->count() > 0)
    <div class="card">
        <div class="block-header">
            <div class="block-title">{{ __('Любимые услуги') }}</div>
        </div>
        <div class="favorite-services">
            @foreach($favoriteServices as $fav)
            <div class="favorite-service">
                <div class="favorite-service__info">
                    <span class="favorite-service__name">{{ $fav['service_name'] }}</span>
                    <span class="favorite-service__count">{{ $fav['count'] }} {{ __('раз') }}</span>
                </div>
                <span class="favorite-service__total">{{ number_format($fav['total'], 0, ',', ' ') }} CZK</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Orders History -->
    <div class="card">
        <div class="block-header">
            <div class="block-title">{{ __('История заказов') }}</div>
            <a href="{{ route('infinity.orders.create') }}?client_id={{ $client->id }}" class="header-action-btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                <span>{{ __('Новый заказ') }}</span>
            </a>
        </div>

        @if($orders->count() > 0)
        <div class="orders-history">
            <table class="table table--spacious">
                <thead>
                    <tr>
                        <th>{{ __('Дата') }}</th>
                        <th>{{ __('Услуга') }}</th>
                        <th>{{ __('Сумма') }}</th>
                        <th>{{ __('Чаевые') }}</th>
                        <th>{{ __('Статус') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>
                            <span class="order-date">{{ $order->order_date->format('d.m.Y') }}</span>
                            @if($order->order_time)
                            <span class="order-time">{{ \Carbon\Carbon::parse($order->order_time)->format('H:i') }}</span>
                            @endif
                        </td>
                        <td>{{ $order->service_display_name }}</td>
                        <td class="order-amount">{{ number_format($order->amount, 0, ',', ' ') }} CZK</td>
                        <td>
                            @if($order->tip > 0)
                            <span class="order-tip">+{{ number_format($order->tip, 0, ',', ' ') }} CZK</span>
                            @else
                            <span class="order-tip order-tip--none">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="order-status order-status--{{ $order->status }}">
                                {{ __(\App\Models\MassageOrder::getStatuses()[$order->status] ?? $order->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state">
            <p>{{ __('Заказов пока нет') }}</p>
            <a href="{{ route('infinity.orders.create') }}?client_id={{ $client->id }}" class="btn btn--outlined-dark">
                {{ __('Создать первый заказ') }}
            </a>
        </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="client-quick-actions">
        <a href="{{ route('infinity.orders.create') }}?client_id={{ $client->id }}" class="quick-action-btn quick-action-btn--primary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="12" y1="18" x2="12" y2="12"></line>
                <line x1="9" y1="15" x2="15" y2="15"></line>
            </svg>
            <span>{{ __('Создать заказ') }}</span>
        </a>
        <a href="{{ route('infinity.dashboard') }}" class="quick-action-btn quick-action-btn--secondary">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <span>{{ __('На главную') }}</span>
        </a>
    </div>
</div>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse.css') }}">
<style>
.client-profile-page { margin-top: 20px; }

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--brand-color);
    text-decoration: none;
    font-weight: 500;
    margin-bottom: 20px;
}
.back-link:hover { text-decoration: underline; }

.client-profile-grid {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 20px;
    margin-bottom: 20px;
}

.client-info-card { padding: 24px; }

.client-header {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.client-avatar--large {
    width: 72px;
    height: 72px;
    border-radius: 16px;
    background: var(--brand-color);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    flex-shrink: 0;
}

.client-header__info { flex: 1; }

.client-name {
    margin: 0 0 6px 0;
    font-size: 24px;
    font-weight: 700;
    color: var(--accent-color);
}

.client-edit-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    background: #fff;
    color: var(--accent-color);
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s;
    align-self: flex-start;
}

.client-edit-btn:hover {
    border-color: var(--brand-color);
    color: var(--brand-color);
    background: rgba(177, 32, 84, 0.05);
}

.client-edit-btn svg { flex-shrink: 0; }

.header-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 16px;
    background: var(--brand-color);
    color: #fff;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 13px;
    transition: all 0.2s;
}

.header-action-btn:hover { background: #9b1b4d; }
.header-action-btn svg { flex-shrink: 0; }

.client-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}
.client-status--active { background: rgba(34, 197, 94, 0.15); color: #16a34a; }
.client-status--vip { background: rgba(234, 179, 8, 0.15); color: #ca8a04; }
.client-status--blocked { background: rgba(239, 68, 68, 0.15); color: #dc2626; }

.client-details {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.client-detail__label {
    display: block;
    font-size: 12px;
    color: #888;
    margin-bottom: 4px;
}
.client-detail__value {
    font-size: 15px;
    font-weight: 500;
    color: var(--accent-color);
}
.client-detail__value--link {
    color: var(--brand-color);
    text-decoration: none;
}
.client-detail__value--link:hover { text-decoration: underline; }

.client-notes {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
.client-notes__label {
    display: block;
    font-size: 12px;
    color: #888;
    margin-bottom: 8px;
}
.client-notes__text {
    margin: 0;
    font-size: 14px;
    color: #555;
    line-height: 1.5;
}

.client-stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.stat-mini-card {
    background: #fff;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.stat-mini-card__value {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: var(--brand-color);
}
.stat-mini-card__label {
    font-size: 12px;
    color: #888;
}

.favorite-services { padding: 0 20px 20px; }

.favorite-service {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}
.favorite-service:last-child { border-bottom: none; }

.favorite-service__name { font-weight: 600; color: var(--accent-color); }
.favorite-service__count { font-size: 13px; color: #888; margin-left: 8px; }
.favorite-service__total { font-weight: 600; color: var(--brand-color); }

.orders-history { overflow-x: auto; }
.orders-history .table { width: 100%; }
.orders-history .table th { text-align: left; font-size: 14px; font-weight: 600; padding: 12px 20px; color: #666; }
.orders-history .table td { padding: 12px 20px; border-bottom: 1px solid #f0f0f0; vertical-align: middle; }
.orders-history .table tr:last-child td { border-bottom: none; }

.order-date { font-weight: 500; }
.order-time { display: block; font-size: 12px; color: #888; }
.order-amount { font-weight: 600; color: var(--accent-color); }
.order-tip { color: #16a34a; font-weight: 500; }
.order-tip--none { color: #ccc; }

.order-status { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
.order-status--completed { background: rgba(34, 197, 94, 0.15); color: #16a34a; }
.order-status--pending { background: rgba(234, 179, 8, 0.15); color: #ca8a04; }
.order-status--confirmed { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
.order-status--cancelled { background: rgba(239, 68, 68, 0.15); color: #dc2626; }

.client-quick-actions {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
    margin-top: 20px;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 18px 24px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.2s;
}

.quick-action-btn--primary { background: var(--brand-color); color: #fff; }
.quick-action-btn--primary:hover { background: #9b1b4d; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(177, 32, 84, 0.3); }
.quick-action-btn--secondary { background: #fff; color: var(--brand-color); border: 2px solid var(--brand-color); }
.quick-action-btn--secondary:hover { background: rgba(177, 32, 84, 0.05); transform: translateY(-2px); }
.quick-action-btn svg { flex-shrink: 0; }

@media (max-width: 900px) {
    .client-profile-grid { grid-template-columns: 1fr; }
    .client-stats-grid { grid-template-columns: repeat(4, 1fr); }
    .client-details { grid-template-columns: 1fr; }
}
@media (max-width: 600px) {
    .client-stats-grid { grid-template-columns: repeat(2, 1fr); }
    .client-header { flex-wrap: wrap; }
    .client-header .btn { width: 100%; margin-top: 12px; }
    .client-quick-actions { grid-template-columns: 1fr; }
}
</style>
@endpush
