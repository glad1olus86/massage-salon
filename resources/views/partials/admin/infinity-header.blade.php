@php
    $languages = [
        'ru' => 'Русский',
        'cs' => 'Čeština', 
        'en' => 'English',
        'uk' => 'Українська',
    ];
    $currentLang = App::getLocale();
@endphp

<header class="header">
    <div class="header-welcome">
        {{ __('Привет') }}, <strong>{{ Auth::user()->name }}</strong>!
    </div>
    
    <div class="header-row">
        <!-- Language Switcher -->
        <div class="dropdown language-dropdown" data-dropdown>
            <button type="button" class="header__outlined-button dropdown__trigger">
                <img src="{{ asset('infinity/assets/icons/header-language-icon.svg') }}" alt="" class="header__outlined-button-icon">
                {{ $languages[$currentLang] ?? 'Русский' }}
            </button>
            <div class="dropdown__menu language-menu">
                @foreach($languages as $code => $name)
                    <a href="{{ route('change.language', $code) }}" class="dropdown__option {{ $currentLang == $code ? 'active' : '' }}">
                        {{ $name }}
                    </a>
                @endforeach
            </div>
        </div>
        
        <a href="{{ route('profile') }}" class="header__outlined-button header__outlined-button--settings" style="display: none;">
            <img src="{{ asset('infinity/assets/icons/header-settings-icon.svg') }}" alt="" class="header__outlined-button-icon">
            {{ __('Настройки') }}
        </a>
        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
            @csrf
            <button type="submit" class="header__outlined-button" title="{{ __('Выйти') }}">
                <img src="{{ asset('infinity/assets/icons/header-logout-icon.svg') }}" alt="" class="header__outlined-button-icon header__logout-icon--desktop">
                <img src="{{ asset('infinity/assets/icons/header-logout-icon-brand.svg') }}" alt="" class="header__outlined-button-icon header__logout-icon--mobile">
            </button>
        </form>
    </div>
</header>

<style>
.header-welcome {
    font-size: 18px;
    color: #fff;
}
.language-dropdown {
    position: relative;
}
.language-dropdown .dropdown__menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    min-width: 150px;
    padding: 8px;
    border-radius: 10px;
    background: var(--accent-color);
    display: none;
    z-index: 100;
}
.language-dropdown.is-open .dropdown__menu {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.language-dropdown .dropdown__option {
    padding: 10px 14px;
    color: #fff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    transition: background 0.2s;
}
.language-dropdown .dropdown__option:hover,
.language-dropdown .dropdown__option.active {
    background: rgba(255, 255, 255, 0.1);
}
</style>

