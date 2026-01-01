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
    @include('partials.admin.infinity-sidebar')
    
    <div class="container">
        @include('partials.admin.infinity-header')
        
        <main class="main-content">
            <h1 class="main-content__title">@yield('page-title')</h1>
            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('infinity/scripts/interactions.js') }}"></script>
    @stack('scripts')
</body>

</html>
