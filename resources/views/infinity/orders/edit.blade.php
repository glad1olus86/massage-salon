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
                    <select name="service_id" class="form-select" id="service-select">
                        <option value="">{{ __('Выберите услугу') }}</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" 
                                    data-price="{{ $service->price }}"
                                    data-has-60="{{ $service->operator_share_60 !== null ? '1' : '0' }}"
                                    data-has-90="{{ $service->operator_share_90 !== null ? '1' : '0' }}"
                                    data-has-120="{{ $service->operator_share_120 !== null ? '1' : '0' }}"
                                    {{ old('service_id', $order->service_id) == $service->id ? 'selected' : '' }}>
                                {{ $service->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Длительность -->
                <div class="form-group">
                    <label class="form-label">{{ __('Длительность') }}</label>
                    <select name="duration" class="form-select" id="duration-select">
                        <option value="">{{ __('Выберите длительность') }}</option>
                        <option value="60" data-duration="60" {{ old('duration', $order->duration) == 60 ? 'selected' : '' }}>60 {{ __('минут') }}</option>
                        <option value="90" data-duration="90" {{ old('duration', $order->duration) == 90 ? 'selected' : '' }}>90 {{ __('минут') }}</option>
                        <option value="120" data-duration="120" {{ old('duration', $order->duration) == 120 ? 'selected' : '' }}>120 {{ __('минут') }}</option>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const serviceSelect = document.getElementById('service-select');
    const durationSelect = document.getElementById('duration-select');
    
    function updateDurationOptions() {
        const selected = serviceSelect.options[serviceSelect.selectedIndex];
        const options = durationSelect.querySelectorAll('option[data-duration]');
        
        if (!selected || !selected.value) {
            options.forEach(opt => { opt.style.display = ''; opt.disabled = false; });
            return;
        }
        
        const has60 = selected.getAttribute('data-has-60');
        const has90 = selected.getAttribute('data-has-90');
        const has120 = selected.getAttribute('data-has-120');
        const allEmpty = has60 === '0' && has90 === '0' && has120 === '0';
        
        options.forEach(opt => {
            const duration = opt.getAttribute('data-duration');
            let show = allEmpty;
            if (!allEmpty) {
                if (duration === '60') show = has60 === '1';
                else if (duration === '90') show = has90 === '1';
                else if (duration === '120') show = has120 === '1';
            }
            opt.style.display = show ? '' : 'none';
            opt.disabled = !show;
        });
    }
    
    serviceSelect.addEventListener('change', updateDurationOptions);
    updateDurationOptions();
});
</script>
@endpush
