@extends('layouts.auth-custom')

@php
    $languages = App\Models\Utility::languages();
    // $lang is passed from controller
    $currentLang = $lang ?? \App::getLocale();
    
    // Language configuration (order: English, Czech, Ukrainian, Russian)
    $langConfig = [
        'en' => ['name' => 'English', 'flag' => 'uk_flag.png'],
        'cs' => ['name' => 'Čeština', 'flag' => 'czech_flag.png'],
        'uk' => ['name' => 'Українська', 'flag' => 'ukraine_flag.png'],
        'ru' => ['name' => 'Русский', 'flag' => 'globe_icon.svg'],
    ];
    
    // Get current language info
    $currentLangInfo = $langConfig[$currentLang] ?? $langConfig['en'];
@endphp

@section('page-title')
    {{ __('Login') }}
@endsection

@section('content')
    <div class="login-page">
        <div class="background-layer"></div>

        <div class="main-content">
            <!-- Logo -->
            <div class="logo-container">
                <img src="{{ asset('assets/images/login/jobsi_logo.png') }}" alt="JOBSI">
            </div>
            <p class="tagline">Personal & Job Agency Performance Management</p>

            <div class="content-wrapper">
                <!-- Left Side - Buttons -->
                <div class="buttons-section">
                    <!-- Login Button -->
                    <button type="button" class="action-btn login-btn" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <img src="{{ asset('assets/images/login/login_icon.svg') }}" alt="" class="btn-icon">
                        <div class="btn-text">
                            <span class="btn-title">{{ __('SIGN IN') }}</span>
                            <span class="btn-subtitle">{{ __('personal account') }}</span>
                        </div>
                    </button>

                    <!-- Register Button -->
                    <button type="button" class="action-btn register-btn" data-bs-toggle="modal" data-bs-target="#registerModal">
                        <img src="{{ asset('assets/images/login/new_account_icon.svg') }}" alt="" class="btn-icon">
                        <div class="btn-text">
                            <span class="btn-title">{{ __('NEW ACCOUNT') }}</span>
                            <span class="btn-subtitle">{{ __('Free') }}</span>
                        </div>
                    </button>

                    <!-- Language Selector -->
                    <div class="lang-selector" onclick="toggleLangDropdown()">
                        <img src="{{ asset('assets/images/login/' . $currentLangInfo['flag']) }}"
                            alt="" class="lang-flag {{ $currentLang == 'ru' ? 'globe-icon' : '' }}">
                        <span class="lang-text">{{ $currentLangInfo['name'] }}</span>
                        <span class="lang-arrow">▼</span>

                        <div class="lang-dropdown" id="langDropdown">
                            @foreach($langConfig as $code => $info)
                                <a href="{{ route('login', $code) }}" class="{{ $currentLang == $code ? 'active' : '' }}">
                                    <img src="{{ asset('assets/images/login/' . $info['flag']) }}" alt="" class="{{ $code == 'ru' ? 'globe-icon' : '' }}">
                                    <span>{{ $info['name'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Right Side - Video -->
                <div class="video-section">
                    <div class="video-container">
                        <img src="{{ asset('assets/images/login/woman_placeholder.jpg') }}" alt="Express Guide">
                        <div class="play-button"></div>
                    </div>
                    <div class="express-guide">
                        <span>{{ __('EXPRESS') }}</span>
                        {{ __('GUIDE') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-links">
                <a href="/#">{{ __('terms of service') }}</a>
                <span>|</span>
                @php
                    $gdprFiles = [
                        'ru' => 'jobsi_gdpr_ru.pdf',
                        'uk' => 'jobsi_gdpr_uk.pdf',
                        'cs' => 'jobsi_gdpr_cz.pdf',
                        'en' => 'jobsi_gdpr_en.pdf',
                    ];
                    $gdprFile = $gdprFiles[$currentLang] ?? $gdprFiles['en'];
                @endphp
                <a href="{{ asset('gdpr/' . $gdprFile) }}" target="_blank">{{ __('privacy policy (GDPR)') }}</a>
                <span>|</span>
                <a href="/#">{{ __('back to homepage') }}</a>
                <span>|</span>
                <a href="/#">{{ __('user guide') }}</a>
                <span>|</span>
                <a href="/#">{{ __('pricing') }}</a>
            </div>
        </footer>
    </div>

    <!-- Login Modal -->
    <div class="modal fade login-modal" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Close Button -->
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                    <svg width="59" height="59" viewBox="0 0 59 59" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M47.5476 3.245H11.4532C6.92731 3.245 3.24512 7.42456 3.24512 12.5611V46.4389C3.24512 51.5754 6.92731 55.755 11.4532 55.755H47.5476C52.0729 55.755 55.7551 51.5754 55.7551 46.4389V12.5611C55.7551 7.42456 52.0729 3.245 47.5476 3.245ZM38.8605 39.0084C38.696 39.2296 38.4851 39.4121 38.2426 39.543C38.0001 39.674 37.7319 39.7503 37.4568 39.7665C37.1816 39.7828 36.9063 39.7386 36.6501 39.6371C36.3938 39.5356 36.1629 39.3792 35.9736 39.1789L29.5591 32.6447L23.1092 39.2037C22.9055 39.4196 22.6568 39.5882 22.3809 39.6977C22.1049 39.8071 21.8083 39.8547 21.5119 39.8372C21.2156 39.8196 20.9266 39.7373 20.6655 39.5961C20.4044 39.4549 20.1774 39.2581 20.0005 39.0196C19.6024 38.5106 19.4028 37.8742 19.4388 37.229C19.4603 36.5874 19.7182 35.9764 20.1628 35.5133L26.274 29.2988L20.1781 23.0902C19.7648 22.6591 19.5251 22.0906 19.5049 21.4937C19.4715 20.8937 19.6568 20.3019 20.0265 19.8281C20.205 19.5932 20.4347 19.402 20.6981 19.2691C20.9616 19.1361 21.2519 19.065 21.5469 19.0611C21.8028 19.0633 22.0556 19.1169 22.2904 19.2188C22.5251 19.3207 22.737 19.4687 22.9134 19.6541L29.3296 26.1913L35.6426 19.7703C35.8463 19.5542 36.095 19.3854 36.371 19.2759C36.6471 19.1663 36.9438 19.1186 37.2403 19.1362C37.5367 19.1537 37.8257 19.2361 38.0869 19.3775C38.3481 19.5188 38.5751 19.7158 38.7519 19.9544C39.15 20.4634 39.3496 21.0998 39.3136 21.745C39.2918 22.3867 39.0338 22.9977 38.5891 23.4608L32.6141 29.5378L38.7088 35.7463C39.122 36.1775 39.3615 36.746 39.3814 37.3429C39.4151 37.9428 39.23 38.5346 38.8605 39.0084Z" fill="#FFAAAA"/>
                    </svg>
                </button>
                
                <div class="modal-inner">
                    <div class="modal-header">
                        <h5 class="modal-title" id="loginModalLabel">
                            {{ __('Sign In to') }} <strong>JOBSI</strong> {{ __('Dashboard') }}
                        </h5>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('login') }}" id="loginForm">
                            @csrf

                            @if (session('status'))
                                <div class="alert alert-danger">
                                    {{ session('status') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    @foreach ($errors->all() as $error)
                                        <div>{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif

                            <div class="form-group">
                                <div class="input-wrapper">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z" fill="#FF0049"/>
                                        </svg>
                                    </div>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" placeholder="{{ __('Email') }}" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="input-wrapper">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M19.5 9.75H18.4167V7.58333C18.4167 4.59375 15.9896 2.16667 13 2.16667C10.0104 2.16667 7.58333 4.59375 7.58333 7.58333V9.75H6.5C5.30833 9.75 4.33333 10.725 4.33333 11.9167V21.6667C4.33333 22.8583 5.30833 23.8333 6.5 23.8333H19.5C20.6917 23.8333 21.6667 22.8583 21.6667 21.6667V11.9167C21.6667 10.725 20.6917 9.75 19.5 9.75ZM13 18.4167C11.8083 18.4167 10.8333 17.4417 10.8333 16.25C10.8333 15.0583 11.8083 14.0833 13 14.0833C14.1917 14.0833 15.1667 15.0583 15.1667 16.25C15.1667 17.4417 14.1917 18.4167 13 18.4167ZM16.3583 9.75H9.64167V7.58333C9.64167 5.72917 11.1458 4.225 13 4.225C14.8542 4.225 16.3583 5.72917 16.3583 7.58333V9.75Z" fill="#FF0049"/>
                                        </svg>
                                    </div>
                                    <input type="password" class="form-control" id="password" name="password"
                                        placeholder="{{ __('Password') }}" required>
                                </div>
                            </div>

                            <div class="forgot-wrapper">
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="forgot-link">
                                        {{ __('Forgot your password?') }}
                                    </a>
                                @endif
                            </div>

                            <div class="btn-wrapper">
                                <button type="submit" class="btn-login">
                                    {{ __('Enter') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade login-modal register-modal" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- Close Button -->
                <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                    <svg width="59" height="59" viewBox="0 0 59 59" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M47.5476 3.245H11.4532C6.92731 3.245 3.24512 7.42456 3.24512 12.5611V46.4389C3.24512 51.5754 6.92731 55.755 11.4532 55.755H47.5476C52.0729 55.755 55.7551 51.5754 55.7551 46.4389V12.5611C55.7551 7.42456 52.0729 3.245 47.5476 3.245ZM38.8605 39.0084C38.696 39.2296 38.4851 39.4121 38.2426 39.543C38.0001 39.674 37.7319 39.7503 37.4568 39.7665C37.1816 39.7828 36.9063 39.7386 36.6501 39.6371C36.3938 39.5356 36.1629 39.3792 35.9736 39.1789L29.5591 32.6447L23.1092 39.2037C22.9055 39.4196 22.6568 39.5882 22.3809 39.6977C22.1049 39.8071 21.8083 39.8547 21.5119 39.8372C21.2156 39.8196 20.9266 39.7373 20.6655 39.5961C20.4044 39.4549 20.1774 39.2581 20.0005 39.0196C19.6024 38.5106 19.4028 37.8742 19.4388 37.229C19.4603 36.5874 19.7182 35.9764 20.1628 35.5133L26.274 29.2988L20.1781 23.0902C19.7648 22.6591 19.5251 22.0906 19.5049 21.4937C19.4715 20.8937 19.6568 20.3019 20.0265 19.8281C20.205 19.5932 20.4347 19.402 20.6981 19.2691C20.9616 19.1361 21.2519 19.065 21.5469 19.0611C21.8028 19.0633 22.0556 19.1169 22.2904 19.2188C22.5251 19.3207 22.737 19.4687 22.9134 19.6541L29.3296 26.1913L35.6426 19.7703C35.8463 19.5542 36.095 19.3854 36.371 19.2759C36.6471 19.1663 36.9438 19.1186 37.2403 19.1362C37.5367 19.1537 37.8257 19.2361 38.0869 19.3775C38.3481 19.5188 38.5751 19.7158 38.7519 19.9544C39.15 20.4634 39.3496 21.0998 39.3136 21.745C39.2918 22.3867 39.0338 22.9977 38.5891 23.4608L32.6141 29.5378L38.7088 35.7463C39.122 36.1775 39.3615 36.746 39.3814 37.3429C39.4151 37.9428 39.23 38.5346 38.8605 39.0084Z" fill="#FFAAAA"/>
                    </svg>
                </button>
                
                <div class="modal-inner">
                    <div class="modal-header">
                        <h5 class="modal-title" id="registerModalLabel">
                            {{ __('Sign up and start to save your time and money') }}
                        </h5>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('register.store') }}" id="registerForm">
                            @csrf

                            <!-- Email -->
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z" fill="#FF0049"/>
                                        </svg>
                                    </div>
                                    <input type="email" class="form-control" id="reg_email" name="email"
                                        value="{{ old('email') }}" placeholder="{{ __('Email') }}" required>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 26 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M19.5 9.75H18.4167V7.58333C18.4167 4.59375 15.9896 2.16667 13 2.16667C10.0104 2.16667 7.58333 4.59375 7.58333 7.58333V9.75H6.5C5.30833 9.75 4.33333 10.725 4.33333 11.9167V21.6667C4.33333 22.8583 5.30833 23.8333 6.5 23.8333H19.5C20.6917 23.8333 21.6667 22.8583 21.6667 21.6667V11.9167C21.6667 10.725 20.6917 9.75 19.5 9.75ZM13 18.4167C11.8083 18.4167 10.8333 17.4417 10.8333 16.25C10.8333 15.0583 11.8083 14.0833 13 14.0833C14.1917 14.0833 15.1667 15.0583 15.1667 16.25C15.1667 17.4417 14.1917 18.4167 13 18.4167ZM16.3583 9.75H9.64167V7.58333C9.64167 5.72917 11.1458 4.225 13 4.225C14.8542 4.225 16.3583 5.72917 16.3583 7.58333V9.75Z" fill="#FF0049"/>
                                        </svg>
                                    </div>
                                    <input type="password" class="form-control" id="reg_password" name="password"
                                        placeholder="{{ __('Password') }}" required>
                                </div>
                            </div>

                            <!-- Name -->
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M14.5 14.5C17.4 14.5 19.75 12.15 19.75 9.25C19.75 6.35 17.4 4 14.5 4C11.6 4 9.25 6.35 9.25 9.25C9.25 12.15 11.6 14.5 14.5 14.5ZM14.5 17.125C10.9925 17.125 4 18.8825 4 22.375V25H25V22.375C25 18.8825 18.0075 17.125 14.5 17.125Z" fill="#FF0049"/>
                                        </svg>
                                    </div>
                                    <input type="text" class="form-control" id="reg_name" name="name"
                                        value="{{ old('name') }}" placeholder="{{ __('Name') }}" required>
                                </div>
                            </div>

                            <!-- Company Name -->
                            <div class="form-group">
                                <div class="input-wrapper">
                                    <div class="input-icon">
                                        <svg viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M10.5 0L0 5.25V7H21V5.25L10.5 0ZM2.625 8.75V15.75H5.25V8.75H2.625ZM9.1875 8.75V15.75H11.8125V8.75H9.1875ZM15.75 8.75V15.75H18.375V8.75H15.75ZM0 17.5V21H21V17.5H0Z" fill="#FF0049"/>
                                        </svg>
                                    </div>
                                    <input type="text" class="form-control" id="reg_company" name="company_name"
                                        value="{{ old('company_name') }}" placeholder="{{ __('Company name') }}">
                                </div>
                            </div>

                            <!-- Terms Checkbox -->
                            <div class="terms-wrapper">
                                <input type="checkbox" class="terms-checkbox" id="terms" name="terms" required>
                                <label for="terms" class="terms-label">
                                    {{ __("I've read the terms and conditions") }}
                                </label>
                            </div>

                            <div class="btn-wrapper">
                                <button type="submit" class="btn-login">
                                    {{ __('Sign up') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        function toggleLangDropdown() {
            document.getElementById('langDropdown').classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.lang-selector')) {
                document.getElementById('langDropdown').classList.remove('show');
            }
        });

        // Blur background when modal opens
        var loginModal = document.getElementById('loginModal');
        var registerModal = document.getElementById('registerModal');
        var loginPage = document.querySelector('.login-page');
        
        // Login modal blur
        loginModal.addEventListener('show.bs.modal', function () {
            loginPage.style.filter = 'blur(12.5px)';
            loginPage.style.transition = 'filter 0.3s ease';
        });
        
        loginModal.addEventListener('hidden.bs.modal', function () {
            loginPage.style.filter = 'none';
        });
        
        // Register modal blur
        registerModal.addEventListener('show.bs.modal', function () {
            loginPage.style.filter = 'blur(12.5px)';
            loginPage.style.transition = 'filter 0.3s ease';
        });
        
        registerModal.addEventListener('hidden.bs.modal', function () {
            loginPage.style.filter = 'none';
        });

        // Show modal if there are errors
        @if ($errors->any())
            document.addEventListener('DOMContentLoaded', function() {
                var modal = new bootstrap.Modal(document.getElementById('loginModal'));
                modal.show();
            });
        @endif
    </script>
@endpush
