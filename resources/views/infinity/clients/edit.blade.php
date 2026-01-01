@extends('layouts.infinity')

@section('page-title')
    {{ __('Редактировать клиента') }}
@endsection

@section('content')
<section class="page-section">
    <div class="card" style="max-width: 900px;">
        <div class="block-header">
            <div class="block-title">{{ __('Редактирование:') }} {{ $client->full_name }}</div>
        </div>
        <div class="form-content">
            <form action="{{ route('infinity.clients.update', $client) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="form-section">
                    <h3 class="form-section__title">{{ __('Основная информация') }}</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name" class="form-label">{{ __('Имя') }} <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-input @error('first_name') is-invalid @enderror" 
                                   value="{{ old('first_name', $client->first_name) }}" required>
                            @error('first_name')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="last_name" class="form-label">{{ __('Фамилия') }} <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-input @error('last_name') is-invalid @enderror" 
                                   value="{{ old('last_name', $client->last_name) }}" required>
                            @error('last_name')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone" class="form-label">{{ __('Телефон') }}</label>
                            <input type="tel" id="phone" name="phone" class="form-input @error('phone') is-invalid @enderror" 
                                   value="{{ old('phone', $client->phone) }}">
                            @error('phone')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">{{ __('Email') }}</label>
                            <input type="email" id="email" name="email" class="form-input @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $client->email) }}">
                            @error('email')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row form-row--3">
                        <div class="form-group">
                            <label for="dob" class="form-label">{{ __('Дата рождения') }}</label>
                            <input type="date" id="dob" name="dob" class="form-input @error('dob') is-invalid @enderror" 
                                   value="{{ old('dob', $client->dob?->format('Y-m-d')) }}">
                            @error('dob')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="gender" class="form-label">{{ __('Пол') }}</label>
                            <select id="gender" name="gender" class="form-input @error('gender') is-invalid @enderror">
                                <option value="">{{ __('Не указан') }}</option>
                                <option value="male" {{ old('gender', $client->gender) == 'male' ? 'selected' : '' }}>{{ __('Мужской') }}</option>
                                <option value="female" {{ old('gender', $client->gender) == 'female' ? 'selected' : '' }}>{{ __('Женский') }}</option>
                            </select>
                            @error('gender')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="nationality" class="form-label">{{ __('Национальность') }}</label>
                            <div class="position-relative">
                                <input type="text" id="nationality" name="nationality" class="form-input @error('nationality') is-invalid @enderror" 
                                       value="{{ old('nationality', $client->nationality) }}" placeholder="{{ __('Начните вводить...') }}" autocomplete="off">
                                <div id="nationality_dropdown" class="nationality-dropdown" style="display: none;"></div>
                            </div>
                            @error('nationality')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3 class="form-section__title">{{ __('Дополнительно') }}</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="preferred_service" class="form-label">{{ __('Предпочитаемая услуга') }}</label>
                            <input type="text" id="preferred_service" name="preferred_service" class="form-input @error('preferred_service') is-invalid @enderror" 
                                   value="{{ old('preferred_service', $client->preferred_service) }}">
                            @error('preferred_service')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="source" class="form-label">{{ __('Откуда узнал') }}</label>
                            <input type="text" id="source" name="source" class="form-input @error('source') is-invalid @enderror" 
                                   value="{{ old('source', $client->source) }}">
                            @error('source')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="status" class="form-label">{{ __('Статус') }}</label>
                            <select id="status" name="status" class="form-input @error('status') is-invalid @enderror">
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ old('status', $client->status) == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('status')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="photo" class="form-label">{{ __('Фото') }}</label>
                            @if($client->photo)
                                <div class="current-photo">
                                    <img src="{{ Storage::url($client->photo) }}" alt="{{ $client->full_name }}" class="current-photo__img">
                                </div>
                            @endif
                            <input type="file" id="photo" name="photo" class="form-input @error('photo') is-invalid @enderror" accept="image/*">
                            @error('photo')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="form-label">{{ __('Заметки') }}</label>
                        <textarea id="notes" name="notes" class="form-input form-textarea @error('notes') is-invalid @enderror" 
                                  rows="3">{{ old('notes', $client->notes) }}</textarea>
                        @error('notes')
                            <span class="error-message">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('infinity.clients.index') }}" class="btn btn--outlined-dark sm-button sm-button--outlined-dark">
                        {{ __('Отмена') }}
                    </a>
                    <button type="submit" class="btn btn--dark sm-button sm-button--dark">
                        {{ __('Сохранить изменения') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('css-page')
<style>
.form-content {
    padding: 30px;
}
.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(22, 11, 14, 0.1);
}
.form-section:last-of-type {
    border-bottom: none;
}
.form-section__title {
    font-size: 18px;
    font-weight: 600;
    color: var(--accent-color);
    margin-bottom: 20px;
}
.form-group {
    margin-bottom: 20px;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.form-row--3 {
    grid-template-columns: 1fr 1fr 1fr;
}
.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--accent-color);
    margin-bottom: 8px;
}
.form-label .required {
    color: var(--danger-color);
}
.form-input {
    width: 100%;
    padding: 12px 16px;
    font-size: 16px;
    border: 2px solid rgba(22, 11, 14, 0.15);
    border-radius: 10px;
    background-color: #fff;
    transition: border-color 0.2s;
}
.form-input:focus {
    outline: none;
    border-color: var(--brand-color);
}
.form-input.is-invalid {
    border-color: var(--danger-color);
}
.form-textarea {
    resize: vertical;
    min-height: 100px;
}
.error-message {
    display: block;
    color: var(--danger-color);
    font-size: 13px;
    margin-top: 6px;
}
.current-photo {
    margin-bottom: 10px;
}
.current-photo__img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
}
.form-actions {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(22, 11, 14, 0.1);
}
@media (max-width: 768px) {
    .form-row, .form-row--3 {
        grid-template-columns: 1fr;
    }
}
.position-relative { position: relative; width: 100%; }
.nationality-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 2px solid #e0e0e0; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-height: 200px; overflow-y: auto; z-index: 1050; margin-top: 4px; }
.nationality-item { padding: 10px 14px; cursor: pointer; transition: background 0.15s; display: flex; align-items: center; gap: 10px; }
.nationality-item:hover, .nationality-item.active { background: rgba(177, 32, 84, 0.1); }
.nationality-item img { width: 24px; height: 18px; object-fit: cover; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Nationality Autocomplete
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
