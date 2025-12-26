@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <a href="{{ route('mobile.hotels.rooms', $room->hotel->id) }}" class="mobile-header-btn">
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
        @php
            $occupied = $room->currentAssignments->count();
            $free = $room->capacity - $occupied;
            $isFull = $free <= 0;
        @endphp

        {{-- Room Info Card --}}
        <div class="mobile-card mb-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div>
                    <h5 class="mb-1">{{ __('Room') }} {{ $room->room_number }}</h5>
                    <small class="text-muted">{{ $room->hotel->name }}</small>
                </div>
                @can('edit room')
                    <a href="#" data-url="{{ route('room.edit', $room->id) }}?redirect_to=mobile" 
                       data-ajax-popup="true" data-title="{{ __('Edit Room') }}" data-size="md"
                       class="btn btn-sm btn-outline-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 20h4l10.5 -10.5a1.5 1.5 0 0 0 -4 -4l-10.5 10.5v4"></path>
                            <line x1="13.5" y1="6.5" x2="17.5" y2="10.5"></line>
                        </svg>
                    </a>
                @endcan
            </div>

            <div class="mobile-info-section">
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Hotel') }}</span>
                    <span class="mobile-info-value">{{ $room->hotel->name }}</span>
                </div>
                @if($room->floor)
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Floor') }}</span>
                    <span class="mobile-info-value">{{ $room->floor }}</span>
                </div>
                @endif
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Capacity') }}</span>
                    <span class="mobile-info-value">{{ $room->capacity }}</span>
                </div>
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Occupied') }}</span>
                    <span class="mobile-info-value">{{ $occupied }}</span>
                </div>
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Free spots') }}</span>
                    <span class="mobile-info-value {{ $free < 1 ? 'text-danger' : 'text-success' }}">{{ $free }}</span>
                </div>
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Status') }}</span>
                    <span class="mobile-info-value">
                        @if($isFull)
                            <span class="mobile-badge mobile-badge-danger">{{ __('Full') }}</span>
                        @else
                            <span class="mobile-badge mobile-badge-success">{{ __('Available') }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        {{-- Current Occupants --}}
        <div class="mobile-section-title">
            <div class="mobile-section-title-left">
                <img src="{{ asset('fromfigma/workers.svg') }}" alt="" width="20" height="20"
                     onerror="this.outerHTML='<svg width=20 height=20 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0\'></path><path d=\'M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2\'></path></svg>'">
                <span>{{ __('Current Occupants') }}</span>
            </div>
        </div>
        
        <div class="mobile-card mb-3">

            @forelse($room->currentAssignments as $assignment)
                <div class="mobile-list-item" onclick="window.location='{{ route('mobile.workers.show', $assignment->worker->id) }}'">
                    <div class="mobile-avatar me-3">
                        @if(!empty($assignment->worker->photo))
                            <img src="{{ asset('uploads/worker_photos/' . $assignment->worker->photo) }}" alt="">
                        @else
                            <div class="mobile-avatar-placeholder">
                                {{ strtoupper(substr($assignment->worker->first_name, 0, 1)) }}{{ strtoupper(substr($assignment->worker->last_name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{ $assignment->worker->first_name }} {{ $assignment->worker->last_name }}</h6>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-muted">
                                {{ __('Since') }}: {{ \Auth::user()->dateFormat($assignment->check_in_date) }}
                            </small>
                            @if($assignment->payment_type === 'worker')
                                <span class="badge" style="background-color: #FF0049; font-size: 10px;">{{ formatCashboxCurrency($assignment->payment_amount ?? 0) }}</span>
                            @else
                                <span class="badge bg-success" style="font-size: 10px;">{{ __('Agency') }}</span>
                            @endif
                        </div>
                    </div>
                    @can('manage worker')
                        <form action="{{ route('worker.unassign.room', $assignment->worker->id) }}" method="POST" 
                              onclick="event.stopPropagation();" style="display: inline;">
                            @csrf
                            <input type="hidden" name="redirect_to" value="mobile_room_{{ $room->id }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('{{ __('Are you sure?') }}')">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2"></path>
                                    <path d="M7 12h14l-3 -3m0 6l3 -3"></path>
                                </svg>
                            </button>
                        </form>
                    @endcan
                </div>
            @empty
                <div class="text-center py-3">
                    <small class="text-muted">{{ __('No occupants in this room') }}</small>
                </div>
            @endforelse
        </div>

        {{-- Actions --}}
        @if(!$isFull)
            @can('manage worker')
                <div class="mobile-card">
                    <a href="#" data-url="{{ route('room.assign.form', $room->id) }}?redirect_to=mobile" 
                       data-ajax-popup="true" data-title="{{ __('Assign Worker to Room') }}" data-size="md"
                       class="btn mobile-btn-primary w-100">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                            <path d="M16 21v-2a4 4 0 0 0 -4 -4h-4a4 4 0 0 0 -4 4v2"></path>
                            <circle cx="9" cy="7" r="4"></circle>
                            <line x1="19" y1="8" x2="19" y2="14"></line>
                            <line x1="22" y1="11" x2="16" y2="11"></line>
                        </svg>
                        {{ __('Assign Worker') }}
                    </a>
                </div>
            @endcan
        @endif

        {{-- Delete Room --}}
        @can('delete room')
            <div class="mt-4 mb-3">
                <form action="{{ route('room.destroy', $room->id) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="redirect_to" value="mobile">
                    <input type="hidden" name="hotel_id" value="{{ $room->hotel->id }}">
                    <button type="submit" class="btn mobile-btn-danger w-100"
                            onclick="return confirm('{{ __('Are you sure you want to delete this room?') }}')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        {{ __('Delete Room') }}
                    </button>
                </form>
            </div>
        @endcan
    </div>

    <style>
        .mobile-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }
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
        .mobile-info-section {
            margin-top: 10px;
        }
        .mobile-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .mobile-info-row:last-child {
            border-bottom: none;
        }
        .mobile-info-label {
            color: #666;
            font-size: 14px;
        }
        .mobile-info-value {
            font-weight: 500;
            font-size: 14px;
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
        .mobile-badge-danger {
            background: #FF0049;
            color: #fff;
        }
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
        .mobile-avatar {
            width: 48px;
            height: 48px;
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
            font-size: 14px;
        }
        .mobile-btn-primary {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
            color: #fff !important;
        }
        .mobile-btn-primary:hover {
            background: #e00040 !important;
            border-color: #e00040 !important;
        }
        .mobile-btn-danger {
            background: transparent;
            border: 1px solid #dc3545;
            color: #dc3545;
        }
        .mobile-btn-danger:hover {
            background: #dc3545;
            color: #fff;
        }
    </style>
@endsection
