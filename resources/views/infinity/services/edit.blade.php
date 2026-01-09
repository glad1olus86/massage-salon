@extends('layouts.infinity')

@section('page-title')
    {{ __('Редактировать услугу') }}
@endsection

@section('content')
<section class="page-section">
    <div class="card" style="max-width: 900px;">
        <div class="block-header">
            <div class="block-title">{{ __('Редактирование:') }} {{ $service->name }}</div>
        </div>
        <div class="form-content">
            <form action="{{ route('infinity.services.update', $service) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="form-group">
                    <label for="name" class="form-label">{{ __('Название услуги') }} <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-input @error('name') is-invalid @enderror" 
                           value="{{ old('name', $service->name) }}" required>
                    @error('name')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="price" class="form-label">{{ __('Базовая цена (CZK)') }} <span class="required">*</span></label>
                    <input type="number" id="price" name="price" class="form-input @error('price') is-invalid @enderror" 
                           value="{{ old('price', $service->price) }}" required min="0" step="100">
                    @error('price')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Цены по длительности (CZK)') }}</label>
                    <div class="duration-prices-grid">
                        <div class="form-field">
                            <label for="price_15" class="form-sublabel">15 {{ __('мин') }}</label>
                            <input type="number" id="price_15" name="price_15" class="form-input" 
                                   value="{{ old('price_15', $service->price_15) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="price_30" class="form-sublabel">30 {{ __('мин') }}</label>
                            <input type="number" id="price_30" name="price_30" class="form-input" 
                                   value="{{ old('price_30', $service->price_30) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="price_45" class="form-sublabel">45 {{ __('мин') }}</label>
                            <input type="number" id="price_45" name="price_45" class="form-input" 
                                   value="{{ old('price_45', $service->price_45) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="price_60" class="form-sublabel">60 {{ __('мин') }}</label>
                            <input type="number" id="price_60" name="price_60" class="form-input" 
                                   value="{{ old('price_60', $service->price_60) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="price_90" class="form-sublabel">90 {{ __('мин') }}</label>
                            <input type="number" id="price_90" name="price_90" class="form-input" 
                                   value="{{ old('price_90', $service->price_90) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="price_120" class="form-sublabel">120 {{ __('мин') }}</label>
                            <input type="number" id="price_120" name="price_120" class="form-input" 
                                   value="{{ old('price_120', $service->price_120) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="price_180" class="form-sublabel">180 {{ __('мин') }}</label>
                            <input type="number" id="price_180" name="price_180" class="form-input" 
                                   value="{{ old('price_180', $service->price_180) }}" min="0" step="50" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Доля оператора по длительности (CZK)') }}</label>
                    <div class="duration-prices-grid">
                        <div class="form-field">
                            <label for="operator_share_15" class="form-sublabel">15 {{ __('мин') }}</label>
                            <input type="number" id="operator_share_15" name="operator_share_15" class="form-input" 
                                   value="{{ old('operator_share_15', $service->operator_share_15) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="operator_share_30" class="form-sublabel">30 {{ __('мин') }}</label>
                            <input type="number" id="operator_share_30" name="operator_share_30" class="form-input" 
                                   value="{{ old('operator_share_30', $service->operator_share_30) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="operator_share_45" class="form-sublabel">45 {{ __('мин') }}</label>
                            <input type="number" id="operator_share_45" name="operator_share_45" class="form-input" 
                                   value="{{ old('operator_share_45', $service->operator_share_45) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="operator_share_60" class="form-sublabel">60 {{ __('мин') }}</label>
                            <input type="number" id="operator_share_60" name="operator_share_60" class="form-input" 
                                   value="{{ old('operator_share_60', $service->operator_share_60) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="operator_share_90" class="form-sublabel">90 {{ __('мин') }}</label>
                            <input type="number" id="operator_share_90" name="operator_share_90" class="form-input" 
                                   value="{{ old('operator_share_90', $service->operator_share_90) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="operator_share_120" class="form-sublabel">120 {{ __('мин') }}</label>
                            <input type="number" id="operator_share_120" name="operator_share_120" class="form-input" 
                                   value="{{ old('operator_share_120', $service->operator_share_120) }}" min="0" step="50" placeholder="0">
                        </div>
                        <div class="form-field">
                            <label for="operator_share_180" class="form-sublabel">180 {{ __('мин') }}</label>
                            <input type="number" id="operator_share_180" name="operator_share_180" class="form-input" 
                                   value="{{ old('operator_share_180', $service->operator_share_180) }}" min="0" step="50" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">{{ __('Описание') }}</label>
                    <textarea id="description" name="description" class="form-input form-textarea @error('description') is-invalid @enderror" 
                              rows="3">{{ old('description', $service->description) }}</textarea>
                    @error('description')
                        <span class="error-message">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label class="form-label">{{ __('Тип услуги') }}</label>
                    <div class="service-type-toggle">
                        <label class="radio-card">
                            <input type="radio" name="is_extra" value="0" {{ old('is_extra', $service->is_extra) ? '' : 'checked' }}>
                            <span class="radio-card__content">
                                <span class="radio-card__icon">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </span>
                                <span class="radio-card__text">{{ __('Обычная') }}</span>
                            </span>
                        </label>
                        <label class="radio-card radio-card--extra">
                            <input type="radio" name="is_extra" value="1" {{ old('is_extra', $service->is_extra) ? 'checked' : '' }}>
                            <span class="radio-card__content">
                                <span class="radio-card__icon">
                                    <svg width="28" height="28" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
                                    </svg>
                                </span>
                                <span class="radio-card__text">{{ __('Экстра') }}</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $service->is_active) ? 'checked' : '' }}>
                        <span class="checkbox-text">{{ __('Услуга активна') }}</span>
                    </label>
                </div>

                <div class="form-actions">
                    <a href="{{ route('infinity.services.index') }}" class="btn btn--outlined-dark sm-button sm-button--outlined-dark">
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
.form-group {
    margin-bottom: 24px;
}
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.form-row--thirds {
    grid-template-columns: 1fr 1fr 1fr;
    gap: 16px;
}
.duration-prices-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 12px;
}
.form-field {
    display: flex;
    flex-direction: column;
}
.form-sublabel {
    font-size: 13px;
    font-weight: 500;
    color: var(--accent-color);
    margin-bottom: 6px;
    opacity: 0.7;
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
    min-height: 80px;
}
.error-message {
    display: block;
    color: var(--danger-color);
    font-size: 13px;
    margin-top: 6px;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 12px 0;
}
.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    accent-color: var(--brand-color);
}
.checkbox-text {
    font-size: 16px;
    color: var(--accent-color);
}
.service-type-toggle {
    display: flex;
    gap: 12px;
}
.radio-card {
    flex: 1;
    cursor: pointer;
}
.radio-card input {
    display: none;
}
.radio-card__content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 16px 12px;
    border: 2px solid rgba(22, 11, 14, 0.15);
    border-radius: 10px;
    transition: all 0.2s;
}
.radio-card input:checked + .radio-card__content {
    border-color: var(--brand-color);
    background: rgba(177, 32, 84, 0.05);
}
.radio-card--extra input:checked + .radio-card__content {
    border-color: #f5a623;
    background: rgba(245, 166, 35, 0.1);
}
.radio-card__icon {
    font-size: 24px;
    color: var(--brand-color);
    display: flex;
    align-items: center;
    justify-content: center;
}
.radio-card--extra .radio-card__icon {
    color: #f5a623;
}
.radio-card__text {
    font-size: 14px;
    font-weight: 600;
    color: var(--accent-color);
}
.form-actions {
    display: flex;
    gap: 16px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid rgba(22, 11, 14, 0.1);
}
.form-actions .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 14px 28px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
    white-space: nowrap;
}
.form-actions .btn--outlined-dark {
    background: transparent;
    color: var(--accent-color);
    border: 2px solid var(--accent-color);
}
.form-actions .btn--outlined-dark:hover {
    background: var(--accent-color);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.form-actions .btn--dark {
    background: var(--accent-color);
    color: #fff;
    border: 2px solid var(--accent-color);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.form-actions .btn--dark:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}
@media (max-width: 900px) {
    .duration-prices-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    .form-row--thirds {
        grid-template-columns: 1fr 1fr 1fr;
    }
    .duration-prices-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    .form-actions {
        flex-direction: column;
    }
    .form-actions .btn {
        width: 100%;
        padding: 16px 24px;
    }
}
</style>
@endpush
