@extends('layouts.infinity')

@section('page-title')
    {{ __('Редактирование заказа') }}
@endsection

@section('content')
<section class="form-section">
    <div class="card">
        <div class="block-header">
            <div class="block-title">{{ __('Редактирование заказа') }} #{{ $order->id }}</div>
        </div>
        
        <form action="{{ route('infinity.orders.update', $order) }}" method="POST" class="form-content">
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
                
                <!-- Сотрудник -->
                <div class="form-group">
                    <label class="form-label">{{ __('Массажистка') }}</label>
                    <select name="employee_id" class="form-select">
                        <option value="">{{ __('Выберите сотрудника') }}</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id', $order->employee_id) == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Филиал -->
                <div class="form-group">
                    <label class="form-label">{{ __('Филиал') }}</label>
                    <select name="branch_id" class="form-select">
                        <option value="">{{ __('Выберите филиал') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('branch_id', $order->branch_id) == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Услуга -->
                <div class="form-group">
                    <label class="form-label">{{ __('Услуга из списка') }}</label>
                    <select name="service_id" class="form-select">
                        <option value="">{{ __('Выберите услугу') }}</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" {{ old('service_id', $order->service_id) == $service->id ? 'selected' : '' }}>
                                {{ $service->name }} ({{ $service->formatted_price }})
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
                    <input type="number" name="amount" class="form-input" value="{{ old('amount', $order->amount) }}" min="0" step="100" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Чаевые') }} (CZK)</label>
                    <input type="number" name="tip" class="form-input" value="{{ old('tip', $order->tip) }}" min="0" step="50">
                </div>
                
                <!-- Способ оплаты и статус -->
                <div class="form-group">
                    <label class="form-label">{{ __('Способ оплаты') }}</label>
                    <select name="payment_method" class="form-select">
                        <option value="">{{ __('Не указан') }}</option>
                        @foreach($paymentMethods as $key => $label)
                            <option value="{{ $key }}" {{ old('payment_method', $order->payment_method) == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Статус') }} *</label>
                    <select name="status" class="form-select" required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $order->status) == $key ? 'selected' : '' }}>
                                {{ $label }}
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
                <a href="{{ route('infinity.orders.index') }}" class="btn btn--outlined-dark">{{ __('Отмена') }}</a>
                <button type="submit" class="btn btn--dark">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('css-page')
@include('infinity.partials.form-styles')
@endpush
