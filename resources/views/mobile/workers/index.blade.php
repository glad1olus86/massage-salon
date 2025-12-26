@extends('layouts.mobile')

@php use App\Services\NationalityFlagService; @endphp

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            {{-- Left side: Menu + Notifications --}}
            <div class="mobile-header-left">
                <button type="button" class="mobile-header-btn" onclick="openSidebar()">
                    <img src="{{ asset('fromfigma/menu_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><line x1=3 y1=6 x2=21 y2=6></line><line x1=3 y1=12 x2=21 y2=12></line><line x1=3 y1=18 x2=21 y2=18></line></svg>'">
                </button>
                <a href="{{ route('notifications.index') }}" class="mobile-header-btn">
                    <img src="{{ asset('fromfigma/bell_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\'></path><path d=\'M13.73 21a2 2 0 0 1-3.46 0\'></path></svg>'">
                </a>
            </div>

            {{-- Right side: Language + Logo --}}
            <div class="mobile-header-right">
                <div class="dropdown">
                    <button class="mobile-lang-btn" data-bs-toggle="dropdown">
                        @php $lang = app()->getLocale(); @endphp
                        @if ($lang == 'cs')
                            <img src="{{ asset('fromfigma/czech_flag.svg') }}" alt="CS" class="mobile-flag">
                        @elseif ($lang == 'uk')
                            <img src="{{ asset('fromfigma/ukraine_flag.png') }}" alt="UK" class="mobile-flag">
                        @elseif ($lang == 'ru')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                            </svg>
                        @else
                            <img src="{{ asset('fromfigma/uk_flag.png') }}" alt="EN" class="mobile-flag">
                        @endif
                        <span>{{ strtoupper($lang) }}</span>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="#000">
                            <path d="M7 10l5 5 5-5z" />
                        </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @foreach (['ru' => 'Русский', 'en' => 'English', 'cs' => 'Čeština', 'uk' => 'Українська'] as $code => $language)
                            <a href="{{ route('change.language', $code) }}" class="dropdown-item {{ $lang == $code ? 'text-primary' : '' }}">{{ $language }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="mobile-logo">
                    <img src="{{ asset('fromfigma/jobsi_mobile.png') }}" alt="JOBSI">
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-content">
        {{-- Page Title --}}
        <div class="mobile-section-title">
            <div class="mobile-section-title-left">
                <img src="{{ asset('fromfigma/workers.svg') }}" alt="" width="22" height="22" 
                     onerror="this.outerHTML='<svg width=22 height=22 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2\'></path><circle cx=9 cy=7 r=4></circle><path d=\'M23 21v-2a4 4 0 0 0-3-3.87\'></path><path d=\'M16 3.13a4 4 0 0 1 0 7.75\'></path></svg>'">
                <span>{{ __('Workers') }}</span>
            </div>
            @can('create worker')
                <a href="#" data-url="{{ route('worker.create') }}" data-ajax-popup="true" 
                   data-title="{{ __('Add New Worker') }}" data-size="lg" class="mobile-add-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </a>
            @endcan
        </div>

        {{-- Search & Filter --}}
        <div class="mb-3">
            <form action="{{ route('mobile.workers.index') }}" method="GET" id="filterForm">
                <div class="mobile-search-wrapper">
                    <div class="mobile-search-box">
                        <i class="ti ti-search mobile-search-icon"></i>
                        <input type="text" id="liveSearchInput" class="mobile-search-input" 
                               placeholder="{{ __('Search by name') }}..." 
                               autocomplete="off">
                        <span class="mobile-search-clear" id="clearSearch" style="display: none;">
                            <i class="ti ti-x"></i>
                        </span>
                    </div>
                    <button type="button" class="mobile-filter-toggle {{ request()->hasAny(['hotel_id', 'workplace_id', 'nationality', 'gender']) ? 'has-filters' : '' }}" onclick="toggleFilters()">
                        <i class="ti ti-adjustments-horizontal"></i>
                        @if(request()->hasAny(['hotel_id', 'workplace_id', 'nationality', 'gender']))
                            <span class="filter-count">{{ collect([request('hotel_id'), request('workplace_id'), request('nationality'), request('gender')])->filter()->count() }}</span>
                        @endif
                    </button>
                </div>
                
                {{-- Search Hint --}}
                <div class="search-hint" id="searchHint">
                    <i class="ti ti-info-circle"></i>
                    <span>{{ __('Type at least 2 characters to search') }}</span>
                </div>
                
                {{-- Filter Panel --}}
                <div id="filterPanel" class="mobile-filter-panel {{ request()->hasAny(['hotel_id', 'workplace_id', 'nationality', 'gender']) ? 'show' : '' }}">
                    <div class="filter-panel-header">
                        <span class="filter-panel-title">
                            <i class="ti ti-filter me-2"></i>{{ __('Filters') }}
                        </span>
                        <button type="button" class="filter-panel-close" onclick="toggleFilters()">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
                    
                    <div class="filter-panel-body">
                        {{-- Accommodation Filter --}}
                        <div class="filter-section">
                            <div class="filter-section-header">
                                <i class="ti ti-home-2"></i>
                                <span>{{ __('Accommodation') }}</span>
                            </div>
                            <select name="hotel_id" class="filter-select">
                                <option value="">{{ __('All accommodations') }}</option>
                                @foreach($hotels as $hotel)
                                    <option value="{{ $hotel->id }}" {{ request('hotel_id') == $hotel->id ? 'selected' : '' }}>
                                        {{ $hotel->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Workplace Filter --}}
                        <div class="filter-section">
                            <div class="filter-section-header">
                                <i class="ti ti-building-factory-2"></i>
                                <span>{{ __('Work Place') }}</span>
                            </div>
                            <select name="workplace_id" class="filter-select">
                                <option value="">{{ __('All workplaces') }}</option>
                                @foreach($workplaces as $workplace)
                                    <option value="{{ $workplace->id }}" {{ request('workplace_id') == $workplace->id ? 'selected' : '' }}>
                                        {{ $workplace->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Nationality Filter --}}
                        <div class="filter-section">
                            <div class="filter-section-header">
                                <i class="ti ti-flag"></i>
                                <span>{{ __('Nationality') }}</span>
                            </div>
                            <select name="nationality" class="filter-select">
                                <option value="">{{ __('All nationalities') }}</option>
                                @foreach($nationalities as $nat)
                                    <option value="{{ $nat }}" {{ request('nationality') == $nat ? 'selected' : '' }}>
                                        {{ $nat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Gender Filter --}}
                        <div class="filter-section">
                            <div class="filter-section-header">
                                <i class="ti ti-gender-bigender"></i>
                                <span>{{ __('Gender') }}</span>
                            </div>
                            <div class="gender-toggle-group">
                                <label class="gender-toggle {{ in_array('male', (array)request('gender', [])) ? 'active' : '' }}">
                                    <input type="checkbox" name="gender[]" value="male" 
                                           {{ in_array('male', (array)request('gender', [])) ? 'checked' : '' }}>
                                    <i class="ti ti-man"></i>
                                    <span>{{ __('Male') }}</span>
                                </label>
                                <label class="gender-toggle {{ in_array('female', (array)request('gender', [])) ? 'active' : '' }}">
                                    <input type="checkbox" name="gender[]" value="female"
                                           {{ in_array('female', (array)request('gender', [])) ? 'checked' : '' }}>
                                    <i class="ti ti-woman"></i>
                                    <span>{{ __('Female') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Filter Actions --}}
                    <div class="filter-panel-footer">
                        <a href="{{ route('mobile.workers.index') }}" class="filter-btn-reset">
                            <i class="ti ti-refresh me-1"></i>{{ __('Reset') }}
                        </a>
                        <button type="submit" class="filter-btn-apply">
                            <i class="ti ti-check me-1"></i>{{ __('Apply Filters') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        {{-- Active Filters Display --}}
        @if(request()->hasAny(['hotel_id', 'workplace_id', 'nationality', 'gender']))
            <div class="active-filters-bar mb-3">
                <div class="active-filters-scroll">
                    @if(request('hotel_id'))
                        <a href="{{ route('mobile.workers.index', request()->except('hotel_id')) }}" class="active-filter-chip">
                            <i class="ti ti-home-2"></i>
                            <span>{{ $hotels->find(request('hotel_id'))?->name }}</span>
                            <i class="ti ti-x chip-remove"></i>
                        </a>
                    @endif
                    @if(request('workplace_id'))
                        <a href="{{ route('mobile.workers.index', request()->except('workplace_id')) }}" class="active-filter-chip">
                            <i class="ti ti-building-factory-2"></i>
                            <span>{{ $workplaces->find(request('workplace_id'))?->name }}</span>
                            <i class="ti ti-x chip-remove"></i>
                        </a>
                    @endif
                    @if(request('nationality'))
                        <a href="{{ route('mobile.workers.index', request()->except('nationality')) }}" class="active-filter-chip">
                            {!! NationalityFlagService::getFlagHtml(request('nationality'), 14) !!}
                            <span>{{ request('nationality') }}</span>
                            <i class="ti ti-x chip-remove"></i>
                        </a>
                    @endif
                    @if(request('gender'))
                        @foreach((array)request('gender') as $g)
                            <span class="active-filter-chip no-remove">
                                <i class="ti ti-{{ $g == 'male' ? 'man' : 'woman' }}"></i>
                                <span>{{ $g == 'male' ? __('Male') : __('Female') }}</span>
                            </span>
                        @endforeach
                    @endif
                </div>
                <a href="{{ route('mobile.workers.index') }}" class="clear-all-filters">
                    {{ __('Clear all') }}
                </a>
            </div>
        @endif
        
        {{-- Results Count --}}
        <div class="results-count mb-2">
            <span>{{ __('Found') }}: <strong id="resultsCountNumber">{{ $workers->total() ?? $workers->count() }}</strong> {{ __('workers') }}</span>
        </div>

        {{-- Workers List --}}
        <div id="workersList">
            @forelse($workers as $worker)
                <div class="mobile-card worker-card mb-3" 
                     data-name="{{ strtolower($worker->first_name . ' ' . $worker->last_name) }}"
                     data-nationality="{{ strtolower($worker->nationality ?? '') }}"
                     onclick="window.location='{{ route('mobile.workers.show', $worker->id) }}'">
                    <div class="d-flex align-items-center">
                        <div class="mobile-avatar me-3">
                            @if(!empty($worker->photo))
                                <img src="{{ asset('uploads/worker_photos/' . $worker->photo) }}" alt="">
                            @else
                                <div class="mobile-avatar-placeholder">
                                    {{ strtoupper(substr($worker->first_name, 0, 1)) }}{{ strtoupper(substr($worker->last_name, 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1 worker-name">{{ $worker->first_name }} {{ $worker->last_name }}</h6>
                            <div class="mobile-card-meta">
                                <div class="mobile-card-meta-row">
                                    <span>{!! NationalityFlagService::getFlagHtml($worker->nationality, 16) !!}</span>
                                    @if($worker->currentWorkAssignment)
                                        <span class="mobile-badge mobile-badge-working">{{ $worker->currentWorkAssignment->workPlace->name }}</span>
                                    @else
                                        <span class="mobile-badge mobile-badge-not-working">{{ __('Not employed') }}</span>
                                    @endif
                                </div>
                                @if($worker->currentAssignment)
                                    <div class="mobile-card-meta-row" style="margin-left: 30px;">
                                        <span class="mobile-badge mobile-badge-housed">{{ $worker->currentAssignment->hotel->name }}, {{ $worker->currentAssignment->room->room_number }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="mobile-card-arrow">
                            <i class="ti ti-chevron-right"></i>
                        </div>
                    </div>
                </div>
            @empty
                <div class="empty-state text-center py-5">
                    <i class="ti ti-users-off" style="font-size: 48px; opacity: 0.3;"></i>
                    <p class="mt-3 text-muted">{{ __('No workers found') }}</p>
                </div>
            @endforelse
        </div>
        
        {{-- No Results Message (hidden by default) --}}
        <div id="noSearchResults" class="text-center py-5" style="display: none;">
            <i class="ti ti-search-off" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-3 text-muted">{{ __('No workers match your search') }}</p>
        </div>

        {{-- Pagination --}}
        @if($workers instanceof \Illuminate\Pagination\LengthAwarePaginator && $workers->hasPages())
            <div class="mobile-pagination mt-3">
                {{-- Previous --}}
                @if($workers->onFirstPage())
                    <span class="page-btn disabled">&lsaquo;</span>
                @else
                    <a href="{{ $workers->previousPageUrl() }}" class="page-btn">&lsaquo;</a>
                @endif

                @php
                    $currentPage = $workers->currentPage();
                    $lastPage = $workers->lastPage();
                    $showPages = [];
                    
                    if ($lastPage <= 9) {
                        // Show all pages if 9 or less
                        for ($i = 1; $i <= $lastPage; $i++) {
                            $showPages[] = $i;
                        }
                    } else {
                        // Smart pagination
                        if ($currentPage <= 5) {
                            // Near start: 1 2 3 4 5 ... 13 14
                            for ($i = 1; $i <= 5; $i++) {
                                $showPages[] = $i;
                            }
                            $showPages[] = '...';
                            $showPages[] = $lastPage - 1;
                            $showPages[] = $lastPage;
                        } elseif ($currentPage >= $lastPage - 4) {
                            // Near end: 1 2 ... 10 11 12 13 14
                            $showPages[] = 1;
                            $showPages[] = 2;
                            $showPages[] = '...';
                            for ($i = $lastPage - 4; $i <= $lastPage; $i++) {
                                $showPages[] = $i;
                            }
                        } else {
                            // Middle: 1 2 ... 6 7 8 ... 13 14
                            $showPages[] = 1;
                            $showPages[] = 2;
                            $showPages[] = '...';
                            $showPages[] = $currentPage - 1;
                            $showPages[] = $currentPage;
                            $showPages[] = $currentPage + 1;
                            $showPages[] = '...';
                            $showPages[] = $lastPage - 1;
                            $showPages[] = $lastPage;
                        }
                    }
                @endphp

                @foreach($showPages as $page)
                    @if($page === '...')
                        <span class="page-dots">...</span>
                    @elseif($page == $currentPage)
                        <span class="page-btn active">{{ $page }}</span>
                    @else
                        <a href="{{ $workers->url($page) }}" class="page-btn">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if($workers->hasMorePages())
                    <a href="{{ $workers->nextPageUrl() }}" class="page-btn">&rsaquo;</a>
                @else
                    <span class="page-btn disabled">&rsaquo;</span>
                @endif
            </div>
        @endif
    </div>

    <style>
        /* Worker Cards */
        .mobile-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .mobile-badge-working {
            background: #22B404;
            color: #fff;
        }
        .mobile-badge-not-working {
            background: #999;
            color: #fff;
        }
        .mobile-badge-housed {
            background: #FF0049;
            color: #fff;
        }
        .mobile-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #f0f0f0;
        }
        .mobile-card:active {
            transform: scale(0.98);
            box-shadow: 0 1px 6px rgba(0,0,0,0.08);
        }
        .mobile-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
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
            background: linear-gradient(135deg, #FF0049, #FF6B6B);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }
        .mobile-card-meta {
            font-size: 12px;
            color: #666;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 6px;
            margin-top: 4px;
        }
        .mobile-card-meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .mobile-card-arrow {
            color: #ccc;
        }
        
        /* Search Box */
        .mobile-search-wrapper {
            display: flex;
            gap: 10px;
            align-items: stretch;
        }
        .mobile-search-box {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
        }
        .mobile-search-icon {
            position: absolute;
            left: 14px;
            color: #999;
            font-size: 18px;
            pointer-events: none;
        }
        .mobile-search-input {
            width: 100%;
            padding: 12px 40px 12px 42px;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            font-size: 15px;
            background: #fff;
            transition: all 0.2s ease;
        }
        .mobile-search-input:focus {
            outline: none;
            border-color: #FF0049;
            box-shadow: 0 0 0 3px rgba(255, 0, 73, 0.1);
        }
        .mobile-search-input::placeholder {
            color: #aaa;
        }
        .mobile-search-clear {
            position: absolute;
            right: 12px;
            color: #999;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-search-clear:hover {
            color: #FF0049;
        }
        
        /* Filter Toggle Button */
        .mobile-filter-toggle {
            width: 48px;
            height: 48px;
            border: 1px solid #e8e8e8;
            border-radius: 12px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 20px;
            position: relative;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .mobile-filter-toggle:hover,
        .mobile-filter-toggle.has-filters {
            border-color: #FF0049;
            color: #FF0049;
            background: #FFF5F7;
        }
        .filter-count {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            background: #FF0049;
            color: #fff;
            border-radius: 50%;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(255, 0, 73, 0.4);
        }
        
        /* Filter Panel */
        .mobile-filter-panel {
            background: #fff;
            border-radius: 16px;
            margin-top: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border: 1px solid #f0f0f0;
            overflow: hidden;
            display: none;
            animation: slideDown 0.25s ease;
        }
        .mobile-filter-panel.show {
            display: block;
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .filter-panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            background: #FAFAFA;
        }
        .filter-panel-title {
            font-weight: 600;
            font-size: 15px;
            color: #333;
            display: flex;
            align-items: center;
        }
        .filter-panel-title i {
            color: #FF0049;
        }
        .filter-panel-close {
            width: 32px;
            height: 32px;
            border: none;
            background: #fff;
            border-radius: 8px;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-panel-close:hover {
            background: #FFF0F4;
            color: #FF0049;
        }
        .filter-panel-body {
            padding: 16px;
        }
        .filter-section {
            margin-bottom: 18px;
        }
        .filter-section:last-child {
            margin-bottom: 0;
        }
        .filter-section-header {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .filter-section-header i {
            color: #FF0049;
            font-size: 16px;
        }
        .filter-select {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            color: #333;
            background: #fff;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23999' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-select:focus {
            outline: none;
            border-color: #FF0049;
            box-shadow: 0 0 0 3px rgba(255, 0, 73, 0.1);
        }
        
        /* Gender Toggle */
        .gender-toggle-group {
            display: flex;
            gap: 10px;
        }
        .gender-toggle {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: #fff;
        }
        .gender-toggle input {
            display: none;
        }
        .gender-toggle i {
            font-size: 18px;
            color: #888;
        }
        .gender-toggle span {
            font-size: 14px;
            font-weight: 500;
            color: #666;
        }
        .gender-toggle:hover {
            border-color: #FF0049;
            background: #FFF8FA;
        }
        .gender-toggle.active {
            border-color: #FF0049;
            background: linear-gradient(135deg, #FFF0F4 0%, #FFE8EE 100%);
        }
        .gender-toggle.active i,
        .gender-toggle.active span {
            color: #FF0049;
        }
        
        /* Filter Actions */
        .filter-panel-footer {
            display: flex;
            gap: 10px;
            padding: 14px 16px;
            border-top: 1px solid #f0f0f0;
            background: #FAFAFA;
        }
        .filter-btn-reset {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background: #fff;
            color: #666;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
        }
        .filter-btn-reset:hover {
            background: #f5f5f5;
            color: #333;
            text-decoration: none;
        }
        .filter-btn-apply {
            flex: 1.5;
            padding: 12px 16px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #FF0049 0%, #FF3366 100%);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(255, 0, 73, 0.3);
        }
        .filter-btn-apply:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(255, 0, 73, 0.4);
        }
        .filter-btn-apply:active {
            transform: translateY(0);
        }
        
        /* Active Filters Bar */
        .active-filters-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }
        .active-filters-scroll {
            flex: 1;
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .active-filters-scroll::-webkit-scrollbar {
            display: none;
        }
        .active-filter-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            background: linear-gradient(135deg, #FFF0F4 0%, #FFE8EE 100%);
            border: 1px solid #FFD6E0;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            color: #FF0049;
            white-space: nowrap;
            text-decoration: none;
            transition: all 0.2s;
            flex-shrink: 0;
        }
        .active-filter-chip:hover {
            background: #FFE0E8;
            color: #E00040;
            text-decoration: none;
        }
        .active-filter-chip.no-remove {
            cursor: default;
        }
        .active-filter-chip i:first-child {
            font-size: 14px;
        }
        .chip-remove {
            font-size: 12px;
            opacity: 0.7;
            margin-left: 2px;
        }
        .active-filter-chip:hover .chip-remove {
            opacity: 1;
        }
        .clear-all-filters {
            font-size: 12px;
            color: #999;
            text-decoration: none;
            white-space: nowrap;
            padding: 6px 0;
            flex-shrink: 0;
        }
        .clear-all-filters:hover {
            color: #FF0049;
            text-decoration: underline;
        }
        
        /* Results Count */
        .results-count {
            font-size: 13px;
            color: #888;
            padding: 4px 0;
        }
        .results-count strong {
            color: #FF0049;
            font-weight: 600;
        }
        
        /* Search Hint */
        .search-hint {
            display: none;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            margin-top: 8px;
            background: #FFF8E1;
            border: 1px solid #FFE082;
            border-radius: 8px;
            font-size: 12px;
            color: #F57C00;
        }
        .search-hint.show {
            display: flex;
        }
        .search-hint i {
            font-size: 14px;
        }
        
        /* Highlight matched text */
        .worker-name .highlight {
            background: linear-gradient(135deg, #FFE0E8 0%, #FFD0DC 100%);
            color: #FF0049;
            padding: 0 2px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        /* Hidden worker card */
        .worker-card.hidden {
            display: none !important;
        }
        
        /* Search active state */
        .mobile-search-input.searching {
            border-color: #FF0049;
            background: #FFF8FA;
        }
        
        /* Mobile Pagination */
        .mobile-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 4px;
            flex-wrap: nowrap;
            padding: 10px 0;
        }
        .page-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            padding: 0 8px;
            border: 1px solid #e8e8e8;
            border-radius: 6px;
            background: #fff;
            color: #666;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .page-btn:hover {
            border-color: #FF0049;
            color: #FF0049;
            background: #FFF5F7;
            text-decoration: none;
        }
        .page-btn.active {
            background: linear-gradient(135deg, #FF0049 0%, #FF3366 100%);
            border-color: #FF0049;
            color: #fff;
            box-shadow: 0 2px 8px rgba(255, 0, 73, 0.3);
        }
        .page-btn.disabled {
            background: #f5f5f5;
            border-color: #e8e8e8;
            color: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }
        .page-dots {
            padding: 0 4px;
            color: #999;
            font-size: 13px;
        }
    </style>
    
    <script>
        function toggleFilters() {
            var panel = document.getElementById('filterPanel');
            panel.classList.toggle('show');
        }
        
        // Gender toggle functionality
        document.querySelectorAll('.gender-toggle input').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                this.closest('.gender-toggle').classList.toggle('active', this.checked);
            });
        });
        
        // Live Search functionality
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('liveSearchInput');
            var clearBtn = document.getElementById('clearSearch');
            var searchHint = document.getElementById('searchHint');
            var workerCards = document.querySelectorAll('.worker-card');
            var noResults = document.getElementById('noSearchResults');
            var resultsCount = document.querySelector('.results-count');
            var resultsCountNumber = document.getElementById('resultsCountNumber');
            var originalNames = {};
            var totalWorkers = {{ $workers->total() ?? $workers->count() }};
            
            // Store original names for restoring after search
            workerCards.forEach(function(card, index) {
                originalNames[index] = card.querySelector('.worker-name').innerHTML;
            });
            
            // Function to update results count
            function updateResultsCount(count) {
                if (resultsCountNumber) {
                    resultsCountNumber.textContent = count;
                }
            }
            
            searchInput.addEventListener('input', function() {
                var query = this.value.toLowerCase().trim();
                var visibleCount = 0;
                
                // Show/hide clear button
                clearBtn.style.display = query.length > 0 ? 'flex' : 'none';
                
                // Add searching class
                this.classList.toggle('searching', query.length > 0);
                
                // Show hint if 1 character
                if (query.length === 1) {
                    searchHint.classList.add('show');
                } else {
                    searchHint.classList.remove('show');
                }
                
                // If less than 2 characters, show all
                if (query.length < 2) {
                    workerCards.forEach(function(card, index) {
                        card.classList.remove('hidden');
                        card.querySelector('.worker-name').innerHTML = originalNames[index];
                    });
                    noResults.style.display = 'none';
                    if (resultsCount) resultsCount.style.display = '';
                    updateResultsCount(totalWorkers);
                    return;
                }
                
                // Filter workers
                workerCards.forEach(function(card, index) {
                    var name = card.dataset.name || '';
                    var nationality = card.dataset.nationality || '';
                    
                    if (name.includes(query) || nationality.includes(query)) {
                        card.classList.remove('hidden');
                        visibleCount++;
                        
                        // Highlight matching text in name
                        var nameEl = card.querySelector('.worker-name');
                        var originalText = originalNames[index];
                        var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
                        nameEl.innerHTML = originalText.replace(regex, '<span class="highlight">$1</span>');
                    } else {
                        card.classList.add('hidden');
                        card.querySelector('.worker-name').innerHTML = originalNames[index];
                    }
                });
                
                // Update results count with filtered number
                updateResultsCount(visibleCount);
                
                // Show/hide no results message
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
                if (resultsCount) resultsCount.style.display = visibleCount === 0 ? 'none' : '';
            });
            
            // Clear search
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.classList.remove('searching');
                clearBtn.style.display = 'none';
                searchHint.classList.remove('show');
                
                workerCards.forEach(function(card, index) {
                    card.classList.remove('hidden');
                    card.querySelector('.worker-name').innerHTML = originalNames[index];
                });
                
                noResults.style.display = 'none';
                if (resultsCount) resultsCount.style.display = '';
                updateResultsCount(totalWorkers);
                searchInput.focus();
            });
            
            // Helper function to escape regex special characters
            function escapeRegex(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
        });
        
        // Close filter panel when clicking outside
        document.addEventListener('click', function(e) {
            var panel = document.getElementById('filterPanel');
            var toggle = document.querySelector('.mobile-filter-toggle');
            if (panel.classList.contains('show') && 
                !panel.contains(e.target) && 
                !toggle.contains(e.target)) {
                panel.classList.remove('show');
            }
        });
    </script>
@endsection
