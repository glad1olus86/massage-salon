@extends('layouts.masseuse')

@section('page-title')
    {{ __('Редактировать клиента') }}
@endsection

@section('content')
<div class="form-page">
    <form action="{{ route('masseuse.clients.update', $client) }}" method="POST" class="form-card">
        @csrf
        @method('PUT')
        
        <div class="form-grid">
            <div class="form-group">
                <label for="first_name" class="form-label">{{ __('Имя') }} *</label>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $client->first_name) }}" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="last_name" class="form-label">{{ __('Фамилия') }}</label>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $client->last_name) }}" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="phone" class="form-label">{{ __('Телефон') }}</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $client->phone) }}" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email', $client->email) }}" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="dob" class="form-label">{{ __('Дата рождения') }}</label>
                <input type="date" id="dob" name="dob" value="{{ old('dob', $client->dob?->format('Y-m-d')) }}" class="form-input">
            </div>
            
            <div class="form-group">
                <label for="nationality" class="form-label">{{ __('Национальность') }}</label>
                <div class="nationality-wrapper">
                    <input type="text" id="nationality" name="nationality" value="{{ old('nationality', $client->nationality) }}" class="form-input" autocomplete="off" placeholder="{{ __('Начните вводить...') }}">
                    <div id="nationality_dropdown" class="nationality-dropdown"></div>
                </div>
            </div>
            
            <div class="form-group form-group--full">
                <label for="preferred_service" class="form-label">{{ __('Предпочитаемая услуга') }}</label>
                <input type="text" id="preferred_service" name="preferred_service" value="{{ old('preferred_service', $client->preferred_service) }}" class="form-input">
            </div>
            
            <div class="form-group form-group--full">
                <label for="notes" class="form-label">{{ __('Заметки') }}</label>
                <textarea id="notes" name="notes" class="form-textarea" rows="3">{{ old('notes', $client->notes) }}</textarea>
            </div>
        </div>
        
        <div class="form-actions">
            <form action="{{ route('masseuse.clients.destroy', $client) }}" method="POST" style="display: inline;">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--danger" onclick="return confirm('{{ __('Удалить клиента?') }}')">{{ __('Удалить') }}</button>
            </form>
            <div style="flex: 1;"></div>
            <a href="{{ route('masseuse.clients.index') }}" class="btn btn--outlined-dark">{{ __('Отмена') }}</a>
            <button type="submit" class="btn btn--dark">{{ __('Сохранить') }}</button>
        </div>
    </form>
</div>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse.css') }}">
<style>
.nationality-wrapper {
    position: relative;
}
.nationality-dropdown {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 2px solid rgba(177, 32, 84, 0.2);
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    max-height: 200px;
    overflow-y: auto;
    z-index: 1050;
    margin-top: 4px;
}
.nationality-item {
    padding: 10px 14px;
    cursor: pointer;
    transition: background 0.15s;
    display: flex;
    align-items: center;
    gap: 10px;
}
.nationality-item:hover,
.nationality-item.active {
    background: rgba(177, 32, 84, 0.1);
}
.nationality-item img {
    width: 24px;
    height: 18px;
    object-fit: cover;
    border-radius: 2px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var nationalityInput = document.getElementById('nationality');
    var dropdown = document.getElementById('nationality_dropdown');
    
    if (!nationalityInput || !dropdown) return;
    
    var nationalities = {!! json_encode(\App\Services\NationalityService::getWithKeys()) !!};
    var selectedIndex = -1;
    
    nationalityInput.addEventListener('input', function() {
        var query = this.value.toLowerCase().trim();
        
        if (query.length < 1) {
            dropdown.style.display = 'none';
            return;
        }
        
        var matches = nationalities.filter(function(n) {
            return n.name.toLowerCase().indexOf(query) !== -1 || 
                   n.key.toLowerCase().indexOf(query) !== -1;
        }).slice(0, 8);
        
        if (matches.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        selectedIndex = -1;
        dropdown.innerHTML = matches.map(function(n, i) {
            var flagUrl = 'https://flagcdn.com/' + n.code.toLowerCase() + '.svg';
            return '<div class="nationality-item" data-key="' + n.key + '" data-index="' + i + '">' +
                '<img src="' + flagUrl + '" alt="' + n.code + '">' +
                '<span>' + n.name + '</span></div>';
        }).join('');
        dropdown.style.display = 'block';
        
        dropdown.querySelectorAll('.nationality-item').forEach(function(item) {
            item.addEventListener('click', function() {
                nationalityInput.value = this.dataset.key;
                dropdown.style.display = 'none';
            });
            item.addEventListener('mouseenter', function() {
                dropdown.querySelectorAll('.nationality-item').forEach(function(el) {
                    el.classList.remove('active');
                });
                this.classList.add('active');
                selectedIndex = parseInt(this.dataset.index);
            });
        });
    });
    
    nationalityInput.addEventListener('keydown', function(e) {
        var items = dropdown.querySelectorAll('.nationality-item');
        if (items.length === 0 || dropdown.style.display === 'none') return;
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, 0);
            updateSelection(items);
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            nationalityInput.value = items[selectedIndex].dataset.key;
            dropdown.style.display = 'none';
        } else if (e.key === 'Escape') {
            dropdown.style.display = 'none';
        }
    });
    
    function updateSelection(items) {
        items.forEach(function(item, i) {
            item.classList.toggle('active', i === selectedIndex);
        });
        if (selectedIndex >= 0) {
            items[selectedIndex].scrollIntoView({ block: 'nearest' });
        }
    }
    
    document.addEventListener('click', function(e) {
        if (!nationalityInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
});
</script>
@endpush
