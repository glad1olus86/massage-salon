@extends('layouts.masseuse')

@section('page-title')
    {{ __('Редактировать заказ') }}
@endsection

@section('content')
<section class="form-section">
    <div class="card">
        <div class="block-header">
            <div class="block-title">{{ __('Редактирование заказа') }}</div>
        </div>
        
        <form action="{{ route('masseuse.orders.update', $order) }}" method="POST" class="form-content">
            @csrf
            @method('PUT')
            
            <div class="form-grid">
                <!-- Клиент -->
                <div class="form-group">
                    <label class="form-label">{{ __('Клиент из базы') }}</label>
                    <select name="client_id" class="form-select">
                        <option value="">{{ __('Выберите клиента') }}</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $order->client_id) == $client->id ? 'selected' : '' }}>
                                {{ $client->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Или введите имя') }}</label>
                    <input type="text" name="client_name" class="form-input" value="{{ old('client_name', $order->client_name) }}" placeholder="{{ __('Имя клиента') }}">
                </div>
                
                <!-- Услуга -->
                <div class="form-group">
                    <label class="form-label">{{ __('Услуга') }}</label>
                    <select name="service_id" class="form-select" id="service-select">
                        <option value="">{{ __('Выберите услугу') }}</option>
                        @foreach($services as $service)
                            @php
                                $price = $service->pivot->is_extra 
                                    ? ($service->extra_price ?? $service->price * 1.5) 
                                    : $service->price;
                            @endphp
                            <option value="{{ $service->id }}" data-price="{{ $price }}" {{ old('service_id', $order->service_id) == $service->id ? 'selected' : '' }}>
                                {{ $service->name }} {{ $service->pivot->is_extra ? '(Extra)' : '' }} - {{ number_format($price, 0, ',', ' ') }} CZK
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Или введите название') }}</label>
                    <input type="text" name="service_name" class="form-input" value="{{ old('service_name', $order->service_name) }}" placeholder="{{ __('Название услуги') }}">
                </div>
                
                <!-- Дата и время -->
                <div class="form-group">
                    <label class="form-label">{{ __('Дата') }} *</label>
                    <input type="date" name="order_date" class="form-input" value="{{ old('order_date', $order->order_date?->format('Y-m-d')) }}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Время') }}</label>
                    <input type="time" name="order_time" class="form-input" value="{{ old('order_time', $order->order_time) }}">
                </div>
                
                <!-- Финансы -->
                <div class="form-group">
                    <label class="form-label">{{ __('Сумма') }} (CZK) *</label>
                    <input type="number" name="amount" class="form-input" id="amount-input" value="{{ old('amount', $order->amount) }}" min="0" step="100" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Чаевые') }} (CZK)</label>
                    <input type="number" name="tip" class="form-input" value="{{ old('tip', $order->tip ?? 0) }}" min="0" step="50">
                </div>
                
                <!-- Способ оплаты и статус -->
                <div class="form-group">
                    <label class="form-label">{{ __('Способ оплаты') }}</label>
                    <select name="payment_method" class="form-select">
                        <option value="">{{ __('Не указан') }}</option>
                        @foreach($paymentMethods as $key => $label)
                            <option value="{{ $key }}" {{ old('payment_method', $order->payment_method) == $key ? 'selected' : '' }}>
                                {{ __($label) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Статус') }} *</label>
                    <select name="status" class="form-select" required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $order->status) == $key ? 'selected' : '' }}>
                                {{ __($label) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Примечания -->
                <div class="form-group form-group--full">
                    <label class="form-label">{{ __('Примечания') }}</label>
                    <textarea name="notes" class="form-textarea" rows="3" placeholder="{{ __('Дополнительная информация') }}">{{ old('notes', $order->notes) }}</textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="{{ route('masseuse.orders.index') }}" class="btn btn--outlined-dark">{{ __('Отмена') }}</a>
                <button type="submit" class="btn btn--dark">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/components.css') }}">
@include('infinity.partials.form-styles')
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service-select');
    const amountInput = document.getElementById('amount-input');
    
    serviceSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const price = selected.dataset.price;
        if (price) {
            amountInput.value = price;
        }
    });
});
</script>
@endpush
