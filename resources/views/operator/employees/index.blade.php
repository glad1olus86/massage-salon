@extends('layouts.operator')

@section('page-title')
    {{ __('Мои сотрудники') }}
@endsection

@section('content')
<section class="employees-section">
    <div class="card entity-list" aria-label="{{ __('Список сотрудников') }}">
        <div class="block-header">
            <div class="block-title">
                <span class="block-title__numbers">{{ __('Мои сотрудники') }}</span>
            </div>
            <a href="{{ route('operator.employees.create') }}" class="btn btn--dark sm-button sm-button--dark">
                + {{ __('Новый сотрудник') }}
            </a>
        </div>

        <div class="entity-list__body">
            @if($employees->count() > 0)
            <ul class="entity-rows">
                @foreach($employees as $employee)
                <li class="entity-row">
                    @if($employee->avatar && \Storage::disk('public')->exists($employee->avatar))
                        <img class="entity-row__avatar" src="{{ asset('storage/' . $employee->avatar) }}" alt="{{ $employee->name }}">
                    @else
                        <div class="entity-row__avatar entity-row__avatar--placeholder">
                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="entity-row__name">{{ strtoupper($employee->name) }}</div>
                    @if($employee->age)
                        <span class="pill pill--brand">{{ $employee->age }} {{ __('лет') }}</span>
                    @else
                        <span class="pill pill--muted">-</span>
                    @endif
                    @if($employee->nationality)
                        <span class="pill pill--flag">
                            {!! \App\Services\NationalityFlagService::getFlagHtml($employee->nationality, 20) !!}
                            <span class="nationality-name">{{ __($employee->nationality) }}</span>
                        </span>
                    @else
                        <span class="pill pill--muted">-</span>
                    @endif
                    <div class="entity-row__services">
                        @if($employee->massageServices->count() > 0)
                            <span class="services-badge">{{ $employee->massageServices->pluck('name')->implode(', ') }}</span>
                        @else
                            <span class="services-badge services-badge--empty">{{ __('Нет услуг') }}</span>
                        @endif
                    </div>
                    <div class="entity-row__actions">
                        <a href="{{ route('operator.employees.edit', $employee) }}" class="action-btn" title="{{ __('Редактировать') }}">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                            </svg>
                        </a>
                        <form action="{{ route('operator.employees.destroy', $employee) }}" method="POST" style="display: inline;" onsubmit="return confirm('{{ __('Удалить сотрудника?') }}')">
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
            @else
            <div class="empty-state">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                </svg>
                <p>{{ __('У вас пока нет подопечных сотрудников') }}</p>
                <a href="{{ route('operator.employees.create') }}" class="btn btn--dark sm-button sm-button--dark">
                    + {{ __('Новый сотрудник') }}
                </a>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('css-page')
<style>
.employees-section { margin-top: 20px; }
.entity-list { border-radius: 18px; }
.entity-list__body { padding: 20px; background: #fff; border-radius: 0 0 18px 18px; }
.entity-rows { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
.entity-row { display: grid; grid-template-columns: 56px 160px 80px 120px 1fr auto; align-items: center; gap: 16px; padding: 14px 18px; background: #fff; border-radius: 12px; border: 2px solid var(--brand-color); }
.entity-row__avatar { width: 56px; height: 56px; border-radius: 10px; object-fit: cover; }
.entity-row__avatar--placeholder { display: flex; align-items: center; justify-content: center; background: rgba(177, 32, 84, 0.15); color: var(--brand-color); font-weight: 700; font-size: 18px; }
.entity-row__name { font-size: 22px; font-weight: 700; color: var(--accent-color); letter-spacing: 0.5px; }
.pill { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; height: 36px; box-sizing: border-box; }
.pill--brand { background: var(--brand-color); color: #fff; }
.pill--flag { background: rgba(22, 11, 14, 0.08); padding: 6px 12px; gap: 8px; }
.pill--flag img { margin-right: 0; flex-shrink: 0; }
.nationality-name { color: #333; font-weight: 500; white-space: nowrap; }
.pill--muted { background: rgba(22, 11, 14, 0.1); color: #666; }
.services-badge { display: inline-flex; align-items: center; padding: 8px 16px; background: var(--brand-color); color: #fff; border-radius: 8px; font-size: 13px; font-weight: 500; height: 36px; box-sizing: border-box; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.services-badge--empty { background: rgba(22, 11, 14, 0.1); color: #666; }
.entity-row__actions { display: flex; gap: 8px; }
.action-btn { width: 36px; height: 36px; border-radius: 8px; border: none; background: var(--accent-color); color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: opacity 0.2s; text-decoration: none; }
.action-btn:hover { opacity: 0.8; }
.empty-state { padding: 60px; text-align: center; color: #888; }
.empty-state svg { margin-bottom: 20px; opacity: 0.5; }
.empty-state p { font-size: 18px; margin-bottom: 20px; }
.btn.btn--dark.sm-button { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 12px 24px; text-align: center; white-space: nowrap; }

/* Красивые кнопки добавления */
.block-header .btn.btn--dark.sm-button {
  background: var(--accent-color);
  color: #fff;
  border: none;
  border-radius: 12px;
  padding: 14px 24px;
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  white-space: nowrap;
}

.block-header .btn.btn--dark.sm-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

@media (max-width: 1100px) { .entity-row { grid-template-columns: 56px 140px 70px 100px 1fr auto; } }

@media (max-width: 900px) { 
  .entity-row { grid-template-columns: 48px 1fr auto; } 
  .entity-row__services, .pill { display: none; }
  
  /* Кнопки добавления на мобилке */
  .block-header .btn.btn--dark.sm-button {
    padding: 12px 16px;
    font-size: 14px;
    border-radius: 10px;
  }
}

@media (max-width: 600px) {
  .block-header {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 12px;
  }
  
  .block-header .btn.btn--dark.sm-button {
    width: 100%;
    justify-content: center;
    padding: 14px 20px;
  }
}
</style>
@endpush
