@extends('layouts.infinity')

@section('page-title')
    {{ __('Новый заказ') }}
@endsection

@section('content')
<section class="form-section">
    <div class="card">
        <div class="block-header">
            <div class="block-title">{{ __('Создание заказа') }}</div>
        </div>
        
        <form action="{{ route('infinity.orders.store') }}" method="POST" class="form-content">
            @csrf
            
            <div class="form-grid">
                <!-- Клиент -->
                <div class="form-group">
                    <label class="form-label">{{ __('Клиент из базы') }}</label>
                    <select name="client_id" class="form-select">
                        <option value="">{{ __('Выберите клиента') }}</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->full_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Или введите имя') }}</label>
                    <input type="text" name="client_name" class="form-input" value="{{ old('client_name') }}" placeholder="{{ __('Имя клиента') }}">
                </div>
                
                <!-- Сотрудник -->
                <div class="form-group">
                    <label class="form-label">{{ __('Массажистка') }}</label>
                    <select name="employee_id" class="form-select">
                        <option value="">{{ __('Выберите сотрудника') }}</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
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
                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
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
                                    data-price-15="{{ $service->price_15 }}"
                                    data-price-30="{{ $service->price_30 }}"
                                    data-price-45="{{ $service->price_45 }}"
                                    data-price-60="{{ $service->price_60 }}"
                                    data-price-90="{{ $service->price_90 }}"
                                    data-price-120="{{ $service->price_120 }}"
                                    data-price-180="{{ $service->price_180 }}"
                                    {{ old('service_id') == $service->id ? 'selected' : '' }}>
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
                        <option value="15" data-duration="15" {{ old('duration') == 15 ? 'selected' : '' }}>15 {{ __('минут') }}</option>
                        <option value="30" data-duration="30" {{ old('duration') == 30 ? 'selected' : '' }}>30 {{ __('минут') }}</option>
                        <option value="45" data-duration="45" {{ old('duration') == 45 ? 'selected' : '' }}>45 {{ __('минут') }}</option>
                        <option value="60" data-duration="60" {{ old('duration') == 60 ? 'selected' : '' }}>60 {{ __('минут') }}</option>
                        <option value="90" data-duration="90" {{ old('duration') == 90 ? 'selected' : '' }}>90 {{ __('минут') }}</option>
                        <option value="120" data-duration="120" {{ old('duration') == 120 ? 'selected' : '' }}>120 {{ __('минут') }}</option>
                        <option value="180" data-duration="180" {{ old('duration') == 180 ? 'selected' : '' }}>180 {{ __('минут') }}</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Или введите название') }}</label>
                    <input type="text" name="service_name" class="form-input" value="{{ old('service_name') }}" placeholder="{{ __('Название услуги') }}">
                </div>

                <!-- Дата и время -->
                <div class="form-group">
                    <label class="form-label">{{ __('Дата') }} *</label>
                    <input type="date" name="order_date" class="form-input" value="{{ old('order_date', date('Y-m-d')) }}" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Время') }}</label>
                    <input type="time" name="order_time" class="form-input" value="{{ old('order_time') }}">
                </div>
                
                <!-- Финансы -->
                <div class="form-group">
                    <label class="form-label">{{ __('Сумма') }} (CZK) *</label>
                    <input type="number" name="amount" class="form-input" id="amount-input" value="{{ old('amount', 0) }}" min="0" step="100" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Чаевые') }} (CZK)</label>
                    <input type="number" name="tip" class="form-input" value="{{ old('tip', 0) }}" min="0" step="50">
                </div>
                
                <!-- Способ оплаты и статус -->
                <div class="form-group">
                    <label class="form-label">{{ __('Способ оплаты') }}</label>
                    <select name="payment_method" class="form-select">
                        <option value="">{{ __('Не указан') }}</option>
                        @foreach($paymentMethods as $key => $label)
                            <option value="{{ $key }}" {{ old('payment_method') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">{{ __('Статус') }} *</label>
                    <select name="status" class="form-select" required>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" {{ old('status', 'pending') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Примечания -->
                <div class="form-group form-group--full">
                    <label class="form-label">{{ __('Примечания') }}</label>
                    <textarea name="notes" class="form-textarea" rows="3" placeholder="{{ __('Дополнительная информация') }}">{{ old('notes') }}</textarea>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="{{ route('infinity.orders.index') }}" class="btn btn--outlined-dark">{{ __('Отмена') }}</a>
                <button type="submit" class="btn btn--dark">{{ __('Создать заказ') }}</button>
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
    const amountInput = document.getElementById('amount-input');
    const durationSelect = document.getElementById('duration-select');
    
    function updateDurationOptions() {
        const selected = serviceSelect.options[serviceSelect.selectedIndex];
        const options = durationSelect.querySelectorAll('option[data-duration]');
        
        if (!selected || !selected.value) {
            // Если услуга не выбрана - показываем все
            options.forEach(opt => { opt.style.display = ''; opt.disabled = false; });
            return;
        }
        
        // Проверяем какие длительности имеют цену
        const durations = ['15', '30', '45', '60', '90', '120', '180'];
        let hasAnyPrice = false;
        
        durations.forEach(d => {
            const price = selected.getAttribute('data-price-' + d);
            if (price && parseFloat(price) > 0) hasAnyPrice = true;
        });
        
        options.forEach(opt => {
            const duration = opt.getAttribute('data-duration');
            const price = selected.getAttribute('data-price-' + duration);
            // Показываем если есть цена > 0, или если нет ни одной цены (показываем все)
            const show = !hasAnyPrice || (price && parseFloat(price) > 0);
            opt.style.display = show ? '' : 'none';
            opt.disabled = !show;
        });
        
        // Сбрасываем выбор если текущая длительность скрыта
        const currentOption = durationSelect.options[durationSelect.selectedIndex];
        if (currentOption && currentOption.disabled) {
            durationSelect.value = '';
        }
    }
    
    function updatePrice() {
        const serviceOption = serviceSelect.options[serviceSelect.selectedIndex];
        const duration = durationSelect.value;
        
        if (!serviceOption || !serviceOption.value) return;
        
        // Если выбрана длительность - берём цену для этой длительности
        if (duration) {
            const durationPrice = serviceOption.getAttribute('data-price-' + duration);
            if (durationPrice && parseFloat(durationPrice) > 0) {
                amountInput.value = durationPrice;
                return;
            }
        }
        
        // Иначе берём базовую цену
        const basePrice = serviceOption.getAttribute('data-price');
        if (basePrice) amountInput.value = basePrice;
    }
    
    serviceSelect.addEventListener('change', function() {
        updateDurationOptions();
        updatePrice();
    });
    
    durationSelect.addEventListener('change', function() {
        updatePrice();
    });
    
    updateDurationOptions();
});
</script>
@endpush
