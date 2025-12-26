@php
    use App\Models\Utility;
    use App\Services\PlanModuleService;
    $setting = \App\Models\Utility::settings();
@endphp

<nav class="dash-sidebar light-sidebar jobsi-sidebar">
    <div class="navbar-wrapper">
        <!-- Logo - захардкожено -->
        <div class="m-header main-logo">
            <a href="{{ route('jobsi.dashboard') }}" class="b-brand">
                <img src="{{ asset('fromfigma/jobsi_logo.png') }}" alt="JOBSI">
            </a>
        </div>
        
        <div class="navbar-content">
            <ul class="dash-navbar">
                <!-- Главная панель -->
                <li class="dash-item {{ Request::routeIs('jobsi.dashboard') || Request::segment(1) == 'jobsi-dashboard' ? 'active' : '' }}">
                    <a href="{{ route('jobsi.dashboard') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/mainpanel.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Главная панель') }}</span>
                    </a>
                </li>

                <!-- Работники -->
                @if(PlanModuleService::hasModule('workers'))
                <li class="dash-item {{ Request::routeIs('worker.*') ? 'active' : '' }}">
                    <a href="{{ route('worker.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/workers.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Работники') }}</span>
                    </a>
                </li>
                @endif

                <!-- Рабочие места -->
                @if(PlanModuleService::hasModule('workplaces'))
                <li class="dash-item {{ Request::routeIs('work-place.*') || Request::routeIs('position.*') ? 'active' : '' }}">
                    <a href="{{ route('work-place.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/workplaces.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Рабочие места') }}</span>
                    </a>
                </li>
                @endif

                <!-- Посещаемость -->
                @if(PlanModuleService::hasModule('attendance'))
                <li class="dash-item {{ Request::routeIs('attendance.*') ? 'active' : '' }}">
                    <a href="{{ route('attendance.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <i class="ti ti-calendar-check" style="color: #FF0049; font-size: 12px;"></i>
                        </span>
                        <span class="dash-mtext">{{ __('Посещаемость') }}</span>
                    </a>
                </li>
                @endif

                <!-- Проживание (Hotels) -->
                @if(PlanModuleService::hasModule('hotels'))
                <li class="dash-item {{ Request::routeIs('hotel.*') || Request::routeIs('room.*') ? 'active' : '' }}">
                    <a href="{{ route('hotel.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/hotel.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Проживание') }}</span>
                    </a>
                </li>
                @endif

                <!-- Транспорт -->
                @if(PlanModuleService::hasModule('vehicles'))
                <li class="dash-item dash-hasmenu {{ Request::routeIs('vehicles.*') || Request::routeIs('inspections.*') ? 'active dash-trigger' : '' }}">
                    <a href="{{ route('vehicles.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/vehicles.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Транспорт') }}</span>
                    </a>
                </li>
                @endif

                <!-- Документы -->
                @if(PlanModuleService::hasModule('documents'))
                <li class="dash-item {{ Request::routeIs('documents.*') ? 'active' : '' }}">
                    <a href="{{ route('documents.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/document.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Документы') }}</span>
                    </a>
                </li>
                @endif

                <!-- Касса -->
                @if(PlanModuleService::hasModule('cashbox'))
                <li class="dash-item {{ Request::routeIs('cashbox.*') ? 'active' : '' }}">
                    <a href="{{ route('cashbox.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/cashbox.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Касса') }}</span>
                    </a>
                </li>
                @endif

                <!-- Календарь (Audit) -->
                @if(PlanModuleService::hasModule('calendar'))
                <li class="dash-item {{ Request::routeIs('audit.*') ? 'active' : '' }}">
                    <a href="{{ route('audit.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/calendar.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Календарь') }}</span>
                    </a>
                </li>
                @endif

                <!-- Уведомления -->
                @if(PlanModuleService::hasModule('notifications'))
                <li class="dash-item {{ Request::routeIs('notifications.*') || Request::routeIs('notification-rules.*') ? 'active' : '' }}">
                    <a href="{{ route('notifications.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <i class="ti ti-bell" style="color: #FF0049; font-size: 12px;"></i>
                        </span>
                        <span class="dash-mtext">{{ __('Уведомления') }}</span>
                    </a>
                </li>
                @endif

                <!-- Сообщения (временно скрыто) -->
                {{-- <li class="dash-item {{ Request::segment(1) == 'chatify' ? 'active' : '' }}">
                    <a href="{{ url('chatify') }}" class="dash-link">
                        <span class="dash-micon">
                            <img src="{{ asset('fromfigma/message.svg') }}" alt="">
                        </span>
                        <span class="dash-mtext">{{ __('Сообщения') }}</span>
                    </a>
                </li> --}}

                <!-- Биллинг -->
                @if(Auth::user()->type == 'company')
                <li class="dash-item {{ Request::routeIs('billing.*') ? 'active' : '' }}">
                    <a href="{{ route('billing.index') }}" class="dash-link">
                        <span class="dash-micon">
                            <i class="ti ti-receipt" style="color: #FF0049; font-size: 12px;"></i>
                        </span>
                        <span class="dash-mtext">{{ __('Billing') }}</span>
                    </a>
                </li>
                @endif

                <!-- Настройки -->
                @if (Gate::check('manage company plan') || Gate::check('manage order') || Gate::check('manage company settings'))
                    <li class="dash-item dash-hasmenu {{ Request::segment(1) == 'settings' || Request::segment(1) == 'plans' || Request::segment(1) == 'stripe' || Request::segment(1) == 'order' || Request::routeIs('notification-rules.*') || Request::routeIs('cashbox.settings') ? 'active dash-trigger' : '' }}">
                        <a href="#!" class="dash-link">
                            <span class="dash-micon">
                                <img src="{{ asset('fromfigma/settings.svg') }}" alt="">
                            </span>
                            <span class="dash-mtext">{{ __('Настройки') }}</span>
                            <span class="dash-arrow"><i data-feather="chevron-right"></i></span>
                        </a>
                        <ul class="dash-submenu">
                            @if (Gate::check('manage company settings') && Auth::user()->type == 'super admin')
                                <li class="{{ Request::segment(1) == 'settings' ? 'active' : '' }}">
                                    <a href="{{ route('settings') }}" class="dash-link">{{ __('System Settings') }}</a>
                                </li>
                            @endif
                            @if (Gate::check('manage company plan'))
                                <li class="{{ Request::routeIs('plans.index') || Request::routeIs('stripe') ? 'active' : '' }}">
                                    <a href="{{ route('plans.index') }}" class="dash-link">{{ __('Setup Subscription Plan') }}</a>
                                </li>
                            @endif
                            <li class="{{ Request::routeIs('notification-rules.index') ? 'active' : '' }}">
                                <a href="{{ route('notification-rules.index') }}" class="dash-link">{{ __('Notification Builder') }}</a>
                            </li>
                            @can('cashbox_access')
                                <li class="{{ Request::routeIs('cashbox.settings') ? 'active' : '' }}">
                                    <a href="{{ route('cashbox.settings') }}" class="dash-link">{{ __('Cashbox Settings') }}</a>
                                </li>
                            @endcan
                            @can('manage user')
                                <li class="{{ Request::routeIs('users.*') ? 'active' : '' }}">
                                    <a href="{{ route('users.index') }}" class="dash-link">{{ __('User Settings') }}</a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
            </ul>
        </div>

        <!-- Google Reviews Block -->
        {{--<div class="jobsi-google-reviews">
            <div class="review-card">
                <span class="review-label">{{ __('Оставьте ваш отзыв') }}</span>
                <a href="https://g.page/r/YOUR_GOOGLE_REVIEW_LINK/review" target="_blank">
                    <img src="{{ asset('fromfigma/otzivi.png') }}" alt="Google Reviews">
                </a>
            </div>
        </div> --}}
    </div>
</nav>
