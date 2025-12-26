<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="JOBSI - Personal & Job Agency Performance Management" />
    <title>JOBSI Mobile</title>
    <link rel="icon" href="{{ asset('fromfigma/jobsi_mobile.png') }}" type="image/png" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/fonts/tabler-icons.min.css') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8f9fa;
            color: #000;
        }
        .mobile-container { max-width: 100%; background: #fff; min-height: 100vh; }
        
        /* Header */
        .mobile-header {
            background: #fff;
            padding: 12px 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .mobile-header-row { display: flex; align-items: center; justify-content: space-between; }
        .mobile-header-left { display: flex; align-items: center; gap: 10px; }
        .mobile-header-right { display: flex; align-items: center; gap: 15px; }
        .mobile-header-btn {
            width: 54px;
            height: 54px;
            background: #fff;
            border: 1px solid #FFE0E6;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            cursor: pointer;
        }
        .mobile-header-btn img { width: 24px; height: 24px; }
        .mobile-lang-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            background: transparent;
            border: none;
            font-size: 14px;
            color: #000;
            cursor: pointer;
            padding: 8px;
        }
        .mobile-flag { width: 29px; height: 19px; object-fit: cover; border-radius: 2px; }
        .mobile-logo { display: flex; align-items: center; }
        .mobile-logo img { height: 50px; width: auto; }
        
        /* Sidebar */
        .mobile-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(0deg, rgba(255,255,255,0.9), rgba(255,255,255,0.9)), url('{{ asset("fromfigma/background.jpg") }}');
            background-size: cover;
            z-index: 1000;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
        }
        .mobile-sidebar.active { transform: translateX(0); }
        .mobile-sidebar::-webkit-scrollbar { display: none; }
        .mobile-sidebar { -ms-overflow-style: none; scrollbar-width: none; }
        .mobile-sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            display: none;
        }
        .mobile-sidebar-overlay.active { display: block; }
        .mobile-sidebar-close {
            position: absolute;
            top: 15px;
            right: -50px;
            width: 40px;
            height: 40px;
            background: #FF0049;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #fff;
            font-size: 20px;
        }
        .mobile-sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #f0f0f0;
        }
        .mobile-sidebar-header img { height: 40px; }
        .mobile-sidebar-menu { padding: 15px 10px; }
        .mobile-sidebar-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            text-decoration: none;
            color: #000;
            border-radius: 8px;
            margin-bottom: 5px;
            font-size: 15px;
            font-weight: 300;
        }
        .mobile-sidebar-item:hover, .mobile-sidebar-item.active {
            background: #FFF4F4;
            color: #000;
        }
        .mobile-sidebar-item img { width: 20px; height: 20px; }
        .mobile-sidebar-google {
            position: relative;
            margin: 10px 15px;
            background: #fff;
            border-radius: 8px;
            padding: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        .mobile-sidebar-google small { font-size: 10px; display: block; margin-bottom: 3px; }
        .mobile-sidebar-google img { max-width: 80px; height: auto; }
        
        /* Quick Actions */
        .mobile-actions {
            background: #FFFFFF;
            box-shadow: 0px 2.8px 7px 0.7px rgba(0, 0, 0, 0.07);
            border-radius: 5px;
            margin: 10px;
            padding: 8px;
        }
        .mobile-actions-row {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
        }
        .mobile-actions-row:last-child { margin-bottom: 0; }
        .mobile-action-btn {
            flex: 1;
            aspect-ratio: 1;
            background: #FFF0F4;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            padding: 10px 5px;
        }
        .mobile-action-btn-full {
            flex: 1;
            aspect-ratio: auto;
            height: 80px;
            flex-direction: row;
            gap: 15px;
        }
        .mobile-action-btn-full .mobile-action-icon {
            margin-bottom: 0;
        }
        .mobile-action-icon {
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }
        .mobile-action-icon img { width: 42px; height: 42px; object-fit: contain; }
        .mobile-action-label {
            font-family: 'Open Sans', sans-serif;
            font-weight: 600;
            font-size: 9px;
            color: #000;
            text-align: center;
            text-transform: uppercase;
            line-height: 12px;
        }
        
        /* Content */
        .mobile-content { padding: 15px; }
        
        /* Widget */
        .mobile-widget {
            background: #fff;
            box-shadow: 0px 4px 14px rgba(0,0,0,0.07);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 55px;
        }
        .mobile-widget-header { margin-bottom: 12px; }
        .mobile-filter-dropdown {
            color: #FF0049;
            font-family: 'Open Sans', sans-serif;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        /* Stats */
        .mobile-workplace-stats { margin-bottom: 15px; }
        .mobile-stat-row { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .mobile-stat-label { font-weight: 300; font-size: 14px; color: #000; min-width: 130px; }
        .mobile-stat-num { font-weight: 700; font-size: 18px; color: #000; min-width: 40px; }
        .mobile-progress-green { height: 18px; background: #22B404; }
        .mobile-progress-red { height: 18px; background: #F8ABAB; }
        
        /* Fluctuation */
        .mobile-fluctuation { display: flex; align-items: center; justify-content: space-between; margin-top: 15px; }
        .mobile-fluctuation-left { display: flex; align-items: center; gap: 8px; }
        .mobile-fluctuation-label { font-weight: 700; font-size: 18px; color: #000; }
        .mobile-fluctuation-value { font-weight: 700; font-size: 36px; color: #000; }
        
        /* Warning */
        .mobile-warning-icon { position: relative; display: inline-flex; margin-left: 5px; vertical-align: middle; }
        .mobile-warning-exclamation { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -35%); color: #fff; font-weight: 700; font-size: 10px; }
        
        /* Hotel */
        .mobile-hotel-stats { margin-bottom: 10px; }
        .mobile-hotel-stat { font-weight: 300; font-size: 14px; color: #000; margin-bottom: 5px; line-height: 1.6; }
        .mobile-hotel-num { font-weight: 700; font-size: 16px; }
        
        /* Footer */
        .mobile-widget-footer { position: absolute; bottom: 0; left: 15px; right: 15px; }
        .mobile-widget-footer-line { height: 3px; background: #FF0049; margin-bottom: 8px; }
        .mobile-widget-footer-text { font-weight: 400; font-size: 12px; color: #B1B0AF; display: block; padding-bottom: 10px; }
        
        /* Dropdown */
        .dropdown-menu { border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .dropdown-item { font-size: 14px; padding: 8px 15px; }
        .dropdown-item:active { background: #FF0049; }
        
        /* Modal styles for mobile */
        .modal-dialog { margin: 10px; max-width: calc(100% - 20px); }
        .modal-content { border-radius: 12px; }
        .modal-header { border-bottom: 1px solid #f0f0f0; padding: 15px; }
        .modal-header .modal-title { font-size: 16px; font-weight: 600; }
        .modal-body { padding: 15px; max-height: 70vh; overflow-y: auto; }
        .modal-footer { border-top: 1px solid #f0f0f0; padding: 12px 15px; }
        .modal .form-group { margin-bottom: 15px; }
        .modal .form-label { font-size: 14px; font-weight: 500; margin-bottom: 5px; }
        .modal .form-control { font-size: 14px; padding: 10px 12px; border-radius: 8px; }
        .modal .btn { padding: 10px 20px; border-radius: 8px; font-size: 14px; }
        .modal .btn-primary { background: #FF0049; border-color: #FF0049; }
        .modal .btn-primary:hover { background: #e00040; border-color: #e00040; }
        
        /* Toast notification */
        #liveToast { min-width: 250px; }
        
        /* Form elements in modal */
        .modal select.form-control { appearance: auto; }
        .modal .choose-file input[type="file"] { font-size: 12px; }
        .modal .alert { font-size: 13px; padding: 10px; }
        .modal .card { margin-bottom: 10px; }
        .modal .card-body { padding: 12px; }
        
        /* Card Stats */
        .mobile-card-stats {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }
        .mobile-stat-item {
            text-align: center;
            flex: 1;
        }
        .mobile-stat-label {
            display: block;
            font-size: 11px;
            color: #666;
            margin-bottom: 2px;
        }
        .mobile-stat-value {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: #000;
        }
        
        /* Occupants list */
        .mobile-occupants {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .mobile-occupant-item {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f8f9fa;
            padding: 4px 8px;
            border-radius: 20px;
        }
        .mobile-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #FF0049;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 600;
            overflow: hidden;
            flex-shrink: 0;
        }
        .mobile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .mobile-avatar-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #FF0049;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
        }
        .mobile-avatar-small {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: #FF0049;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 600;
            overflow: hidden;
        }
        .mobile-avatar-small img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .mobile-occupant-name {
            font-size: 12px;
            color: #333;
        }
        .mobile-occupant-more {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        
        /* List items */
        .mobile-list-item {
            display: flex;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
        }
        .mobile-list-item:last-child {
            border-bottom: none;
        }
        
        /* Empty state */
        .mobile-empty-state {
            text-align: center;
            padding: 40px 20px;
        }
        
        /* Section Title */
        .mobile-section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            margin-bottom: 12px;
            border-bottom: 2px solid #FFE0E6;
        }
        .mobile-section-title-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mobile-section-title-left span {
            font-size: 16px;
            font-weight: 600;
            color: #FF0049;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .mobile-add-btn {
            width: 40px;
            height: 40px;
            background: #FFE0E6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .mobile-add-btn:hover {
            background: #FF0049;
        }
        .mobile-add-btn:hover svg {
            stroke: #fff;
        }
        
        /* Section Header (inside cards) */
        .mobile-section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 12px;
            margin-bottom: 12px;
            border-bottom: 1px solid #FFE0E6;
        }
        .mobile-section-header span {
            font-size: 14px;
            font-weight: 600;
            color: #FF0049;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    {{-- Sidebar Overlay --}}
    <div class="mobile-sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
    
    {{-- Sidebar --}}
    <div class="mobile-sidebar" id="mobileSidebar">
        <button class="mobile-sidebar-close" onclick="closeSidebar()">✕</button>
        <div class="mobile-sidebar-header">
            <img src="{{ asset('fromfigma/jobsi_logo.png') }}" alt="JOBSI">
        </div>
        @php use App\Services\PlanModuleService; @endphp
        <div class="mobile-sidebar-menu">
            <a href="{{ route('mobile.dashboard') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.dashboard') ? 'active' : '' }}">
                <img src="{{ asset('fromfigma/mainpanel.svg') }}" alt=""> {{ __('Dashboard') }}
            </a>
            @if(PlanModuleService::hasModule('workers'))
            <a href="{{ route('mobile.workers.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.workers.*') ? 'active' : '' }}">
                <img src="{{ asset('fromfigma/workers.svg') }}" alt=""> {{ __('Workers') }}
            </a>
            @endif
            @if(PlanModuleService::hasModule('workplaces'))
            <a href="{{ route('mobile.workplaces.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.workplaces.*') ? 'active' : '' }}">
                <img src="{{ asset('fromfigma/workplaces.svg') }}" alt=""> {{ __('Work Places') }}
            </a>
            @endif
            @if(PlanModuleService::hasModule('hotels'))
            <a href="{{ route('mobile.hotels.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.hotels.*') ? 'active' : '' }}">
                <img src="{{ asset('fromfigma/hotel.svg') }}" alt=""> {{ __('Accommodation') }}
            </a>
            @endif
            @if(PlanModuleService::hasModule('vehicles'))
            <a href="{{ route('mobile.vehicles.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.vehicles.*') ? 'active' : '' }}">
                <img src="{{ asset('fromfigma/vehicles.svg') }}" alt=""> {{ __('Vehicles') }}
            </a>
            @endif
            @if(PlanModuleService::hasModule('documents'))
            <a href="{{ route('mobile.documents.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.documents.*') ? 'active' : '' }}">
                <img src="{{ asset('fromfigma/document.svg') }}" alt=""> {{ __('Documents') }}
            </a>
            @endif
            @if(PlanModuleService::hasModule('cashbox'))
            <a href="{{ route('mobile.cashbox.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.cashbox.*') ? 'active' : '' }}">
                <img src="{{ asset('fromfigma/cashbox.svg') }}" alt=""> {{ __('Cashbox') }}
            </a>
            @endif
            @if(PlanModuleService::hasModule('calendar'))
            <a href="{{ route('mobile.calendar.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.calendar.*') ? 'active' : '' }}">
                <img src="{{ asset('fromfigma/calendar.svg') }}" alt=""> {{ __('Calendar') }}
            </a>
            @endif
            @if(PlanModuleService::hasModule('notifications'))
            <a href="{{ route('mobile.notifications.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.notifications.*') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                {{ __('Notifications') }}
            </a>
            @endif
            <a href="{{ route('mobile.profile.index') }}" class="mobile-sidebar-item {{ Request::routeIs('mobile.profile.*') ? 'active' : '' }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                {{ __('Profile') }}
            </a>
        </div>
    </div>

    <div class="mobile-container">
        @yield('content')
    </div>
    
    {{-- Common Modal for AJAX popups --}}
    <div class="modal fade" id="commonModal" tabindex="-1" role="dialog" aria-labelledby="commonModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="commonModalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="body">
                </div>
            </div>
        </div>
    </div>
    
    {{-- Toast notification --}}
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 99999">
        <div id="liveToast" class="toast text-white fade" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openSidebar() {
            document.getElementById('mobileSidebar').classList.add('active');
            document.getElementById('sidebarOverlay').classList.add('active');
        }
        function closeSidebar() {
            document.getElementById('mobileSidebar').classList.remove('active');
            document.getElementById('sidebarOverlay').classList.remove('active');
        }
        
        // Toast notification function
        function show_toastr(type, message) {
            var toast = document.getElementById('liveToast');
            toast.classList.remove('bg-primary', 'bg-danger', 'bg-success');
            if (type == 'success') {
                toast.classList.add('bg-success');
            } else {
                toast.classList.add('bg-danger');
            }
            toast.querySelector('.toast-body').innerHTML = message;
            new bootstrap.Toast(toast).show();
        }
        
        // AJAX popup handler for mobile
        $(document).on('click', 'a[data-ajax-popup="true"], button[data-ajax-popup="true"]', function(e) {
            e.preventDefault();
            
            var title = $(this).data('title') || $(this).data('bs-original-title') || '';
            var size = $(this).data('size') || 'md';
            var url = $(this).data('url');
            
            $('#commonModal .modal-dialog').removeClass('modal-sm modal-md modal-lg modal-xl');
            $('#commonModal .modal-title').html(title);
            $('#commonModal .modal-dialog').addClass('modal-' + size);
            
            // Show loading
            $('#commonModal .body').html('<div class="modal-body text-center py-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            $('#commonModal').modal('show');
            
            $.ajax({
                url: url,
                success: function(data) {
                    $('#commonModal .body').html(data);
                },
                error: function(data) {
                    var errorMsg = data.responseJSON ? data.responseJSON.error : 'Error loading content';
                    $('#commonModal .body').html('<div class="modal-body"><div class="alert alert-danger">' + errorMsg + '</div></div>');
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>