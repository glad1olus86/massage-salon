@extends('layouts.infinity')

@section('page-title')
    {{ __('Филиалы') }}
@endsection

@section('content')
<section class="branches-wrapper">
    <div class="branches-list">
        @forelse($branches as $branch)
        <article class="card branch-card" aria-label="{{ __('Филиал') }} {{ $branch->name }}">
            <header class="block-header branch-card__header">
                <div class="block-title">
                    <a href="{{ route('infinity.branches.show', $branch) }}" class="branch-card__address-link">
                        <span class="block-title__numbers branch-card__address">{{ $branch->address ?? $branch->name }}</span>
                    </a>
                </div>
                <div class="branch-card__actions" aria-label="{{ __('Действия') }}">
                    <a href="{{ route('infinity.branches.edit', $branch) }}" class="sm-icon-button" aria-label="{{ __('Редактировать') }}">
                        <img src="{{ asset('infinity/assets/icons/pencil-icon.svg') }}" alt="" class="sm-icon-button__icon">
                    </a>
                    <form action="{{ route('infinity.branches.destroy', $branch) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('Удалить филиал?') }}')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="sm-icon-button" aria-label="{{ __('Удалить') }}">
                            <img src="{{ asset('infinity/assets/icons/cross-icon.svg') }}" alt="" class="sm-icon-button__icon">
                        </button>
                    </form>
                </div>
            </header>

            <div class="branch-card__body">
                <div class="branch-card__media" aria-label="{{ __('Фото филиала') }}">
                    @if($branch->photos && count($branch->photos) > 0)
                        <img class="branch-card__photo" src="{{ asset('storage/' . $branch->photos[0]) }}" alt="{{ __('Фото филиала') }}">
                        @if(count($branch->photos) > 1)
                            <span class="branch-card__photo-count">+{{ count($branch->photos) - 1 }}</span>
                        @endif
                    @else
                        <div class="branch-card__photo-placeholder">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                    @endif
                </div>

                <div class="branch-card__info" aria-label="{{ __('Информация о филиале') }}">
                    <div class="branch-card__group">
                        <div class="branch-card__label">{{ __('Kontakty') }}</div>
                        <div class="branch-card__value">GSM +WhatsApp</div>
                        <div class="branch-card__value">{{ $branch->phone ?? '+420 XXX XXX XXX' }}</div>
                    </div>

                    <div class="branch-card__group">
                        <div class="branch-card__label">{{ __('Adresa') }}</div>
                        <div class="branch-card__value">{{ $branch->address ?? '-' }}</div>
                        <div class="branch-card__value">{{ $branch->name }}</div>
                    </div>

                    <div class="branch-card__group">
                        <div class="branch-card__label">{{ __('Otevírací doba') }}</div>
                        <div class="branch-card__value">{{ __('každý den') }}</div>
                        <div class="branch-card__value">{{ $branch->working_hours ?? '9:00 - 23:00' }}</div>
                    </div>
                </div>

                <div class="branch-card__map" aria-label="{{ __('Карта') }}">
                    @if($branch->latitude && $branch->longitude)
                        <iframe title="{{ $branch->address }} {{ __('на карте') }}" loading="lazy" allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.openstreetmap.org/export/embed.html?bbox={{ $branch->longitude - 0.02 }}%2C{{ $branch->latitude - 0.01 }}%2C{{ $branch->longitude + 0.02 }}%2C{{ $branch->latitude + 0.01 }}&layer=mapnik&marker={{ $branch->latitude }}%2C{{ $branch->longitude }}"></iframe>
                    @elseif($branch->address)
                        <iframe title="{{ $branch->address }} {{ __('на карте') }}" loading="lazy" allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://www.google.com/maps?q={{ urlencode($branch->address) }}&output=embed"></iframe>
                    @else
                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: rgba(177, 32, 84, 0.1); color: var(--brand-color);">
                            {{ __('Адрес не указан') }}
                        </div>
                    @endif
                </div>
            </div>
        </article>
        @empty
        <div class="card" style="padding: 60px; text-align: center;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 20px; opacity: 0.5; color: var(--brand-color);">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            <p style="font-size: 18px; margin-bottom: 10px; color: #888;">{{ __('Филиалы не найдены') }}</p>
            <p style="font-size: 14px; margin-bottom: 20px; color: #888;">{{ __('Создайте первый филиал для начала работы') }}</p>
            <a href="{{ route('infinity.branches.create') }}" class="btn btn--dark sm-button sm-button--dark">
                {{ __('Создать филиал') }}
            </a>
        </div>
        @endforelse
    </div>
    
    @if($branches->count() > 0)
    <div style="margin-top: 20px; text-align: right;">
        <a href="{{ route('infinity.branches.create') }}" class="btn btn--dark sm-button sm-button--dark">
            + {{ __('Добавить филиал') }}
        </a>
    </div>
    @endif
</section>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/branches.css') }}">
<link rel="stylesheet" href="{{ asset('infinity/styles/components-responsive.css') }}">
<style>
.sm-icon-button {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: rgba(255, 255, 255, 0.15);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.sm-icon-button:hover {
    background: rgba(255, 255, 255, 0.25);
}
.sm-icon-button__icon {
    width: 18px;
    height: 18px;
}
.branch-card__photo-count {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: #fff;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
}
.branch-card__photo-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(177, 32, 84, 0.1);
    color: var(--brand-color);
}
.branch-card__address-link {
    text-decoration: none;
    color: inherit;
    cursor: pointer;
    transition: opacity 0.2s;
}
.branch-card__address-link:hover {
    opacity: 0.8;
}
</style>
@endpush
