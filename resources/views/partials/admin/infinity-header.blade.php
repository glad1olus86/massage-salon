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
    <button type="button" class="header-profile-button">
        <img src="{{ Auth::user()->avatar ? Storage::url(Auth::user()->avatar) : asset('infinity/assets/profile-image.webp') }}" alt="Profile" class="header-profile__image">
        <span class="header-profile__name">{{ Auth::user()->name }}</span>
        <div class="arrow-button">
            <svg viewBox="0 0 7 5" fill="none">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M0 1.98734V0L3.37859 2.7877L6.75719 0V1.98734L3.37859 4.77504L0 1.98734Z" fill="white"/>
            </svg>
        </div>
    </button>
    
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
        
        <a href="{{ route('profile') }}" class="header__outlined-button">
            <img src="{{ asset('infinity/assets/icons/header-settings-icon.svg') }}" alt="" class="header__outlined-button-icon">
            {{ __('Настройки') }}
        </a>
        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
            @csrf
            <button type="submit" class="header__outlined-button" title="{{ __('Выйти') }}">
                <img src="{{ asset('infinity/assets/icons/header-logout-icon.svg') }}" alt="" class="header__outlined-button-icon">
            </button>
        </form>
    </div>
</header>

<style>
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

