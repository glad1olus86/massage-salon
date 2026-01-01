@extends('layouts.masseuse')

@section('page-title')
    {{ __('Мои клиенты') }}
@endsection

@section('content')
<div class="clients-page">
    <!-- Header -->
    <div class="page-header">
        <form action="{{ route('masseuse.clients.index') }}" method="GET" class="search-form">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Поиск по имени или телефону...') }}" class="search-input">
            <button type="submit" class="btn btn--dark btn--sm btn--icon">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
                {{ __('Найти') }}
            </button>
        </form>
        <a href="{{ route('masseuse.clients.create') }}" class="btn btn--dark btn--icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="8.5" cy="7" r="4"></circle>
                <line x1="20" y1="8" x2="20" y2="14"></line>
                <line x1="23" y1="11" x2="17" y2="11"></line>
            </svg>
            {{ __('Добавить клиента') }}
        </a>
    </div>
    
    <!-- Clients List -->
    @if($clients->count() > 0)
        <div class="clients-grid">
            @foreach($clients as $client)
                <div class="client-card">
                    <a href="{{ route('masseuse.clients.show', $client) }}" class="client-card__avatar">
                        {{ mb_substr($client->first_name, 0, 1) }}{{ mb_substr($client->last_name ?? '', 0, 1) }}
                    </a>
                    <div class="client-card__info">
                        <a href="{{ route('masseuse.clients.show', $client) }}" class="client-card__name">{{ $client->full_name }}</a>
                        @if($client->phone)
                            <div class="client-card__phone">{{ $client->phone }}</div>
                        @endif
                        @if($client->preferred_service)
                            <div class="client-card__service">{{ $client->preferred_service }}</div>
                        @endif
                    </div>
                    <div class="client-card__actions">
                        <a href="{{ route('masseuse.clients.show', $client) }}" class="btn btn--dark btn--sm btn--icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            {{ __('Открыть') }}
                        </a>
                        <a href="{{ route('masseuse.clients.edit', $client) }}" class="btn btn--outlined-dark btn--sm btn--icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                            {{ __('Редактировать') }}
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        
        <div class="pagination-wrapper">
            {{ $clients->links() }}
        </div>
    @else
        <div class="card">
            <div class="empty-state">
                <p>{{ __('У вас пока нет клиентов') }}</p>
                <a href="{{ route('masseuse.clients.create') }}" class="btn btn--dark btn--icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    {{ __('Добавить первого клиента') }}
                </a>
            </div>
        </div>
    @endif
</div>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse.css') }}">
@endpush
