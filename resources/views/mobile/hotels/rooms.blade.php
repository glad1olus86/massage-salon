@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <a href="{{ route('mobile.hotels.index') }}" class="mobile-header-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                </a>
            </div>
            <div class="mobile-header-right">
                <div class="mobile-logo">
                    <img src="{{ asset('fromfigma/jobsi_mobile.png') }}" alt="JOBSI">
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-content">
        {{-- Hotel Info --}}
        <div class="mobile-card mb-3">
            <h5 class="mb-1">{{ $hotel->name }}</h5>
            <small class="text-muted">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                {{ $hotel->address }}
            </small>
            
            @php
                $totalCapacity = $hotel->rooms->sum('capacity');
                $totalOccupied = $hotel->rooms->sum(function($room) {
                    return $room->currentAssignments->count();
                });
                $freeSpots = $totalCapacity - $totalOccupied;
            @endphp
            
            <div class="mobile-card-stats mt-3">
                <div class="mobile-stat-item">
                    <span class="mobile-stat-label">{{ __('Rooms') }}</span>
                    <span class="mobile-stat-value">{{ $hotel->rooms->count() }}</span>
                </div>
                <div class="mobile-stat-item">
                    <span class="mobile-stat-label">{{ __('Capacity') }}</span>
                    <span class="mobile-stat-value">{{ $totalCapacity }}</span>
                </div>
                <div class="mobile-stat-item">
                    <span class="mobile-stat-label">{{ __('Free') }}</span>
                    <span class="mobile-stat-value {{ $freeSpots < 5 ? 'text-danger' : '' }}">{{ $freeSpots }}</span>
                </div>
            </div>
        </div>

        {{-- Page Title --}}
        <div class="mobile-section-title">
            <div class="mobile-section-title-left">
                <img src="{{ asset('fromfigma/hotel.svg') }}" alt="" width="20" height="20"
                     onerror="this.outerHTML='<svg width=20 height=20 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M3 7v11m0 -4h18m0 4v-8a2 2 0 0 0 -2 -2h-8v6\'></path><circle cx=7 cy=10 r=1></circle></svg>'">
                <span>{{ __('Rooms') }}</span>
            </div>
            @can('create room')
                <a href="#" data-url="{{ route('room.create', ['hotel_id' => $hotel->id, 'redirect_to' => 'mobile']) }}" 
                   data-ajax-popup="true" data-title="{{ __('Add New Room') }}" data-size="md" 
                   class="mobile-add-btn">
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
                       placeholder="{{ __('Search rooms') }}..." 
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

        {{-- Rooms List --}}
        <div id="roomsList">
            @forelse($hotel->rooms as $room)
                @php
                    $occupied = $room->currentAssignments->count();
                    $free = $room->capacity - $occupied;
                    $isFull = $free <= 0;
                    $occupantNames = $room->currentAssignments->pluck('worker.first_name')->implode(' ');
                @endphp
                <div class="mobile-card room-card mb-3" 
                     data-room="{{ strtolower($room->room_number) }}"
                     data-floor="{{ strtolower($room->floor ?? '') }}"
                     data-occupants="{{ strtolower($occupantNames) }}"
                     onclick="window.location='{{ route('mobile.rooms.show', $room->id) }}'">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-1 room-name">
                                <img src="{{ asset('fromfigma/hotel.svg') }}" alt="" width="16" height="16" class="me-1" style="vertical-align: -2px;"
                                     onerror="this.outerHTML='<svg width=16 height=16 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2 class=\'me-1\'><path d=\'M3 7v11m0 -4h18m0 4v-8a2 2 0 0 0 -2 -2h-8v6\'></path><circle cx=7 cy=10 r=1></circle></svg>'">
                                {{ __('Room') }} <span class="room-number">{{ $room->room_number }}</span>
                            </h6>
                            @if($room->floor)
                                <small class="text-muted">{{ __('Floor') }}: {{ $room->floor }}</small>
                            @endif
                        </div>
                        <div>
                            @if($isFull)
                                <span class="mobile-badge mobile-badge-danger">{{ __('Full') }}</span>
                            @elseif($free == 1)
                                <span class="mobile-badge mobile-badge-warning">{{ $free }} {{ __('free') }}</span>
                            @else
                                <span class="mobile-badge mobile-badge-success">{{ $free }} {{ __('free') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="mobile-card-meta mb-2">
                        <span>{{ __('Capacity') }}: {{ $room->capacity }}</span>
                        <span class="ms-3">{{ __('Occupied') }}: {{ $occupied }}</span>
                    </div>

                    {{-- Current Occupants --}}
                    @if($room->currentAssignments->count() > 0)
                        <div class="mobile-occupants">
                            @foreach($room->currentAssignments->take(3) as $assignment)
                                <div class="mobile-occupant-item">
                                    <div class="mobile-avatar-small">
                                        @if(!empty($assignment->worker->photo))
                                            <img src="{{ asset('uploads/worker_photos/' . $assignment->worker->photo) }}" alt="">
                                        @else
                                            {{ strtoupper(substr($assignment->worker->first_name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <span class="mobile-occupant-name">{{ $assignment->worker->first_name }}</span>
                                    @if($assignment->payment_type === 'worker')
                                        <span class="badge ms-1" style="background-color: #FF0049; font-size: 9px; padding: 2px 4px;">{{ formatCashboxCurrency($assignment->payment_amount ?? 0) }}</span>
                                    @endif
                                </div>
                            @endforeach
                            @if($room->currentAssignments->count() > 3)
                                <span class="mobile-occupant-more">+{{ $room->currentAssignments->count() - 3 }}</span>
                            @endif
                        </div>
                    @else
                        <small class="text-muted">{{ __('No occupants') }}</small>
                    @endif
                </div>
            @empty
                <div class="mobile-empty-state">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                        <path d="M3 7v11m0 -4h18m0 4v-8a2 2 0 0 0 -2 -2h-8v6"></path>
                        <circle cx="7" cy="10" r="1"></circle>
                    </svg>
                    <p class="mt-2 text-muted">{{ __('No rooms found') }}</p>
                    @can('create room')
                        <a href="#" data-url="{{ route('room.create', ['hotel_id' => $hotel->id, 'redirect_to' => 'mobile']) }}" 
                           data-ajax-popup="true" data-title="{{ __('Add New Room') }}" 
                           class="btn btn-sm mobile-btn-primary">
                            {{ __('Add Room') }}
                        </a>
                    @endcan
                </div>
            @endforelse
        </div>
        
        {{-- No Results Message --}}
        <div id="noSearchResults" class="text-center py-5" style="display: none;">
            <i class="ti ti-search-off" style="font-size: 48px; opacity: 0.3;"></i>
            <p class="mt-3 text-muted">{{ __('No rooms match your search') }}</p>
        </div>
    </div>

    <style>
        /* Prevent horizontal scroll */
        html, body {
            overflow-x: hidden;
            max-width: 100vw;
        }
        .mobile-content {
            overflow-x: hidden;
            padding: 0 15px;
            box-sizing: border-box;
        }
        
        .mobile-card {
            background: #fff;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #f0f0f0;
            overflow: hidden;
            box-sizing: border-box;
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
            box-sizing: border-box;
        }
        .mobile-stat-item {
            text-align: center;
            flex: 1;
            min-width: 0;
        }
        .mobile-stat-label {
            display: block;
            font-size: 11px;
            color: #666;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .mobile-stat-value {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: #000;
        }
        .mobile-card-meta {
            font-size: 12px;
            color: #666;
            overflow: hidden;
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
        .mobile-section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            margin-bottom: 12px;
            border-bottom: 2px solid #FFE0E6;
            box-sizing: border-box;
            width: 100%;
        }
        .mobile-section-title-left {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
            flex: 1;
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
            min-width: 40px;
            background: #FFE0E6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.2s ease;
            flex-shrink: 0;
        }
        .mobile-add-btn:hover {
            background: #FF0049;
        }
        .mobile-add-btn:hover svg {
            stroke: #fff;
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
            width: 100%;
            box-sizing: border-box;
        }
        .mobile-search-box {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            min-width: 0;
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
            box-sizing: border-box;
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
        .room-number .highlight {
            background: linear-gradient(135deg, #FFE0E8 0%, #FFD0DC 100%);
            color: #FF0049;
            padding: 0 2px;
            border-radius: 3px;
            font-weight: 600;
        }
        
        /* Hidden card */
        .room-card.hidden {
            display: none !important;
        }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var searchInput = document.getElementById('liveSearchInput');
            var clearBtn = document.getElementById('clearSearch');
            var searchHint = document.getElementById('searchHint');
            var roomCards = document.querySelectorAll('.room-card');
            var noResults = document.getElementById('noSearchResults');
            var originalNumbers = {};
            
            // Store original room numbers
            roomCards.forEach(function(card, index) {
                var numberEl = card.querySelector('.room-number');
                if (numberEl) {
                    originalNumbers[index] = numberEl.innerHTML;
                }
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
                    roomCards.forEach(function(card, index) {
                        card.classList.remove('hidden');
                        var numberEl = card.querySelector('.room-number');
                        if (numberEl && originalNumbers[index]) {
                            numberEl.innerHTML = originalNumbers[index];
                        }
                    });
                    noResults.style.display = 'none';
                    return;
                }
                
                // Filter rooms
                roomCards.forEach(function(card, index) {
                    var room = card.dataset.room || '';
                    var floor = card.dataset.floor || '';
                    var occupants = card.dataset.occupants || '';
                    
                    if (room.includes(query) || floor.includes(query) || occupants.includes(query)) {
                        card.classList.remove('hidden');
                        visibleCount++;
                        
                        // Highlight matching room number
                        var numberEl = card.querySelector('.room-number');
                        if (numberEl && originalNumbers[index]) {
                            var originalText = originalNumbers[index];
                            var regex = new RegExp('(' + escapeRegex(query) + ')', 'gi');
                            numberEl.innerHTML = originalText.replace(regex, '<span class="highlight">$1</span>');
                        }
                    } else {
                        card.classList.add('hidden');
                        var numberEl = card.querySelector('.room-number');
                        if (numberEl && originalNumbers[index]) {
                            numberEl.innerHTML = originalNumbers[index];
                        }
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
                
                roomCards.forEach(function(card, index) {
                    card.classList.remove('hidden');
                    var numberEl = card.querySelector('.room-number');
                    if (numberEl && originalNumbers[index]) {
                        numberEl.innerHTML = originalNumbers[index];
                    }
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
