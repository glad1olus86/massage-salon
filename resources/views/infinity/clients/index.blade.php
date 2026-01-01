@extends('layouts.infinity')

@section('page-title')
    {{ __('Мои клиенты') }}
@endsection

@section('content')
<section class="clients-section">
    <div class="card entity-list" aria-label="{{ __('Список клиентов') }}">
        <div class="block-header">
            <div class="block-title">
                <span class="block-title__numbers">{{ __('Последний 30 дней') }}</span>
            </div>
            <a href="{{ route('infinity.clients.create') }}" class="btn btn--dark sm-button sm-button--dark">
                <span style="font-size: 22px;">+</span> {{ __('Новый клиент') }}
            </a>
        </div>

        <div class="entity-list__body">
            @if($clients->count() > 0)
            <ul class="entity-rows">
                @foreach($clients as $client)
                <li class="entity-row">
                    <a href="{{ route('infinity.clients.show', $client) }}" class="entity-row__avatar-link">
                        @if($client->photo && \Storage::disk('public')->exists($client->photo))
                            <img class="entity-row__avatar" src="{{ asset('storage/' . $client->photo) }}" alt="{{ $client->full_name }}">
                        @else
                            <div class="entity-row__avatar entity-row__avatar--placeholder">
                                {{ mb_substr($client->first_name, 0, 1) }}{{ mb_substr($client->last_name, 0, 1) }}
                            </div>
                        @endif
                    </a>
                    <a href="{{ route('infinity.clients.show', $client) }}" class="entity-row__name-link">{{ strtoupper($client->first_name) }}</a>
                    @if($client->dob)
                        <span class="pill pill--brand">{{ $client->dob->age }} {{ __('лет') }}</span>
                    @endif
                    @if($client->nationality)
                        @php
                            $nationalityCode = \App\Services\NationalityService::getCodeByName($client->nationality);
                        @endphp
                        <span class="pill pill--brand pill--with-flag">
                            @if($nationalityCode)
                                <img src="https://flagcdn.com/{{ strtolower($nationalityCode) }}.svg" alt="{{ $client->nationality }}" class="pill__flag">
                            @endif
                            {{ __($client->nationality) }}
                        </span>
                    @endif
                    <div class="pill pill--brand entity-row__services">
                        @if($client->preferred_service)
                            {{ $client->preferred_service }}
                        @endif
                    </div>
                    <div class="entity-row__actions">
                        <a href="{{ route('infinity.clients.show', $client) }}" class="action-btn" title="{{ __('Открыть') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </a>
                        <a href="{{ route('infinity.clients.edit', $client) }}" class="action-btn" title="{{ __('Редактировать') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <form action="{{ route('infinity.clients.destroy', $client) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('Удалить клиента?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="action-btn" title="{{ __('Удалить') }}">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </form>
                    </div>
                </li>
                @endforeach
            </ul>
            
            <div class="pagination-wrapper">
                {{ $clients->withQueryString()->links() }}
            </div>
            @else
            <div style="padding: 60px; text-align: center; color: #888;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" style="margin-bottom: 20px; opacity: 0.5;">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                <p style="font-size: 18px; margin-bottom: 10px;">{{ __('Клиенты не найдены') }}</p>
                <p style="font-size: 14px; margin-bottom: 20px;">{{ __('Добавьте первого клиента') }}</p>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/components-responsive.css') }}">
<style>
.btn.btn--dark {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-decoration: none;
}
.entity-list__body {
    padding: 20px;
}
.entity-rows {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.entity-row {
    display: grid;
    grid-template-columns: 56px 140px 96px 140px minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px;
    padding: 14px 18px;
    background: #fff;
    border-radius: 12px;
    border: 2px solid var(--brand-color);
}
.entity-row__avatar {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    object-fit: cover;
}
.entity-row__avatar--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(177, 32, 84, 0.15);
    color: var(--brand-color);
    font-weight: 700;
    font-size: 18px;
}
.entity-row__name {
    font-size: 24px;
    font-weight: 700;
    color: var(--accent-color);
    letter-spacing: 0.5px;
}
.entity-row__name-link {
    font-size: 24px;
    font-weight: 700;
    color: var(--accent-color);
    letter-spacing: 0.5px;
    text-decoration: none;
    transition: color 0.2s;
}
.entity-row__name-link:hover {
    color: var(--brand-color);
}
.entity-row__avatar-link {
    display: block;
}
.pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
}
.pill--brand {
    background: var(--brand-color);
    color: #fff;
}
.pill--with-flag {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f0f0f0;
    color: #333;
}
.pill__flag {
    width: 20px;
    height: 14px;
    object-fit: cover;
    border-radius: 2px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
.entity-row__services {
    font-size: 13px;
    font-weight: 400;
    padding: 8px 14px;
    white-space: normal;
    line-height: 1.3;
    min-height: 40px;
}
.entity-row__actions {
    display: flex;
    gap: 8px;
}
.action-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: none;
    background: var(--accent-color);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s;
    text-decoration: none;
}
.action-btn:hover {
    opacity: 0.8;
}
.pagination-wrapper {
    padding: 20px 0 0;
    display: flex;
    justify-content: center;
}
</style>
@endpush
