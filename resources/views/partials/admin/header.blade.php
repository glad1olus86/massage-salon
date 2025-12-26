@php
    $users = \Auth::user();
    $profile = \App\Models\Utility::get_file('uploads/avatar/');
    $languages = \App\Models\Utility::languages();

    $lang = isset($users->lang) ? $users->lang : 'en';
    if ($lang == null) {
        $lang = 'en';
    }
    $LangName = cache()->remember('full_language_data_' . $lang, now()->addHours(24), function () use ($lang) {
        return \App\Models\Language::languageData($lang);
    });

    $setting = \App\Models\Utility::settings();
    $unseenCounter = App\Models\ChMessage::where('to_id', Auth::user()->id)
        ->where('seen', 0)
        ->count();

    // Get company name - use company_name field if available, otherwise fallback
    $companyName = 'NansPlus s.r.o.';
    if (!empty(Auth::user()->company_name)) {
        $companyName = Auth::user()->company_name;
    } elseif (Auth::user()->currentWorkspace && !empty(Auth::user()->currentWorkspace->name)) {
        $companyName = Auth::user()->currentWorkspace->name;
    } elseif (Auth::user()->type == 'company') {
        // For company type, try to get company_name first, then fall back to name
        $companyName = !empty(Auth::user()->company_name) ? Auth::user()->company_name : Auth::user()->name;
    }

    // If user is coordinator/employee, get company name from their creator (company owner)
    if (Auth::user()->type != 'company' && Auth::user()->created_by) {
        $companyOwner = \App\Models\User::find(Auth::user()->created_by);
        if ($companyOwner && !empty($companyOwner->company_name)) {
            $companyName = $companyOwner->company_name;
        } elseif ($companyOwner) {
            $companyName = $companyOwner->name;
        }
    }
@endphp

@if (Auth::user()->type != 'super admin')
    {{-- JOBSI Style Header --}}
    @if (isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on')
        <header class="dash-header transprent-bg jobsi-header">
        @else
            <header class="dash-header jobsi-header">
    @endif
    <div class="header-wrapper">
        <div class="me-auto dash-mob-drp d-flex align-items-center">
            <ul class="list-unstyled mb-0">
                <li class="dash-h-item mob-hamburger">
                    <a href="#!" class="dash-head-link" id="mobile-collapse">
                        <div class="hamburger hamburger--arrowturn">
                            <div class="hamburger-box">
                                <div class="hamburger-inner"></div>
                            </div>
                        </div>
                    </a>
                </li>
            </ul>
            
            {{-- Current Page Indicator --}}
            <div class="jobsi-page-indicator">
                @php
                    $pageIcon = 'mainpanel.svg';
                    $pageTitle = __('Главная панель');
                    
                    if (Request::routeIs('jobsi.dashboard') || Request::segment(1) == 'jobsi-dashboard') {
                        $pageIcon = 'mainpanel.svg';
                        $pageTitle = __('Главная панель');
                    } elseif (Request::routeIs('worker.*')) {
                        $pageIcon = 'workers.svg';
                        $pageTitle = __('Работники');
                    } elseif (Request::routeIs('work-place.*') || Request::routeIs('position.*')) {
                        $pageIcon = 'workplaces.svg';
                        $pageTitle = __('Рабочие места');
                    } elseif (Request::routeIs('hotel.*') || Request::routeIs('room.*')) {
                        $pageIcon = 'hotel.svg';
                        $pageTitle = __('Проживание');
                    } elseif (Request::routeIs('vehicles.*') || Request::routeIs('inspections.*')) {
                        $pageIcon = 'vehicles.svg';
                        $pageTitle = __('Транспорт');
                    } elseif (Request::routeIs('documents.*')) {
                        $pageIcon = 'document.svg';
                        $pageTitle = __('Документы');
                    } elseif (Request::routeIs('cashbox.*')) {
                        $pageIcon = 'cashbox.svg';
                        $pageTitle = __('Касса');
                    } elseif (Request::routeIs('audit.*')) {
                        $pageIcon = 'calendar.svg';
                        $pageTitle = __('Календарь');
                    } elseif (Request::routeIs('notifications.*') || Request::routeIs('notification-rules.*')) {
                        $pageIcon = null;
                        $pageTitle = __('Уведомления');
                    } elseif (Request::segment(1) == 'chatify') {
                        $pageIcon = 'message.svg';
                        $pageTitle = __('Сообщения');
                    } elseif (Request::routeIs('settings') || Request::routeIs('settings.*') || Request::routeIs('profile')) {
                        $pageIcon = 'settings.svg';
                        $pageTitle = __('Настройки');
                    } elseif (Request::routeIs('billing.*') || Request::segment(1) == 'billing') {
                        $pageIcon = 'receipt.svg';
                        $pageTitle = __('Billing');
                    } elseif (Request::routeIs('users.*') || Request::segment(1) == 'users') {
                        $pageIcon = 'workers.svg';
                        $pageTitle = __('Users');
                    }
                @endphp
                <div class="jobsi-page-icon">
                    @if($pageIcon)
                        <img src="{{ asset('fromfigma/' . $pageIcon) }}" alt="">
                    @else
                        <i class="ti ti-bell" style="color: #FF0049; font-size: 18px;"></i>
                    @endif
                </div>
                <span class="jobsi-page-title">{{ $pageTitle }}</span>
            </div>
        </div>

        <div class="ms-auto">
            <ul class="list-unstyled d-flex align-items-center gap-2 mb-0">
                {{-- Messages Icon (временно скрыто) --}}
                {{-- <li class="dash-h-item">
                    <a class="jobsi-header-btn" href="{{ url('chats') }}" title="{{ __('Сообщения') }}">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#FFAAAA">
                            <path
                                d="M20 2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h4l4 4 4-4h4c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                        </svg>
                        @if ($unseenCounter > 0)
                            <span class="jobsi-badge">{{ $unseenCounter }}</span>
                        @endif
                    </a>
                </li> --}}

                {{-- Notifications Icon --}}
                <li class="dropdown dash-h-item" id="notification-dropdown">
                    <a class="jobsi-header-btn dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#"
                        role="button" aria-haspopup="true" aria-expanded="false" title="{{ __('Уведомления') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#FFAAAA">
                            <path
                                d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z" />
                        </svg>
                        <span class="jobsi-badge" id="notification-badge" style="display: none;">0</span>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end notification-dropdown-menu"
                        style="width: 380px; max-height: 400px; overflow-y: auto;">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <h6 class="mb-0">{{ __('Notifications') }}</h6>
                            <a href="#" id="mark-all-read"
                                class="text-primary small">{{ __('Mark all as read') }}</a>
                        </div>
                        <div id="notification-list">
                            <div class="text-center py-3 text-muted">
                                <i class="ti ti-bell-off"></i> {{ __('No notifications') }}
                            </div>
                        </div>
                        <div class="border-top px-3 py-2 text-center">
                            <a href="{{ route('notifications.index') }}"
                                class="text-primary small">{{ __('All notifications') }}</a>
                        </div>
                    </div>
                </li>

                {{-- Language Selector with Flag (only RU, EN, CS, UK) --}}
                @php
                    $allowedLangs = ['ru' => 'Русский', 'en' => 'English', 'cs' => 'Čeština', 'uk' => 'Українська'];
                @endphp
                <li class="dropdown dash-h-item">
                    <a class="jobsi-header-btn jobsi-lang-selector dropdown-toggle arrow-none" data-bs-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        @if ($lang == 'ru')
                            <i class="ti ti-world" style="font-size: 20px; color: #666;"></i>
                        @elseif($lang == 'uk')
                            <img src="{{ asset('fromfigma/ukraine_flag.png') }}" alt="UK" class="jobsi-flag">
                        @elseif($lang == 'cs')
                            <img src="{{ asset('fromfigma/czech_flag.svg') }}" alt="CS" class="jobsi-flag">
                        @else
                            <img src="{{ asset('fromfigma/uk_flag.png') }}" alt="EN" class="jobsi-flag">
                        @endif
                        <span class="jobsi-lang-text">{{ $allowedLangs[$lang] ?? ucfirst($LangName->full_name) }}</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="#000">
                            <path d="M7 10l5 5 5-5z" />
                        </svg>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                        @foreach ($allowedLangs as $code => $language)
                            <a href="{{ route('change.language', $code) }}"
                                class="dropdown-item {{ $lang == $code ? 'text-primary' : '' }}">
                                @if ($code == 'ru')
                                    <i class="ti ti-world" style="font-size: 18px; color: #666; margin-right: 8px;"></i>
                                @elseif($code == 'uk')
                                    <img src="{{ asset('fromfigma/ukraine_flag.png') }}" alt="UK" class="jobsi-dropdown-flag">
                                @elseif($code == 'cs')
                                    <img src="{{ asset('fromfigma/czech_flag.svg') }}" alt="CS" class="jobsi-dropdown-flag">
                                @else
                                    <img src="{{ asset('fromfigma/uk_flag.png') }}" alt="EN" class="jobsi-dropdown-flag">
                                @endif
                                <span>{{ $language }}</span>
                            </a>
                        @endforeach
                    </div>
                </li>

                {{-- Company Info Block --}}
                <li class="dash-h-item">
                    <div class="jobsi-company-block">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#FF0049">
                            <path
                                d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z" />
                        </svg>
                        <span class="jobsi-company-name">{{ $companyName }}</span>
                    </div>
                </li>

                {{-- User Info Block --}}
                <li class="dash-h-item">
                    <div class="jobsi-user-block">
                        <div class="jobsi-user-email">{{ Auth::user()->email }}</div>
                        <div class="jobsi-user-role">
                            {{ __(Auth::user()->type == 'company' ? '' : ucfirst(Auth::user()->type)) }}</div>
                    </div>
                </li>

                {{-- Settings Icon --}}
                <li class="dash-h-item">
                    <a class="jobsi-header-icon" href="{{ route('profile') }}" title="{{ __('Настройки') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#FFAAAA">
                            <path
                                d="M19.14,12.94c0.04-0.3,0.06-0.61,0.06-0.94c0-0.32-0.02-0.64-0.07-0.94l2.03-1.58c0.18-0.14,0.23-0.41,0.12-0.61l-1.92-3.32c-0.12-0.22-0.37-0.29-0.59-0.22l-2.39,0.96c-0.5-0.38-1.03-0.7-1.62-0.94L14.4,2.81c-0.04-0.24-0.24-0.41-0.48-0.41h-3.84c-0.24,0-0.43,0.17-0.47,0.41L9.25,5.35C8.66,5.59,8.12,5.92,7.63,6.29L5.24,5.33c-0.22-0.08-0.47,0-0.59,0.22L2.74,8.87C2.62,9.08,2.66,9.34,2.86,9.48l2.03,1.58C4.84,11.36,4.82,11.69,4.82,12s0.02,0.64,0.07,0.94l-2.03,1.58c-0.18,0.14-0.23,0.41-0.12,0.61l1.92,3.32c0.12,0.22,0.37,0.29,0.59,0.22l2.39-0.96c0.5,0.38,1.03,0.7,1.62,0.94l0.36,2.54c0.05,0.24,0.24,0.41,0.48,0.41h3.84c0.24,0,0.44-0.17,0.47-0.41l0.36-2.54c0.59-0.24,1.13-0.56,1.62-0.94l2.39,0.96c0.22,0.08,0.47,0,0.59-0.22l1.92-3.32c0.12-0.22,0.07-0.47-0.12-0.61L19.14,12.94z M12,15.6c-1.98,0-3.6-1.62-3.6-3.6s1.62-3.6,3.6-3.6s3.6,1.62,3.6,3.6S13.98,15.6,12,15.6z" />
                        </svg>
                    </a>
                </li>

                {{-- Logout Icon --}}
                <li class="dash-h-item">
                    <a class="jobsi-header-icon" href="{{ route('logout') }}"
                        onclick="event.preventDefault(); document.getElementById('frm-logout-jobsi').submit();"
                        title="{{ __('Выход') }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#FFAAAA">
                            <path
                                d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z" />
                        </svg>
                    </a>
                    <form id="frm-logout-jobsi" action="{{ route('logout') }}" method="POST" class="d-none">
                        {{ csrf_field() }}
                    </form>
                </li>
            </ul>
        </div>
    </div>
    </header>
@else
    {{-- Default Header for other user types --}}
    @if (isset($setting['cust_theme_bg']) && $setting['cust_theme_bg'] == 'on')
        <header class="dash-header transprent-bg">
        @else
            <header class="dash-header">
    @endif
    <div class="header-wrapper">
        <div class="me-auto dash-mob-drp">
            <ul class="list-unstyled">
                <li class="dash-h-item mob-hamburger">
                    <a href="#!" class="dash-head-link" id="mobile-collapse">
                        <div class="hamburger hamburger--arrowturn">
                            <div class="hamburger-box">
                                <div class="hamburger-inner"></div>
                            </div>
                        </div>
                    </a>
                </li>

                <li class="dropdown dash-h-item drp-company">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        @php
                            $adminInitials = mb_strtoupper(mb_substr(\Auth::user()->name, 0, 2));
                        @endphp
                        <span class="theme-avtar d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #FF0049 0%, #ff6b8a 100%); color: white; font-weight: 600; font-size: 14px; width: 40px; height: 40px; border-radius: 8px;">
                            {{ $adminInitials }}
                        </span>
                        <span class="hide-mob ms-2">{{ __('Hi, ') }}{{ \Auth::user()->name }}!</span>
                        <i class="ti ti-chevron-down drp-arrow nocolor hide-mob"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown">
                        <a href="{{ route('profile') }}" class="dropdown-item">
                            <i class="ti ti-user text-dark"></i><span>{{ __('Profile') }}</span>
                        </a>
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('frm-logout').submit();"
                            class="dropdown-item">
                            <i class="ti ti-power text-dark"></i><span>{{ __('Logout') }}</span>
                        </a>
                        <form id="frm-logout" action="{{ route('logout') }}" method="POST" class="d-none">
                            {{ csrf_field() }}
                        </form>
                    </div>
                </li>
            </ul>
        </div>
        <div class="ms-auto">
            <ul class="list-unstyled">
                @if (\Auth::user()->type == 'company')
                    @impersonating($guard = null)
                        <li class="dropdown dash-h-item drp-company">
                            <a class="btn btn-danger btn-sm" href="{{ route('exit.company') }}"><i
                                    class="ti ti-ban"></i>
                                {{ __('Exit Company Login') }}
                            </a>
                        </li>
                    @endImpersonating
                @endif

                @if (\Auth::user()->type != 'client' && \Auth::user()->type != 'super admin')
                    <li class="dropdown dash-h-item drp-notification">
                        <a class="dash-head-link arrow-none me-0" href="{{ url('chats') }}" aria-haspopup="false"
                            aria-expanded="false">
                            <i class="ti ti-brand-hipchat"></i>
                            <span
                                class="bg-danger dash-h-badge message-toggle-msg  message-counter custom_messanger_counter beep">
                                {{ $unseenCounter }}<span class="sr-only"></span>
                            </span>
                        </a>
                    </li>
                @endif

                @if (\Auth::user()->type != 'client')
                    <li class="dropdown dash-h-item drp-notification" id="notification-dropdown">
                        <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown"
                            href="#" role="button" aria-haspopup="true" aria-expanded="false">
                            <i class="ti ti-bell"></i>
                            <span class="bg-danger text-white notification-badge" id="notification-badge"
                                style="display: none; position: absolute; top: 5px; right: 5px; min-width: 18px; height: 18px; border-radius: 50%; font-size: 11px; font-weight: bold; line-height: 18px; text-align: center; padding: 0 4px;">0</span>
                        </a>
                        <div class="dropdown-menu dash-h-dropdown dropdown-menu-end notification-dropdown-menu"
                            style="width: 420px; max-height: 450px; overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <h6 class="mb-0">{{ __('Notifications') }}</h6>
                                <a href="#" id="mark-all-read"
                                    class="text-primary small">{{ __('Mark all as read') }}</a>
                            </div>
                            <div id="notification-list">
                                <div class="text-center py-3 text-muted">
                                    <i class="ti ti-bell-off"></i> {{ __('No notifications') }}
                                </div>
                            </div>
                            <div class="border-top px-3 py-2 text-center">
                                <a href="{{ route('notifications.index') }}"
                                    class="text-primary small">{{ __('All notifications') }}</a>
                            </div>
                        </div>
                    </li>
                @endif

                <li class="dropdown dash-h-item drp-language">
                    <a class="dash-head-link dropdown-toggle arrow-none me-0" data-bs-toggle="dropdown"
                        href="#" role="button" aria-haspopup="false" aria-expanded="false">
                        <i class="ti ti-world nocolor"></i>
                        <span class="drp-text hide-mob">{{ ucfirst($LangName->full_name) }}</span>
                        <i class="ti ti-chevron-down drp-arrow nocolor"></i>
                    </a>
                    <div class="dropdown-menu dash-h-dropdown dropdown-menu-end">
                        @foreach ($languages as $code => $language)
                            <a href="{{ route('change.language', $code) }}"
                                class="dropdown-item {{ $lang == $code ? 'text-primary' : '' }}">
                                <span>{{ ucFirst($language) }}</span>
                            </a>
                        @endforeach
                        @if (\Auth::user()->type == 'super admin')
                            <a data-url="{{ route('create.language') }}" class="dropdown-item text-primary"
                                data-ajax-popup="true" data-title="{{ __('Create New Language') }}"
                                style="cursor: pointer">
                                {{ __('Create Language') }}
                            </a>
                            <a class="dropdown-item text-primary"
                                href="{{ route('manage.language', [isset($lang) ? $lang : 'english']) }}">{{ __('Manage Language') }}</a>
                        @endif
                    </div>
                </li>
            </ul>
        </div>
    </div>
    </header>
@endif
