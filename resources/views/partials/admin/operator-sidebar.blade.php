<aside class="sidebar">
    <img src="{{ asset('infinity/assets/logo-massage.png') }}" alt="Infinity logo | Massage" class="sidebar-logo">
    
    <nav class="sidebar-nav">
        <ul class="sidebar-nav__list">
            <li>
                <a href="{{ route('operator.dashboard') }}" class="sidebar-nav__link {{ Request::routeIs('operator.dashboard') ? 'active' : '' }}">
                    <img src="{{ asset('infinity/assets/icons/nav-dashboard-icon.svg') }}" class="sidebar-nav__icon" alt="">
                    {{ __('Главная') }}
                </a>
            </li>
            <li>
                <a href="{{ route('operator.employees.index') }}" class="sidebar-nav__link {{ Request::routeIs('operator.employees.*') ? 'active' : '' }}">
                    <img src="{{ asset('infinity/assets/icons/nav-employees-icon.svg') }}" class="sidebar-nav__icon" alt="">
                    {{ __('Мои сотрудники') }}
                </a>
            </li>
            <li>
                <a href="{{ route('operator.orders.index') }}" class="sidebar-nav__link {{ Request::routeIs('operator.orders.*') ? 'active' : '' }}">
                    <img src="{{ asset('infinity/assets/icons/nav-orders-icon.svg') }}" class="sidebar-nav__icon" alt="">
                    {{ __('Заказы') }}
                </a>
            </li>
            <li>
                <a href="{{ route('operator.calendar') }}" class="sidebar-nav__link {{ Request::routeIs('operator.calendar*') ? 'active' : '' }}">
                    <img src="{{ asset('infinity/assets/icons/nav-calendar-icon.svg') }}" class="sidebar-nav__icon" alt="">
                    {{ __('Календарь') }}
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-contacts">
        <span class="sidebar-contacts__title">{{ __('КОНТАКТЫ АДМИНИСТРАТОРА') }}</span>
        <ul class="sidebar-contacts__list">
            <li>
                <a href="tel:+420777555777" class="sidebar-contact__link">
                    <img src="{{ asset('infinity/assets/icons/contacts-phone-icon.svg') }}" alt="" class="sidebar-contact__icon">
                    +420 777 555 7777
                </a>
            </li>
            <li>
                <a href="mailto:admin@masaze-infinity.cz" class="sidebar-contact__link">
                    <img src="{{ asset('infinity/assets/icons/contacts-mail-icon.svg') }}" alt="" class="sidebar-contact__icon">
                    admin@masaze-infinity.cz
                </a>
            </li>
        </ul>
    </div>
    
    <a href="#" class="sidebar-ideas-wrapper">
        <div class="sidebar-ideas">
            {{ __('Идеи и') }}
            <svg class="sidebar-ideas-icon" viewBox="0 0 18 25" fill="none">
                <path d="M6.90065 0.1962C3.59157 0.887655 0.908466 3.54508 0.20246 6.87472C-0.393683 9.68634 0.340936 12.5091 2.21794 14.6192C3.13547 15.6506 3.66164 17.0121 3.66164 18.3583V19.8232C3.66164 20.7978 4.29968 21.6257 5.17985 21.9125C5.46931 23.6227 6.95368 24.9989 8.78859 24.9989C10.623 24.9989 12.1078 23.6232 12.3973 21.9125C13.2775 21.6257 13.9155 20.7978 13.9155 19.8231V18.3583C13.9155 17.0094 14.4435 15.6643 15.402 14.5707C16.805 12.9701 17.5777 10.9164 17.5777 8.788C17.5777 3.24283 12.502 -0.974015 6.90065 0.1962Z" fill="#FFC107"/>
            </svg>
            {{ __('предложения') }}
        </div>
    </a>
</aside>
