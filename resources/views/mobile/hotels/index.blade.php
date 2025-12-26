@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <button type="button" class="mobile-header-btn" onclick="openSidebar()">
                    <img src="{{ asset('fromfigma/menu_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><line x1=3 y1=6 x2=21 y2=6></line><line x1=3 y1=12 x2=21 y2=12></line><line x1=3 y1=18 x2=21 y2=18></line></svg>'">
                </button>
                <a href="{{ route('mobile.notifications.index') }}" class="mobile-header-btn">
                    <img src="{{ asset('fromfigma/bell_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\'></path><path d=\'M13.73 21a2 2 0 0 1-3.46 0\'></path></svg>'">
                </a>
            </div>
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
                <img src="{{ asset('fromfigma/hotel.svg') }}" alt="" width="22" height="22"
                     onerror="this.outerHTML='<svg width=22 height=22 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M3 21h18\'></path><path d=\'M5 21v-14l8 -4v18\'></path><path d=\'M19 21v-10l-6 -4\'></path></svg>'">
                <span>{{ __('Hotels') }}</span>
            </div>
            @can('create hotel')
                <a href="#" data-url="{{ route('hotel.create', ['redirect_to' => 'mobile']) }}" data-ajax-popup="true" 
                   data-title="{{ __('Create New Hotel') }}" data-size="md" class="mobile-add-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </a>
            @endcan
        </div>

        {{-- Search --}}
        <div class="mobile-search-wrapper mb-3">
            <div class="mobile-search-box">
                <i class="ti ti-search mobile-search-icon"></i>
                <input type="text" id="liveSearchInput" class="mobile-search-input" 
                       placeholder="{{ __('Search hotels') }}..." 
                       autocomplete="off">
                <span class="mobile-search-clear" id="clearSearch" style="display: none;">
                    <i class="ti ti-x"></i>
                </span>
            </div>
        </div>
        
        {{-- Search Hint --}}
        <div class="search-hint" id="searchHint">
            <i class="ti ti-info-circle"></i>
            <span>{{ __('Type at least 2 characters to search') }}</span>
        </div>

        {{-- Hotels List --}}
        <div id="hotelsList">
            @forelse($hotels as $hotel)
                @php
                    $totalCapacity = $hotel->rooms->sum('capacity');
                    $totalOccupied = $hotel->rooms->sum(function($room) {
                        return $room->currentAssignments->count();
                    });
                    $freeSpots = $totalCapacity - $totalOccupied;
                    $percentage = $totalCapacity > 0 ? ($totalOccupied / $totalCapacity) * 100 : 0;
                @endphp
                <div class="mobile-card hotel-card mb-3" 
                     data-name="{{ strtolower($hotel->name) }}"
                     data-address="{{ strtolower($hotel->address ?? '') }}"
                     onclick="window.location='{{ route('mobile.hotels.rooms', $hotel->id) }}'">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1 hotel-name">{{ $hotel->name }}</h6>
                            <small class="text-muted">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                {{ $hotel->address }}
                            </small>
                        </div>
                        <div class="text-end">
                            @if($freeSpots < 5)
                                <span class="mobile-badge mobile-badge-danger">{{ $freeSpots }} {{ __('free') }}</span>
                            @elseif($freeSpots < 10)
                                <span class="mobile-badge mobile-badge-warning">{{ $freeSpots }} {{ __('free') }}</span>
                            @else
                                <span class="mobile-badge mobile-badge-success">{{ $freeSpots }} {{ __('free') }}</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mobile-card-stats">
                        <div class="mobile-stat-item">
                            <span class="mobile-stat-label">{{ __('Rooms') }}</span>
                            <span class="mobile-stat-value">{{ $hotel->rooms->count() }}</span>
                        </div>
                        <div class="mobile-stat-item">
                            <span class="mobile-stat-label">{{ __('Capacity') }}</span>
                            <span class="mobile-stat-value">{{ $totalCapacity }}</span>
                        </div>
                        <div class="mobile-stat-item">
                            <span class="mobile-stat-label">{{ __('Occupied') }}</span>
                            <span class="mobile-stat-value">{{ $totalOccupied }}</span>
                        </div>
                    </div>

                    <div class="progress mt-2" style="height: 6px;">
                        <div class="progress-bar {{ $percentage >= 90 ? 'bg-success' : ($percentage >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                             style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @empty
                <div class="mobile-empty-state">
                    <img src="{{ asset('fromfigma/hotel.svg') }}" alt="" width="48" height="48" style="opacity: 0.3;"
                         onerror="this.outerHTML='<svg width=48 height=48 viewBox=\'0 0 24 24\' fill=none stroke=#ccc stroke-width=1.5><path d=\'M3 21h18\'></path><path d=\'M5 21v-14l8 -4v18\'></path><path d=\'M19 21v-10l-6 -4\'></path></svg>'">
                    <p class="mt-2 text-muted">{{ __('No hotels found') }}</p>
                    @can('create hotel')
                        <a href="#" data-url="{{ route('hotel.create', ['redirect_to' => 'mobile']) }}" data-ajax-popup="true" 
                           data-title="{{ __('Create New Hotel') }}" class="btn btn-sm mobile-btn-primary">
                            {{ __('Add Hotel') }}
                        </a>
                    @endcan
                </div>
            @endforelse
        </div>
        
        {{-- No Results Message --}}
        <div id="noSearchResults" class="text-center py-5" style="display: none;">
            <i class="ti ti-search-off" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-3 text-muted">{{ __('No hotels match your search') }}</p>
        </div>
    </div>

    <style>
        .mobile-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #f0f0f0;
            overflow: visible;
        }
        .mobile-card:active {
            transform: scale(0.98);
            box-shadow: 0 1px 6px rgba(0,0,0,0.08);
        }
        .mobile-card-stats {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            width: 100%;
            overflow: visible;
        }
        .mobile-stat-item {
            text-align: center;
            flex: 1;
            min-width: 0;
        }
        .mobile-stat-label {
            display: block;
            font-size: 10px;
            color: #666;
            margin-bottom: 2px;
            white-space: nowrap;
        }
        .mobile-stat-value {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: #000;
        }
        .mobile-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .mobile-badge-success {
            background: #22B404;
            color: #fff;
        }
        .mobile-badge-warning {
            background: #F59E0B;
            color: #fff;
        }
        .mobile-badge-danger {
            background: #FF0049;
            color: #fff;
        }
        .mobile-btn-primary {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
            color: #fff !important;
        }
        .mobile-empty-state {
            text-align: center;
            padding: 40px 20px;
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
        .mobile-search-input.searching {
            border-color: #FF0049;
            background: #FFF8FA;
        }
        .mobile-search-clear {
            position: absolute;
            right: 12px;
            color: #999;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .mobile-search-clear:hover {
            color: #FF0049;
        }
        
        /* Search Hint */
        .search-hint {
            display: none;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
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
        .hotel-name .highlight {
            background: linear-gradient(135deg, #FFE0E8 0%, #FFD0DC 100%);
            color: #FF0049;
            padding: 0 2px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        /* Hidden card */
        .hotel-card.hidden {
            display: none !important;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('liveSearchInput');
            var clearBtn = document.getElementById('clearSearch');
            var searchHint = document.getElementById('searchHint');
            var hotelCards = document.querySelectorAll('.hotel-card');
            var noResults = document.getElementById('noSearchResults');
            var originalNames = {};
            
            // Store original names
            hotelCards.forEach(function(card, index) {
                originalNames[index] = card.querySelector('.hotel-name').innerHTML;
            });
            
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
                    hotelCards.forEach(function(card, index) {
                        card.classList.remove('hidden');
                        card.querySelector('.hotel-name').innerHTML = originalNames[index];
                    });
                    noResults.style.display = 'none';
                    return;
                }
                
                // Filter hotels
                hotelCards.forEach(function(card, index) {
                    var name = card.dataset.name || '';
                    var address = card.dataset.address || '';
                    
                    if (name.includes(query) || address.includes(query)) {
                        card.classList.remove('hidden');
                        visibleCount++;
                        
                        // Highlight matching text
                        var nameEl = card.querySelector('.hotel-name');
                        var originalText = originalNames[index];
                        var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
                        nameEl.innerHTML = originalText.replace(regex, '<span class="highlight">$1</span>');
                    } else {
                        card.classList.add('hidden');
                        card.querySelector('.hotel-name').innerHTML = originalNames[index];
                    }
                });
                
                // Show/hide no results
                noResults.style.display = visibleCount === 0 ? 'block' : 'none';
            });
            
            // Clear search
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.classList.remove('searching');
                clearBtn.style.display = 'none';
                searchHint.classList.remove('show');
                
                hotelCards.forEach(function(card, index) {
                    card.classList.remove('hidden');
                    card.querySelector('.hotel-name').innerHTML = originalNames[index];
                });
                
                noResults.style.display = 'none';
                searchInput.focus();
            });
            
            function escapeRegex(string) {
                return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            }
        });
    </script>
@endsection
