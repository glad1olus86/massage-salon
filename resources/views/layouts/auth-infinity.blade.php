<!DOCTYPE html>
@php
    use App\Models\Utility;
    $setting = Utility::settings();
    $lang = \App::getLocale('lang');
    $languages = App\Models\Utility::languages();
@endphp

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>Infinity | @yield('page-title')</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="Infinity - Massage Salon CRM" />
    <link rel="icon" href="{{ asset('infinity/assets/logo-massage.png') }}" type="image/png" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

    <!-- Infinity Styles -->
    <link rel="stylesheet" href="{{ asset('infinity/styles/index.css') }}">
    <link rel="stylesheet" href="{{ asset('infinity/styles/auth.css') }}">
    
    @stack('styles')
</head>

<body>
    @yield('content')
    
    @stack('scripts')
</body>

</html>
