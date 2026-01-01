@extends('layouts.masseuse')

@section('page-title')
    {{ __('Забронировать комнату') }}
@endsection

@section('content')
<div class="form-page">
    <form action="{{ route('masseuse.bookings.store') }}" method="POST" class="form-card" id="bookingForm">
        @csrf
        
        <div class="form-grid">
            <div class="form-group">
                <label for="room_id" class="form-label">{{ __('Комната') }} *</label>
                <select id="room_id" name="room_id" class="form-select" required>
                    <option value="">{{ __('Выберите комнату') }}</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                            {{ $room->room_number }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
            </div>
            
            <div class="form-group">
                <label for="start_time" class="form-label">{{ __('Время начала') }} *</label>
                <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '10:00') }}" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="end_time" class="form-label">{{ __('Время окончания') }} *</label>
                <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '12:00') }}" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="booking_date" class="form-label">{{ __('Дата начала') }} *</label>
                <input type="date" id="booking_date" name="booking_date" value="{{ old('booking_date', date('Y-m-d')) }}" class="form-input" min="{{ date('Y-m-d') }}" required>
            </div>
            
            <div class="form-group">
                <label for="end_date" class="form-label">{{ __('Дата окончания') }} *</label>
                <input type="date" id="end_date" name="end_date" value="{{ old('end_date', date('Y-m-d')) }}" class="form-input" min="{{ date('Y-m-d') }}" required>
                <small class="form-hint">{{ __('Максимум 7 дней') }}</small>
            </div>
            
            <div class="form-group form-group--full">
                <label for="client_id" class="form-label">{{ __('Клиент') }} ({{ __('необязательно') }})</label>
                <select id="client_id" name="client_id" class="form-select">
                    <option value="">{{ __('Без клиента') }}</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->full_name }} {{ $client->phone ? '(' . $client->phone . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="form-group form-group--full">
                <label for="notes" class="form-label">{{ __('Заметки') }}</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="3" placeholder="{{ __('Дополнительная информация...') }}">{{ old('notes') }}</textarea>
            </div>
        </div>
        
        <!-- Booking Summary -->
        <div id="bookingSummary" class="booking-summary" style="display: none;">
            <span class="booking-summary-text"></span>
        </div>
        
        <!-- Availability Check -->
        <div id="availabilityStatus" class="availability-status" style="display: none;">
            <span class="availability-icon"></span>
            <span class="availability-text"></span>
        </div>
        
        <div class="form-actions">
            <a href="{{ route('masseuse.dashboard') }}" class="btn btn--outlined-dark">{{ __('Отмена') }}</a>
            <div style="flex: 1;"></div>
            <button type="submit" class="btn btn--dark" id="submitBtn">{{ __('Забронировать') }}</button>
        </div>
    </form>
</div>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse.css') }}">
<style>
.booking-summary {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 16px;
    color: #0369a1;
    font-size: 14px;
}
.form-hint {
    display: block;
    margin-top: 4px;
    font-size: 12px;
    color: #6b7280;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bookingForm');
    const roomSelect = document.getElementById('room_id');
    const bookingDateInput = document.getElementById('booking_date');
    const endDateInput = document.getElementById('end_date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const statusDiv = document.getElementById('availabilityStatus');
    const summaryDiv = document.getElementById('bookingSummary');
    const submitBtn = document.getElementById('submitBtn');
    
    let checkTimeout;
    let endDateManuallyChanged = false;
    
    // Синхронизация end_date с booking_date
    bookingDateInput.addEventListener('change', function() {
        // Обновляем минимальное значение end_date
        endDateInput.min = this.value;
        
        // Если end_date не был изменён вручную или меньше booking_date, синхронизируем
        if (!endDateManuallyChanged || endDateInput.value < this.value) {
            endDateInput.value = this.value;
        }
        
        debounceCheck();
    });
    
    endDateInput.addEventListener('change', function() {
        endDateManuallyChanged = true;
        
        // Валидация: end_date не может быть раньше booking_date
        if (this.value < bookingDateInput.value) {
            this.value = bookingDateInput.value;
        }
        
        // Валидация: максимум 7 дней
        const start = new Date(bookingDateInput.value);
        const end = new Date(this.value);
        const diffDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        
        if (diffDays > 7) {
            const maxDate = new Date(start);
            maxDate.setDate(maxDate.getDate() + 6);
            this.value = maxDate.toISOString().split('T')[0];
            alert('{{ __("Максимальный период бронирования — 7 дней.") }}');
        }
        
        debounceCheck();
    });
    
    function updateSummary() {
        const startDate = new Date(bookingDateInput.value);
        const endDate = new Date(endDateInput.value);
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;
        
        if (!bookingDateInput.value || !endDateInput.value || !startTime || !endTime) {
            summaryDiv.style.display = 'none';
            return;
        }
        
        const diffDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
        
        if (diffDays > 1) {
            // Расчёт часов
            const [startH, startM] = startTime.split(':').map(Number);
            const [endH, endM] = endTime.split(':').map(Number);
            const hoursPerDay = (endH + endM/60) - (startH + startM/60);
            const totalHours = hoursPerDay * diffDays;
            
            summaryDiv.style.display = 'block';
            summaryDiv.querySelector('.booking-summary-text').textContent = 
                `{{ __("Бронирование на") }} ${diffDays} {{ __("дней") }} × ${hoursPerDay.toFixed(1)} {{ __("ч.") }} = ${totalHours.toFixed(1)} {{ __("ч. всего") }}`;
        } else {
            summaryDiv.style.display = 'none';
        }
    }
    
    function checkAvailability() {
        const roomId = roomSelect.value;
        const bookingDate = bookingDateInput.value;
        const endDate = endDateInput.value;
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;
        
        if (!roomId || !bookingDate || !endDate || !startTime || !endTime) {
            statusDiv.style.display = 'none';
            return;
        }
        
        updateSummary();
        
        // Show checking status
        statusDiv.style.display = 'flex';
        statusDiv.className = 'availability-status availability-status--checking';
        statusDiv.querySelector('.availability-text').textContent = '{{ __("Проверка доступности...") }}';
        
        fetch('{{ route("masseuse.bookings.check-range-availability") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                room_id: roomId,
                booking_date: bookingDate,
                end_date: endDate,
                start_time: startTime,
                end_time: endTime
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                statusDiv.className = 'availability-status availability-status--available';
                statusDiv.querySelector('.availability-text').textContent = data.message;
                submitBtn.disabled = false;
            } else {
                statusDiv.className = 'availability-status availability-status--unavailable';
                statusDiv.querySelector('.availability-text').textContent = data.message;
                submitBtn.disabled = true;
            }
        })
        .catch(error => {
            statusDiv.style.display = 'none';
            console.error('Error:', error);
        });
    }
    
    function debounceCheck() {
        clearTimeout(checkTimeout);
        checkTimeout = setTimeout(checkAvailability, 500);
    }
    
    roomSelect.addEventListener('change', debounceCheck);
    startTimeInput.addEventListener('change', function() {
        updateSummary();
        debounceCheck();
    });
    endTimeInput.addEventListener('change', function() {
        updateSummary();
        debounceCheck();
    });
    
    // Инициализация
    endDateInput.min = bookingDateInput.value;
});
</script>
@endpush
