@extends('layouts.operator')

@section('page-title')
    {{ __('Заказы') }}
@endsection

@section('content')
<section class="orders-section">
    <div class="card" aria-label="{{ __('Список заказов') }}">
        <div class="block-header">
            <div class="block-title">
                <span class="block-title__numbers">{{ $ordersCount ?? 0 }}</span>
                {{ __('заказов за') }}
            </div>
            <div class="header-actions">
                <div class="dropdown" data-dropdown>
                    <button type="button" class="dropdown__trigger" aria-haspopup="listbox" aria-expanded="false">
                        <span class="dropdown__value" data-dropdown-value>
                            @switch($period ?? 'week')
                                @case('day') {{ __('день') }} @break
                                @case('week') {{ __('неделю') }} @break
                                @case('month') {{ __('месяц') }} @break
                                @case('all') {{ __('все') }} @break
                            @endswitch
                        </span>
                        <div class="arrow-button">
                            <svg viewBox="0 0 7 5" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M0 1.98734V0L3.37859 2.7877L6.75719 0V1.98734L3.37859 4.77504L0 1.98734Z" fill="white" />
                            </svg>
                        </div>
                    </button>
                    <div class="dropdown__menu" role="listbox" aria-label="{{ __('Период') }}">
                        <a href="{{ route('operator.orders.index', ['period' => 'day']) }}" class="dropdown__option" role="option" {{ ($period ?? '') == 'day' ? 'aria-selected=true' : '' }}>{{ __('день') }}</a>
                        <a href="{{ route('operator.orders.index', ['period' => 'week']) }}" class="dropdown__option" role="option" {{ ($period ?? 'week') == 'week' ? 'aria-selected=true' : '' }}>{{ __('неделю') }}</a>
                        <a href="{{ route('operator.orders.index', ['period' => 'month']) }}" class="dropdown__option" role="option" {{ ($period ?? '') == 'month' ? 'aria-selected=true' : '' }}>{{ __('месяц') }}</a>
                        <a href="{{ route('operator.orders.index', ['period' => 'all']) }}" class="dropdown__option" role="option" {{ ($period ?? '') == 'all' ? 'aria-selected=true' : '' }}>{{ __('все') }}</a>
                    </div>
                </div>
                <a href="{{ route('operator.orders.create') }}" class="btn btn--dark sm-button sm-button--dark">
                    + {{ __('Новый заказ') }}
                </a>
            </div>
        </div>

        <div class="orders__content">
            @if(isset($orders) && $orders->count() > 0)
            <table class="table table--spacious orders-table">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Клиент') }}</th>
                        <th scope="col">{{ __('Дата') }}</th>
                        <th scope="col">{{ __('Услуга') }}</th>
                        <th scope="col">{{ __('Сотрудник') }}</th>
                        <th scope="col">{{ __('Сумма') }}</th>
                        <th scope="col">{{ __('Статус') }}</th>
                        <th scope="col">{{ __('Действия') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                    <tr>
                        <td>{{ $order->client_display_name }}</td>
                        <td>{{ $order->formatted_date }}</td>
                        <td>{{ $order->service_display_name }}</td>
                        <td><a class="text-link text-link--brand" href="#">{{ $order->employee_display_name }}</a></td>
                        <td class="order-amount">{{ $order->formatted_amount }}</td>
                        <td>
                            <span class="order-status order-status--{{ $order->status }}">
                                {{ \App\Models\MassageOrder::getStatuses()[$order->status] ?? $order->status }}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="{{ route('operator.orders.edit', $order) }}" class="action-btn action-btn--edit" title="{{ __('Редактировать') }}">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </a>
                                <form action="{{ route('operator.orders.destroy', $order) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('Удалить заказ?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="action-btn action-btn--delete" title="{{ __('Удалить') }}">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="pagination-wrapper">
                {{ $orders->withQueryString()->links() }}
            </div>
            @else
            <div style="padding: 60px; text-align: center; color: #888;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 20px; opacity: 0.5;">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <p style="font-size: 18px; margin-bottom: 10px;">{{ __('Заказов не найдено') }}</p>
                <p style="font-size: 14px; margin-bottom: 20px;">{{ __('Создайте первый заказ') }}</p>
                <a href="{{ route('operator.orders.create') }}" class="btn btn--dark sm-button sm-button--dark">
                    {{ __('Создать заказ') }}
                </a>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('css-page')
<style>
.orders-section { margin-top: 20px; }
.header-actions { display: flex; align-items: center; gap: 15px; }
.header-actions .btn { display: inline-flex; align-items: center; justify-content: center; text-decoration: none; white-space: nowrap; }
.orders__content { padding: 0; }
.orders-table { width: 100%; }
.orders-table thead th { text-align: left; font-size: 18px; font-weight: 600; color: #000; padding: 18px 20px 10px 20px; }
.orders-table tbody td { font-size: 15px; padding: 12px 20px; vertical-align: middle; border-bottom: 1px solid rgba(22, 11, 14, 0.05); }
.orders-table tbody tr:last-child td { border-bottom: none; }
.orders-table tbody tr:hover { background-color: rgba(177, 32, 84, 0.02); }
.order-amount { font-weight: 600; color: var(--accent-color); }
.order-status { display: inline-block; padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; }
.order-status--completed { background: rgba(34, 197, 94, 0.15); color: #16a34a; }
.order-status--pending { background: rgba(234, 179, 8, 0.15); color: #ca8a04; }
.order-status--confirmed { background: rgba(59, 130, 246, 0.15); color: #2563eb; }
.order-status--cancelled { background: rgba(239, 68, 68, 0.15); color: #dc2626; }
.action-buttons { display: flex; gap: 8px; }
.action-btn { display: flex; align-items: center; justify-content: center; width: 32px; height: 32px; border-radius: 6px; border: none; cursor: pointer; transition: all 0.2s; }
.action-btn--edit { background-color: rgba(177, 32, 84, 0.1); color: var(--brand-color); }
.action-btn--edit:hover { background-color: rgba(177, 32, 84, 0.2); }
.action-btn--delete { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; }
.action-btn--delete:hover { background-color: rgba(239, 68, 68, 0.2); }
.pagination-wrapper { padding: 20px; display: flex; justify-content: center; }
.dropdown { position: relative; display: inline-flex; }
.dropdown__trigger { border: 0; cursor: pointer; background-color: var(--accent-color); color: #fff; font-size: 16px; border-radius: 10px; padding: 8px 16px; display: flex; align-items: center; gap: 12px; }
.dropdown__menu { position: absolute; top: calc(100% + 8px); right: 0; min-width: 150px; padding: 8px; border-radius: 10px; z-index: 10; background: var(--accent-color); display: none; }
.dropdown.is-open .dropdown__menu { display: flex; flex-direction: column; gap: 4px; }
.dropdown__option { border: 0; cursor: pointer; border-radius: 6px; padding: 8px 12px; text-align: left; background: transparent; color: #fff; font-size: 14px; text-decoration: none; display: block; }
.dropdown__option:hover, .dropdown__option[aria-selected="true"] { background: rgba(255, 255, 255, 0.1); }
@media (max-width: 900px) { .orders-table { min-width: 800px; } .orders__content { overflow-x: auto; } }
</style>
@endpush
