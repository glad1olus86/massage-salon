@extends('layouts.infinity')

@section('page-title')
    {{ __('Новый сотрудник') }}
@endsection

@section('content')
<section class="form-section">
    <div class="card">
        <div class="block-header">
            <a href="{{ route('infinity.employees.index') }}" class="back-link">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                {{ __('Назад') }}
            </a>
        </div>

        <div class="card__body">
            <form action="{{ route('infinity.employees.store') }}" method="POST" enctype="multipart/form-data" class="infinity-form">
                @csrf

                <div class="form-grid">
                    <div class="form-section-left">
                        <div class="avatar-upload">
                            <div class="avatar-preview" id="avatarPreview">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                            <label class="avatar-upload-btn">
                                <input type="file" name="avatar" accept="image/*" id="avatarInput" hidden>
                                {{ __('Загрузить фото') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-section-right">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">{{ __('Имя') }} <span class="required">*</span></label>
                                <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
                                @error('name')<span class="form-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Email') }} <span class="required">*</span></label>
                                <input type="email" name="email" class="form-input" value="{{ old('email') }}" required>
                                @error('email')<span class="form-error">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">{{ __('Пароль') }} <span class="required">*</span></label>
                                <input type="password" name="password" class="form-input" required>
                                @error('password')<span class="form-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Национальность') }}</label>
                                <div class="position-relative">
                                    <input type="text" name="nationality" class="form-input" id="nationality" 
                                        value="{{ old('nationality') }}" 
                                        placeholder="{{ __('Начните вводить...') }}" autocomplete="off">
                                    <div id="nationality_dropdown" class="nationality-dropdown" style="display: none;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">{{ __('Дата рождения') }}</label>
                                <input type="date" name="birth_date" class="form-input" value="{{ old('birth_date') }}">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Филиал') }} <span class="required">*</span></label>
                                <select name="branch_id" class="form-select" required>
                                    <option value="">{{ __('Выберите филиал') }}</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')<span class="form-error">{{ $message }}</span>@enderror
                            </div>
                        </div>

                        @if($services->count() > 0)
                        <div class="form-group form-group--full masseuse-field" id="mainServicesField">
                            <label class="form-label">{{ __('Основные услуги') }}</label>
                            <div class="services-checkboxes" id="regularServices">
                                @foreach($services as $service)
                                <label class="checkbox-card" data-service-id="{{ $service->id }}">
                                    <input type="checkbox" name="services[]" value="{{ $service->id }}" 
                                        class="regular-service-checkbox"
                                        {{ in_array($service->id, old('services', [])) ? 'checked' : '' }}>
                                    <span class="checkbox-card__content">
                                        <span class="checkbox-card__name">{{ $service->name }}</span>
                                        <span class="checkbox-card__price">{{ $service->formatted_price }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group form-group--full masseuse-field" id="extraServicesField">
                            <label class="form-label">{{ __('Экстра услуги') }}</label>
                            <div class="services-checkboxes" id="extraServices">
                                @foreach($services as $service)
                                <label class="checkbox-card checkbox-card--extra" data-service-id="{{ $service->id }}">
                                    <input type="checkbox" name="extra_services[]" value="{{ $service->id }}" 
                                        class="extra-service-checkbox"
                                        {{ in_array($service->id, old('extra_services', [])) ? 'checked' : '' }}>
                                    <span class="checkbox-card__content checkbox-card__content--extra">
                                        <span class="checkbox-card__name">{{ $service->name }}</span>
                                        <span class="checkbox-card__price">{{ $service->formatted_price }}</span>
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if(isset($operators) && $operators->count() > 0)
                        <div class="form-group masseuse-field" id="operatorField">
                            <label class="form-label">{{ __('Оператор') }}</label>
                            <select name="operator_id" class="form-select">
                                <option value="">{{ __('Не выбран') }}</option>
                                @foreach($operators as $operator)
                                    <option value="{{ $operator->id }}" {{ old('operator_id') == $operator->id ? 'selected' : '' }}>
                                        {{ $operator->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif

                        @if($roles->count() > 0)
                        <div class="form-group">
                            <label class="form-label">{{ __('Роль') }}</label>
                            <select name="role" class="form-select" id="roleSelect">
                                <option value="">{{ __('Не выбрана') }}</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role', request('role')) == $role->name ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn--dark sm-button sm-button--dark">
                        {{ __('Создать сотрудника') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('css-page')
<style>
.form-section { margin-top: 20px; }
.card__body { padding: 30px; }
.back-link { display: inline-flex; align-items: center; gap: 8px; color: #fff; text-decoration: none; font-size: 14px; font-weight: 500; opacity: 0.8; transition: opacity 0.2s; }
.back-link:hover { opacity: 1; }
.form-grid { display: grid; grid-template-columns: 200px 1fr; gap: 40px; }
.form-section-left { display: flex; flex-direction: column; align-items: center; }
.avatar-upload { text-align: center; }
.avatar-preview { width: 160px; height: 160px; border-radius: 16px; background: rgba(177, 32, 84, 0.1); display: flex; align-items: center; justify-content: center; margin-bottom: 16px; overflow: hidden; color: var(--brand-color); }
.avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
.avatar-upload-btn { display: inline-block; padding: 10px 20px; background: var(--brand-color); color: #fff; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition: opacity 0.2s; }
.avatar-upload-btn:hover { opacity: 0.9; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-group--full { grid-column: 1 / -1; }
.form-label { font-size: 14px; font-weight: 600; color: #333; }
.required { color: var(--brand-color); }
.form-input, .form-select { padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px; transition: border-color 0.2s; width: 100%; box-sizing: border-box; }
.form-input:focus, .form-select:focus { outline: none; border-color: var(--brand-color); }
.form-error { color: #dc3545; font-size: 13px; }
.services-checkboxes { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.checkbox-card { display: block; cursor: pointer; }
.checkbox-card input { display: none; }
.checkbox-card__content { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px; transition: all 0.2s; }
.checkbox-card input:checked + .checkbox-card__content { border-color: var(--brand-color); background: rgba(177, 32, 84, 0.05); }
.checkbox-card__content--extra { border-style: dashed; }
.checkbox-card input:checked + .checkbox-card__content--extra { border-color: #f5a623; background: rgba(245, 166, 35, 0.1); border-style: solid; }
.checkbox-card--disabled { opacity: 0.4; pointer-events: none; }
.checkbox-card__name { font-weight: 600; color: #333; }
.checkbox-card__price { font-size: 13px; color: #666; }
.form-actions { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
@media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .form-row { grid-template-columns: 1fr; } }
.position-relative { position: relative; width: 100%; }
.nationality-dropdown { position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 2px solid #e0e0e0; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); max-height: 200px; overflow-y: auto; z-index: 1050; margin-top: 4px; }
.nationality-item { padding: 10px 14px; cursor: pointer; transition: background 0.15s; display: flex; align-items: center; gap: 10px; }
.nationality-item:hover, .nationality-item.active { background: rgba(177, 32, 84, 0.1); }
.nationality-item img { width: 24px; height: 18px; object-fit: cover; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
</style>
@endpush

@push('scripts')
<script>
document.getElementById('avatarInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
        };
        reader.readAsDataURL(file);
    }
});

// Скрытие/показ полей в зависимости от роли
function toggleFieldsByRole() {
    const roleSelect = document.getElementById('roleSelect');
    if (!roleSelect) return;
    
    const isMasseuse = roleSelect.value === 'masseuse';
    const masseuseFields = document.querySelectorAll('.masseuse-field');
    
    masseuseFields.forEach(field => {
        field.style.display = isMasseuse ? 'flex' : 'none';
    });
}

// Инициализация при загрузке
document.addEventListener('DOMContentLoaded', function() {
    toggleFieldsByRole();
    
    const roleSelect = document.getElementById('roleSelect');
    if (roleSelect) {
        roleSelect.addEventListener('change', toggleFieldsByRole);
    }
    
    // Nationality Autocomplete
    (function() {
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
    })();
    
    // Логика взаимоисключения услуг
    document.querySelectorAll('.regular-service-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const serviceId = this.value;
            const extraCheckbox = document.querySelector('.extra-service-checkbox[value="' + serviceId + '"]');
            const extraCard = extraCheckbox.closest('.checkbox-card');
            
            if (this.checked) {
                extraCheckbox.checked = false;
                extraCard.classList.add('checkbox-card--disabled');
            } else {
                extraCard.classList.remove('checkbox-card--disabled');
            }
        });
        if (checkbox.checked) {
            const serviceId = checkbox.value;
            const extraCard = document.querySelector('.checkbox-card--extra[data-service-id="' + serviceId + '"]');
            if (extraCard) extraCard.classList.add('checkbox-card--disabled');
        }
    });

    document.querySelectorAll('.extra-service-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const serviceId = this.value;
            const regularCheckbox = document.querySelector('.regular-service-checkbox[value="' + serviceId + '"]');
            const regularCard = regularCheckbox.closest('.checkbox-card');
            
            if (this.checked) {
                regularCheckbox.checked = false;
                regularCard.classList.add('checkbox-card--disabled');
            } else {
                regularCard.classList.remove('checkbox-card--disabled');
            }
        });
        if (checkbox.checked) {
            const serviceId = checkbox.value;
            const regularCard = document.querySelector('#regularServices .checkbox-card[data-service-id="' + serviceId + '"]');
            if (regularCard) regularCard.classList.add('checkbox-card--disabled');
        }
    });
});
</script>
@endpush
