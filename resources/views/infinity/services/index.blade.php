@extends('layouts.infinity')

@section('page-title')
    {{ __('Список доступных услуг') }}
@endsection

@section('content')
<section class="services" aria-label="{{ __('Список услуг') }}">
    <div class="services__panel">
        <!-- Regular Services Section -->
        <div class="services__section-header">
            <span class="services__section-name">{{ __('Основные услуги') }}</span>
        </div>

        @if($regularServices->count() > 0)
        <ul class="services__list">
            @foreach($regularServices as $service)
            <li class="services__row {{ $loop->last ? 'services__row--last' : '' }}" data-service-id="{{ $service->id }}">
                <span class="services__name">{{ $service->name }}</span>
                <div class="services__actions-inline">
                    <a href="{{ route('infinity.services.edit', $service) }}" class="services__action-btn" title="{{ __('Редактировать') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form action="{{ route('infinity.services.destroy', $service) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('Удалить услугу?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="services__action-btn" title="{{ __('Удалить') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </form>
                </div>
                <span class="services__price">{{ $service->formatted_price }}</span>
            </li>
            @endforeach
        </ul>
        @else
        <div class="services__empty">{{ __('Нет основных услуг') }}</div>
        @endif

        <!-- Extra Services Section -->
        <div class="services__section-header services__section-header--extra">
            <span class="services__section-name">{{ __('Экстра услуги') }}</span>
        </div>

        @if($extraServices->count() > 0)
        <ul class="services__list">
            @foreach($extraServices as $service)
            <li class="services__row {{ $loop->last ? 'services__row--last' : '' }}" data-service-id="{{ $service->id }}">
                <span class="services__name">{{ $service->name }}</span>
                <div class="services__actions-inline">
                    <a href="{{ route('infinity.services.edit', $service) }}" class="services__action-btn" title="{{ __('Редактировать') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </a>
                    <form action="{{ route('infinity.services.destroy', $service) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('Удалить услугу?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="services__action-btn" title="{{ __('Удалить') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </form>
                </div>
                <span class="services__price">{{ $service->formatted_price }}</span>
            </li>
            @endforeach
        </ul>
        @else
        <div class="services__empty">{{ __('Нет экстра услуг') }}</div>
        @endif

        <div class="services__actions">
            <a href="{{ route('infinity.services.create') }}" class="btn btn--brand lg-button">+ {{ __('Добавить') }}</a>
        </div>
    </div>
</section>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/services.css') }}">
<style>
.services__section-header {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    background: rgba(177, 32, 84, 0.15);
    border-left: 4px solid var(--brand-color);
    margin-top: 10px;
}
.services__section-header--extra {
    background: rgba(245, 166, 35, 0.15);
    border-left-color: #f5a623;
    margin-top: 20px;
}
.services__section-name {
    font-size: 14px;
    font-weight: 700;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.services__row {
    display: grid;
    grid-template-columns: 1fr auto auto;
    align-items: center;
    gap: 18px;
}
.services__actions-inline {
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.2s;
}
.services__row:hover .services__actions-inline {
    opacity: 1;
}
.services__action-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.services__action-btn:hover {
    background: rgba(255, 255, 255, 0.2);
}
.services__empty {
    padding: 20px;
    text-align: center;
    color: rgba(255,255,255,0.4);
    font-size: 14px;
}
.btn--brand {
    background: var(--brand-color);
    color: #fff;
    border: none;
}
.lg-button {
    height: 50px;
    padding: 0 30px;
    border-radius: 12px;
    font-size: 20px;
    font-weight: 600;
    cursor: pointer;
}
</style>
@endpush
