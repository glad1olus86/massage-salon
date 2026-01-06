@extends('layouts.auth-infinity')

@section('page-title')
    {{ __('Авторизация') }}
@endsection

@section('content')
    <div class="container">
        <div class="form-wrapper">
            <img src="{{ asset('infinity/assets/logo-massage.png') }}" alt="Infinity logo" class="logo">
            
            <form method="POST" action="{{ route('login') }}" class="auth-form" autocomplete="off">
                @csrf

                @if (session('status'))
                    <div class="alert alert-error">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <label for="email" class="input-label">
                    <img src="{{ asset('infinity/assets/icons/user-auth-icon.svg') }}" alt="User icon" class="input-icon">
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="{{ __('Логин') }}" 
                           value="{{ old('email') }}"
                           required 
                           class="input">
                </label>

                <label for="password" class="input-label">
                    <img src="{{ asset('infinity/assets/icons/password-auth-icon.svg') }}" alt="Password icon" class="input-icon">
                    <input type="password" 
                           id="password" 
                           name="password" 
                           placeholder="{{ __('Пароль') }}" 
                           required 
                           class="input">
                </label>

                <button type="submit" class="submit-button login-button">{{ __('Войти в систему') }}</button>
                
                <button type="button" onclick="openMasseuseRegisterModal()" class="submit-button register-button">{{ __('Регистрация') }}</button>
            </form>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot-link">{{ __('Забыли пароль?') }}</a>
            @endif
        </div>
    </div>

    <!-- Masseuse Registration Modal -->
    @include('auth.partials.masseuse-register-modal')
@endsection
