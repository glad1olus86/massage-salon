@php
    use App\Models\Utility;
    $setting = \App\Models\Utility::settings();
    $lang = \App::getLocale('lang');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>Infinity | @yield('page-title')</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Infinity - Massage Salon CRM" />
    <link rel="icon" href="{{ asset('infinity/assets/logo-massage.png') }}" type="image/png" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,100..900&display=swap" rel="stylesheet">

    <!-- Infinity Styles -->
    <link rel="stylesheet" href="{{ asset('infinity/styles/index.css') }}">
    <link rel="stylesheet" href="{{ asset('infinity/styles/layout.css') }}">
    <link rel="stylesheet" href="{{ asset('infinity/styles/components.css') }}">
    
    @stack('css-page')
</head>

<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Открыть меню">
        <span class="mobile-menu-btn__line"></span>
        <span class="mobile-menu-btn__line"></span>
        <span class="mobile-menu-btn__line"></span>
    </button>
    
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    @include('partials.admin.masseuse-sidebar')
    
    <div class="container">
        @include('partials.admin.masseuse-header')
        
        <main class="main-content">
            <h1 class="main-content__title">@yield('page-title')</h1>
            
            @if(session('success'))
                <div class="alert alert--success">{{ session('success') }}</div>
            @endif
            
            @if(session('error'))
                <div class="alert alert--error">{{ session('error') }}</div>
            @endif
            
            @if($errors->any())
                <div class="alert alert--error">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('infinity/scripts/interactions.js') }}"></script>
    <script src="{{ asset('infinity/scripts/mobile-sidebar.js') }}"></script>
    @stack('scripts')
</body>

</html>
