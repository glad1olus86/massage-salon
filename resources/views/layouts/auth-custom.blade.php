<!DOCTYPE html>
@php
    use App\Models\Utility;
    $setting = Utility::settings();
    $lang = \App::getLocale('lang');
    $languages = App\Models\Utility::languages();
@endphp

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <title>JOBSI - @yield('page-title')</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="description" content="JOBSI - Personal & Job Agency Performance Management" />
    <link rel="icon" href="{{ asset('fromfigma/jobsi_mobile.png') }}" type="image/png" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Amatic+SC:wght@700&family=Inter:wght@300;400;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: #FFFFFF;
            position: relative;
            overflow-x: hidden;
        }

        .login-page {
            min-height: 100vh;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .background-layer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(0deg, rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.8)),
                url('{{ asset('assets/images/login/background.jpg') }}');
            background-size: cover;
            background-position: center bottom;
            z-index: 0;
        }

        .main-content {
            position: relative;
            z-index: 1;
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 40px 20px;
        }

        /* Logo */
        .logo-container {
            margin-bottom: 30px;
        }

        .logo-container img {
            max-width: 350px;
            height: auto;
        }

        .tagline {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 14px;
            color: #000000;
            margin-top: -10px;
            margin-bottom: 40px;
        }

        /* Main Layout */
        .content-wrapper {
            display: flex;
            gap: 60px;
            align-items: flex-start;
            justify-content: center;
            flex-wrap: wrap;
            max-width: 1200px;
            width: 100%;
        }

        /* Left Side - Buttons */
        .buttons-section {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
        }

        .action-btn {
            width: 343px;
            height: 105px;
            border-radius: 55px;
            border: none;
            display: flex;
            align-items: center;
            padding: 0 30px;
            gap: 20px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.15);
        }

        .login-btn {
            background: #FFFCF2;
            box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.25);
        }

        .register-btn {
            background: rgba(255, 243, 243, 0.5);
            box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.25);
        }

        .btn-icon {
            width: 48px;
            height: 48px;
        }

        .btn-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .btn-title {
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            font-size: 20px;
            color: #000000;
            white-space: nowrap;
        }

        /* Smaller font for Russian and Ukrainian languages */
        html[lang="ru"] .btn-title,
        html[lang="uk"] .btn-title {
            font-size: 16px;
        }

        .btn-subtitle {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 18px;
            color: #FFAAAA;
        }

        /* Language Selector */
        .lang-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            cursor: pointer;
            position: relative;
        }

        .lang-flag {
            width: 39px;
            height: 27px;
            border-radius: 9px;
            object-fit: cover;
        }

        .lang-flag.globe-icon {
            width: 27px;
            height: 27px;
            border-radius: 50%;
            object-fit: contain;
        }

        .lang-text {
            font-family: 'Inter', sans-serif;
            font-weight: 300;
            font-size: 20px;
            color: #000000;
        }

        .lang-arrow {
            color: #FF0049;
            font-size: 12px;
        }

        .lang-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: none;
            min-width: 180px;
            z-index: 100;
        }

        .lang-dropdown.show {
            display: block;
        }

        .lang-dropdown a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            text-decoration: none;
            color: #000;
            transition: background 0.2s;
        }

        .lang-dropdown a:hover {
            background: #f5f5f5;
        }

        .lang-dropdown a.active {
            background: #fff3f3;
            color: #FF0049;
        }

        .lang-dropdown a img {
            width: 30px;
            height: 20px;
            border-radius: 4px;
            object-fit: cover;
        }

        .lang-dropdown a img.globe-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            object-fit: contain;
        }

        /* Right Side - Video */
        .video-section {
            position: relative;
        }

        .video-container {
            width: 472px;
            height: 314px;
            border: 2px solid #FF0049;
            border-radius: 35px;
            overflow: hidden;
            position: relative;
            background: #f5f5f5;
        }

        .video-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .play-button {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 64px;
            height: 64px;
            background: #FF0049;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .play-button:hover {
            transform: translate(-50%, -50%) scale(1.1);
        }

        .play-button::after {
            content: '';
            width: 0;
            height: 0;
            border-left: 20px solid white;
            border-top: 12px solid transparent;
            border-bottom: 12px solid transparent;
            margin-left: 5px;
        }

        .express-guide {
            position: absolute;
            top: 20px;
            right: 15px;
            font-family: 'Amatic SC', cursive;
            font-weight: 700;
            font-size: 45px;
            line-height: 44px;
            color: #000000;
            transform: rotate(21deg);
            text-align: center;
        }

        .express-guide span {
            display: block;
            color: #FF0049;
        }

        /* Footer */
        .footer {
            position: relative;
            z-index: 1;
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .footer-links a {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 15px;
            color: #000000;
            text-decoration: none;
        }

        .footer-links span {
            color: #000000;
        }

        /* Modal Styles - Figma Design */
        .login-modal .modal-dialog {
            max-width: 569px;
        }

        .login-modal .modal-content {
            border-radius: 55px;
            border: none;
            background: #FFF3F3;
            box-shadow: 0px 1px 1px rgba(0, 0, 0, 0.25);
            padding: 21px;
            position: relative;
            min-height: 579px;
        }

        .login-modal .modal-inner {
            background: #FFFFFF;
            border-radius: 55px;
            padding: 73px 76px 80px;
            min-height: 534px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-modal .modal-header {
            border-bottom: none;
            padding: 0;
            margin-bottom: 77px;
            position: relative;
            width: 100%;
        }

        .login-modal .modal-title {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 25px;
            line-height: 30px;
            color: #000000;
            text-align: center;
            width: 100%;
        }

        .login-modal .modal-title strong {
            font-weight: 700;
        }

        .login-modal .btn-close-custom {
            position: absolute;
            top: -30px;
            right: -30px;
            width: 59px;
            height: 59px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 10;
            transition: transform 0.2s;
        }

        .login-modal .btn-close-custom:hover {
            transform: scale(1.1);
        }

        .login-modal .btn-close-custom svg {
            width: 59px;
            height: 59px;
        }

        .login-modal .modal-body {
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-modal .modal-body form {
            width: 100%;
            max-width: 374px;
        }

        .login-modal .form-group {
            margin-bottom: 24px;
        }

        .login-modal .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background: #FFF3F3;
            border: 1px solid rgba(255, 0, 73, 0.25);
            border-radius: 20px;
            width: 374px;
            height: 63px;
            padding: 0 17px;
            gap: 17px;
            box-sizing: border-box;
        }

        .login-modal .input-wrapper:focus-within {
            border-color: #FF0049;
        }

        .login-modal .input-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
        }

        .login-modal .input-icon svg {
            width: 100%;
            height: 100%;
        }

        .login-modal .form-control {
            border: none;
            background: transparent;
            padding: 0;
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 20px;
            line-height: 24px;
            color: #000000;
            width: 100%;
            height: 100%;
        }

        .login-modal .form-control::placeholder {
            color: #000000;
        }

        .login-modal .form-control:focus {
            outline: none;
            box-shadow: none;
        }

        .login-modal .forgot-wrapper {
            text-align: center;
            margin-top: 28px;
            margin-bottom: 35px;
        }

        .login-modal .forgot-link {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 20px;
            line-height: 24px;
            color: #FF0049;
            text-decoration: none;
            border-bottom: 1px solid #FF0049;
            padding-bottom: 3px;
        }

        .login-modal .forgot-link:hover {
            color: #e00040;
            border-color: #e00040;
        }

        .login-modal .btn-wrapper {
            text-align: center;
        }

        .login-modal .btn-login {
            background: #FF0049;
            border: none;
            border-radius: 15px;
            width: 139px;
            height: 44px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 18px;
            line-height: 22px;
            color: #FFFFFF;
            cursor: pointer;
            transition: background 0.2s;
            padding: 0;
        }

        .login-modal .btn-login:hover {
            background: #e00040;
        }

        .login-modal .alert {
            border-radius: 15px;
            margin-bottom: 20px;
        }

        /* Modal backdrop blur */
        .login-modal.show~.login-page,
        body.modal-open .login-page {
            filter: blur(12.5px);
        }

        .login-modal .modal-backdrop {
            background: rgba(126, 126, 126, 0.2);
        }

        body.modal-open {
            overflow: hidden;
        }

        /* Override Bootstrap modal backdrop */
        .modal-backdrop.show {
            opacity: 0.3;
            background-color: #7e7e7e;
        }

        /* Register Modal - extends login modal styles */
        .register-modal .modal-content {
            min-height: 711px;
        }

        .register-modal .modal-inner {
            min-height: 666px;
            padding: 50px 76px 60px;
        }

        .register-modal .modal-header {
            margin-bottom: 50px;
        }

        .register-modal .modal-title {
            max-width: 300px;
            margin: 0 auto;
        }

        .register-modal .form-group {
            margin-bottom: 24px;
        }

        .register-modal .terms-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 35px;
            margin-bottom: 30px;
        }

        .register-modal .terms-checkbox {
            width: 24px;
            height: 24px;
            appearance: none;
            -webkit-appearance: none;
            border: 2px solid #FF0049;
            border-radius: 6px;
            cursor: pointer;
            position: relative;
            flex-shrink: 0;
        }

        .register-modal .terms-checkbox:checked {
            background: #FF0049;
        }

        .register-modal .terms-checkbox:checked::after {
            content: '';
            position: absolute;
            left: 7px;
            top: 3px;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .register-modal .terms-label {
            font-family: 'Inter', sans-serif;
            font-weight: 400;
            font-size: 16px;
            line-height: 19px;
            color: #000000;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .content-wrapper {
                flex-direction: column;
                align-items: center;
            }

            .video-section {
                margin-top: 40px;
            }

            .express-guide {
                right: 10px;
                font-size: 35px;
            }
        }

        @media (max-width: 576px) {
            .action-btn {
                width: 300px;
                height: 90px;
            }

            .video-container {
                width: 340px;
                height: 226px;
            }

            .logo-container img {
                max-width: 250px;
            }

            .express-guide {
                font-size: 28px;
                right: 5px;
                top: 10px;
            }

            /* Modal mobile styles */
            .login-modal .modal-dialog {
                max-width: calc(100% - 30px);
                margin: 15px auto;
            }

            .login-modal .modal-content {
                border-radius: 35px;
                padding: 15px;
                min-height: auto;
            }

            .login-modal .modal-inner {
                border-radius: 35px;
                padding: 40px 25px 50px;
                min-height: auto;
            }

            .login-modal .modal-header {
                margin-bottom: 40px;
            }

            .login-modal .modal-title {
                font-size: 20px;
                line-height: 26px;
            }

            .login-modal .btn-close-custom {
                top: -10px;
                right: -10px;
                width: 45px;
                height: 45px;
            }

            .login-modal .btn-close-custom svg {
                width: 45px;
                height: 45px;
            }

            .login-modal .input-wrapper {
                width: 100%;
                height: 55px;
                border-radius: 15px;
            }

            .login-modal .form-control {
                font-size: 16px;
            }

            .login-modal .forgot-link {
                font-size: 16px;
            }

            .login-modal .forgot-wrapper {
                margin-top: 20px;
                margin-bottom: 25px;
            }

            /* Register modal mobile */
            .register-modal .modal-inner {
                padding: 35px 25px 45px;
            }

            .register-modal .modal-header {
                margin-bottom: 30px;
            }

            .register-modal .terms-wrapper {
                margin-top: 25px;
                margin-bottom: 20px;
            }

            .register-modal .terms-label {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    @yield('content')

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>

</html>
