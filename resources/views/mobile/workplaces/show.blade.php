@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <a href="{{ route('mobile.workplaces.index') }}" class="mobile-header-btn">
                    <i class="ti ti-arrow-left" style="font-size: 24px; color: #FF0049;"></i>
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
        {{-- Workplace Info Card --}}
        <div class="mobile-card mb-3">
            <div class="d-flex align-items-start">
                <div class="mobile-workplace-icon me-3">
                    <img src="{{ asset('fromfigma/workplaces.svg') }}" alt="" width="28" height="28" style="filter: brightness(0) invert(1);"
                         onerror="this.outerHTML='<i class=\'ti ti-briefcase\'></i>'">
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-1">{{ $workplace->name }}</h5>
                    @if($workplace->address)
                        <div class="text-muted mb-2">
                            <i class="ti ti-map-pin me-1"></i>{{ $workplace->address }}
                        </div>
                    @endif
                    <div class="d-flex flex-wrap gap-2">
                        @if($workplace->phone)
                            <a href="tel:{{ $workplace->phone }}" class="mobile-contact-link">
                                <i class="ti ti-phone me-1"></i>{{ $workplace->phone }}
                            </a>
                        @endif
                        @if($workplace->email)
                            <a href="mailto:{{ $workplace->email }}" class="mobile-contact-link">
                                <i class="ti ti-mail me-1"></i>{{ $workplace->email }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Stats --}}
            <div class="mobile-stats-row mt-3">
                <div class="mobile-stat-item">
                    <div class="mobile-stat-value">{{ $workplace->currentAssignments->count() }}</div>
                    <div class="mobile-stat-label">{{ __('Employees') }}</div>
                </div>
                <div class="mobile-stat-item">
                    <div class="mobile-stat-value">{{ $workplace->positions->count() }}</div>
                    <div class="mobile-stat-label">{{ __('Positions') }}</div>
                </div>
            </div>
        </div>

        {{-- Positions Section --}}
        <div class="mobile-section-title">
            <div class="mobile-section-title-left">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                <span>{{ __('Positions') }}</span>
            </div>
            @can('manage work place')
                <button type="button" class="mobile-add-btn" data-bs-toggle="modal" data-bs-target="#createPositionModal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </button>
            @endcan
        </div>

        @forelse($workplace->positions as $position)
            @php
                $positionWorkers = $position->workAssignments()->whereNull('ended_at')->with('worker')->get();
            @endphp
            <div class="mobile-card mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2" class="me-2" style="vertical-align: -2px;">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                        </svg>{{ $position->name }}
                    </h6>
                    <span class="mobile-badge {{ $positionWorkers->count() > 0 ? 'mobile-badge-success' : 'mobile-badge-secondary' }}">
                        {{ $positionWorkers->count() }} {{ __('employees') }}
                    </span>
                </div>
                
                @if($positionWorkers->count() > 0)
                    <div class="mobile-workers-list">
                        @foreach($positionWorkers as $assignment)
                            <div class="mobile-worker-item" onclick="window.location='{{ route('mobile.workers.show', $assignment->worker->id) }}'">
                                <div class="mobile-avatar-sm me-2">
                                    @if(!empty($assignment->worker->photo))
                                        <img src="{{ asset('uploads/worker_photos/' . $assignment->worker->photo) }}" alt="">
                                    @else
                                        <div class="mobile-avatar-placeholder-sm">
                                            {{ strtoupper(substr($assignment->worker->first_name, 0, 1)) }}{{ strtoupper(substr($assignment->worker->last_name, 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-medium">{{ $assignment->worker->first_name }} {{ $assignment->worker->last_name }}</span>
                                </div>
                                <i class="ti ti-chevron-right text-muted"></i>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-2 text-muted small">
                        {{ __('No employees assigned') }}
                    </div>
                @endif
                
                @can('manage work place')
                    <div class="d-flex gap-2 mt-2 pt-2 border-top">
                        <a href="#" 
                           data-url="{{ route('positions.workers', $position->id) }}"
                           data-ajax-popup="true"
                           data-title="{{ __('Employees') }}: {{ $position->name }}"
                           data-size="lg"
                           class="btn btn-sm btn-outline-success flex-grow-1">
                            <i class="ti ti-user-plus me-1"></i>{{ __('Employ') }}
                        </a>
                        <form action="{{ route('positions.destroy', $position->id) }}" 
                              method="POST" class="d-inline"
                              onsubmit="return confirm('{{ __('Delete position?') }}')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="redirect_to" value="mobile_workplace_{{ $workplace->id }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                @endcan
            </div>
        @empty
            <div class="text-center py-4">
                <i class="ti ti-briefcase-off" style="font-size: 40px; opacity: 0.3;"></i>
                <p class="mt-2 text-muted small">{{ __('No positions created yet') }}</p>
            </div>
        @endforelse

        {{-- Action Buttons --}}
        <div class="mobile-actions mt-4">
            @can('edit work place')
                <a href="#" data-url="{{ route('work-place.edit', $workplace->id) }}?redirect_to=mobile" data-ajax-popup="true" 
                   data-title="{{ __('Edit Work Place') }}" class="btn mobile-btn-outline w-100 mb-2">
                    <i class="ti ti-pencil me-2"></i>{{ __('Edit Work Place') }}
                </a>
            @endcan
            @can('delete work place')
                <form action="{{ route('work-place.destroy', $workplace->id) }}" method="POST"
                      onsubmit="return confirm('{{ __('Are you sure?') . ' ' . __('This action cannot be undone.') }}')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="redirect_to" value="mobile">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="ti ti-trash me-2"></i>{{ __('Delete Work Place') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <!-- Create Position Modal -->
    <div class="modal fade" id="createPositionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('positions.store', $workplace->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="redirect_to" value="mobile_workplace_{{ $workplace->id }}">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Create Position') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">{{ __('Position Name') }}</label>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="{{ __('For example: Manager') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn mobile-btn-primary">{{ __('Create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
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
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
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
        .mobile-btn-outline {
            border: 1px solid #FF0049;
            color: #FF0049;
            background: transparent;
        }
        .mobile-btn-outline:hover {
            background: #FF0049;
            color: #fff;
        }
        .mobile-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        .mobile-badge-success {
            background: rgba(34, 180, 4, 0.15);
            color: #22B404;
        }
        .mobile-badge-secondary {
            background: rgba(108, 117, 125, 0.15);
            color: #6c757d;
        }
        .mobile-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .mobile-workplace-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: linear-gradient(135deg, #FF0049, #FF6B6B);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .mobile-contact-link {
            font-size: 12px;
            color: #666;
            text-decoration: none;
        }
        .mobile-contact-link:hover {
            color: #FF0049;
        }
        .mobile-stats-row {
            display: flex;
            border-top: 1px solid #eee;
            padding-top: 12px;
        }
        .mobile-stat-item {
            flex: 1;
            text-align: center;
        }
        .mobile-stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #FF0049;
        }
        .mobile-stat-label {
            font-size: 11px;
            color: #999;
            text-transform: uppercase;
        }
        .mobile-workers-list {
            margin-top: 10px;
        }
        .mobile-worker-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
        }
        .mobile-worker-item:last-child {
            border-bottom: none;
        }
        .mobile-avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
        }
        .mobile-avatar-sm img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .mobile-avatar-placeholder-sm {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #FF0049, #FF6B6B);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 11px;
        }
    </style>
@endsection
