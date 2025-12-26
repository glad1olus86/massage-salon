@extends('layouts.auth-custom')

@php
    use App\Models\Utility;
    $currentLang = $lang ?? \App::getLocale();
@endphp

@section('page-title')
    {{ __('Forgot Password') }}
@endsection

@section('content')
    <div class="login-page">
        <div class="background-layer"></div>

        <div class="main-content">
            <!-- Logo -->
            <div class="logo-container">
                <a href="{{ route('login') }}">
                    <img src="{{ asset('assets/images/login/jobsi_logo.png') }}" alt="JOBSI">
                </a>
            </div>

            <!-- Forgot Password Card -->
            <div class="forgot-card">
                <div class="forgot-card-inner">
                    <div class="forgot-header">
                        <h2>{{ __('Forgot Password') }}</h2>
                        <p class="forgot-subtitle">{{ __('Enter your email to receive a password reset link') }}</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}" class="forgot-form">
                        @csrf

                        <div class="form-group">
                            <div class="input-wrapper">
                                <div class="input-icon">
                                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 4H4C2.9 4 2 4.9 2 6V18C2 19.1 2.9 20 4 20H20C21.1 20 22 19.1 22 18V6C22 4.9 21.1 4 20 4ZM20 8L12 13L4 8V6L12 11L20 6V8Z" fill="#FF0049"/>
                                    </svg>
                                </div>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ old('email') }}" placeholder="{{ __('Email') }}" required autofocus>
                            </div>
                        </div>

                        @if (isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'on')
                            @if (isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v2-checkbox')
                                <div class="form-group recaptcha-wrapper">
                                    {!! NoCaptcha::display() !!}
                                    @error('g-recaptcha-response')
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            @else
                                <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
                            @endif
                        @endif

                        <div class="btn-wrapper">
                            <button type="submit" class="btn-submit">
                                {{ __('Send Reset Link') }}
                            </button>
                        </div>

                        <div class="back-link-wrapper">
                            <a href="{{ route('login') }}" class="back-link">
                                ← {{ __('Back to Login') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="footer">
            <div class="footer-links">
                <a href="{{ route('login') }}">{{ __('back to homepage') }}</a>
            </div>
        </footer>
    </div>

    <style>
        /* Forgot Password Card */
        .forgot-card {
            background: #FFF3F3;
            border-radius: 55px;
            padding: 21px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.25);
        }

        .forgot-card-inner {
            background: #FFFFFF;
            border-radius: 55px;
            padding: 50px 60px;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .forgot-header h2 {
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 28px;
            color: #000000;
            margin-bottom: 10px;
        }

        .forgot-subtitle {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 14px;
            color: #666666;
        }

        .forgot-form .form-group {
            margin-bottom: 24px;
        }

        .forgot-form .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: #FFF3F3;
            border: 1px solid rgba(255, 0, 73, 0.25);
            border-radius: 20px;
            height: 63px;
            padding: 0 17px;
            gap: 17px;
        }

        .forgot-form .input-wrapper:focus-within {
            border-color: #FF0049;
        }

        .forgot-form .input-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .forgot-form .input-icon svg {
            width: 100%;
            height: 100%;
        }

        .forgot-form .form-control {
            border: none;
            background: transparent;
            padding: 0;
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 18px;
            color: #000000;
            width: 100%;
            height: 100%;
        }

        .forgot-form .form-control::placeholder {
            color: #999999;
        }

        .forgot-form .form-control:focus {
            outline: none;
            box-shadow: none;
        }

        .forgot-form .btn-wrapper {
            text-align: center;
            margin-top: 30px;
        }

        .forgot-form .btn-submit {
            background: #FF0049;
            border: none;
            border-radius: 15px;
            padding: 12px 40px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 16px;
            color: #FFFFFF;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .forgot-form .btn-submit:hover {
            background: #e00040;
            transform: translateY(-2px);
        }

        .back-link-wrapper {
            text-align: center;
            margin-top: 25px;
        }

        .back-link {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 16px;
            color: #FF0049;
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #e00040;
            text-decoration: underline;
        }

        .recaptcha-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .alert {
            border-radius: 15px;
            padding: 15px 20px;
            margin-bottom: 25px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .forgot-card {
                border-radius: 35px;
                padding: 15px;
                margin: 0 15px;
            }

            .forgot-card-inner {
                border-radius: 35px;
                padding: 35px 25px;
            }

            .forgot-header h2 {
                font-size: 24px;
            }

            .forgot-form .input-wrapper {
                height: 55px;
                border-radius: 15px;
            }

            .forgot-form .form-control {
                font-size: 16px;
            }
        }
    </style>
@endsection

@push('scripts')
    @if (isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'on')
        @if (isset($settings['google_recaptcha_version']) && $settings['google_recaptcha_version'] == 'v2-checkbox')
            {!! NoCaptcha::renderJs() !!}
        @else
            <script src="https://www.google.com/recaptcha/api.js?render={{ $settings['google_recaptcha_key'] }}"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    grecaptcha.ready(function() {
                        grecaptcha.execute('{{ $settings['google_recaptcha_key'] }}', {
                            action: 'submit'
                        }).then(function(token) {
                            document.getElementById('g-recaptcha-response').value = token;
                        });
                    });
                });
            </script>
        @endif
    @endif
@endpush
