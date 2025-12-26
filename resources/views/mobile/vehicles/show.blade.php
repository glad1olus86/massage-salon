@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <a href="{{ route('mobile.vehicles.index') }}" class="mobile-header-btn">
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
        {{-- Vehicle Photo & Info --}}
        <div class="mobile-card mb-3">
            <div class="text-center mb-3">
                @if($vehicle->photo)
                    <img src="{{ asset('uploads/vehicle_photos/' . $vehicle->photo) }}" alt="" 
                         class="rounded" style="max-width: 100%; max-height: 150px; object-fit: cover;">
                @else
                    <div class="mobile-vehicle-placeholder-lg mx-auto">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2">
                            <circle cx="7" cy="17" r="2"></circle>
                            <circle cx="17" cy="17" r="2"></circle>
                            <path d="M5 17h-2v-6l2 -5h9l4 5h1a2 2 0 0 1 2 2v4h-2m-4 0h-6m-6 -6h15m-6 0v-5"></path>
                        </svg>
                    </div>
                @endif
            </div>
            <div class="text-center">
                <h5 class="mb-1">{{ $vehicle->brand }}</h5>
                <h4 class="text-primary mb-2">{{ $vehicle->license_plate }}</h4>
                <span class="mobile-badge mobile-badge-{{ $vehicle->inspection_status }}">
                    {{ $vehicle->inspection_status_label }}
                </span>
            </div>
        </div>

        {{-- Vehicle Details --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>{{ __('Information') }}</h6>
            
            <div class="mobile-info-list">
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('Color') }}</span>
                    <span class="mobile-info-value">{{ $vehicle->color ?? '-' }}</span>
                </div>
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('VIN Code') }}</span>
                    <span class="mobile-info-value" style="font-size: 11px;">{{ $vehicle->vin_code ?? '-' }}</span>
                </div>
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('Registration Date') }}</span>
                    <span class="mobile-info-value">{{ $vehicle->registration_date ? \Auth::user()->dateFormat($vehicle->registration_date) : '-' }}</span>
                </div>
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('Engine Volume') }}</span>
                    <span class="mobile-info-value">{{ $vehicle->engine_volume ? $vehicle->engine_volume . ' cm³' : '-' }}</span>
                </div>
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('Fuel Consumption') }}</span>
                    <span class="mobile-info-value">{{ $vehicle->fuel_consumption ? $vehicle->fuel_consumption . ' l/100km' : '-' }}</span>
                </div>
                <div class="mobile-info-item">
                    <span class="mobile-info-label">{{ __('Responsible') }}</span>
                    <span class="mobile-info-value">{{ $vehicle->assigned_name ?? '-' }}</span>
                </div>
            </div>
        </div>

        {{-- Tech Passport Photos --}}
        @if($vehicle->tech_passport_front || $vehicle->tech_passport_back)
            <div class="mobile-card mb-3">
                <h6 class="mb-3"><i class="ti ti-file-certificate me-2 text-primary"></i>{{ __('Tech Passport Photos') }}</h6>
                <div class="row g-2">
                    @if($vehicle->tech_passport_front)
                        <div class="col-6">
                            <p class="text-muted small mb-1">{{ __('Front Side') }}</p>
                            <a href="{{ asset('uploads/vehicle_documents/' . $vehicle->tech_passport_front) }}" target="_blank">
                                <img src="{{ asset('uploads/vehicle_documents/' . $vehicle->tech_passport_front) }}" 
                                     alt="" class="img-fluid rounded" style="max-height: 100px;">
                            </a>
                        </div>
                    @endif
                    @if($vehicle->tech_passport_back)
                        <div class="col-6">
                            <p class="text-muted small mb-1">{{ __('Back Side') }}</p>
                            <a href="{{ asset('uploads/vehicle_documents/' . $vehicle->tech_passport_back) }}" target="_blank">
                                <img src="{{ asset('uploads/vehicle_documents/' . $vehicle->tech_passport_back) }}" 
                                     alt="" class="img-fluid rounded" style="max-height: 100px;">
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Inspection History --}}
        <div class="mobile-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="ti ti-clipboard-check me-2 text-primary"></i>{{ __('Inspection History') }}</h6>
                @can('technical_inspection_manage')
                    <button type="button" class="btn btn-sm mobile-btn-primary" data-bs-toggle="modal" data-bs-target="#addInspectionModal">
                        <i class="ti ti-plus"></i>
                    </button>
                @endcan
            </div>
            
            @if($vehicle->inspections->count() > 0)
                @foreach($vehicle->inspections as $inspection)
                    <div class="mobile-inspection-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-medium">{{ $inspection->formatted_inspection_date }}</div>
                                <small class="text-muted">{{ __('Next') }}: {{ $inspection->formatted_next_inspection_date }}</small>
                            </div>
                            <div class="text-end">
                                @if($inspection->cost)
                                    <div class="fw-medium">{{ $inspection->formatted_cost }}</div>
                                @endif
                                @if($inspection->mileage)
                                    <small class="text-muted">{{ number_format($inspection->mileage, 0, '', ' ') }} km</small>
                                @endif
                            </div>
                        </div>
                        @if($inspection->service_station)
                            <small class="text-muted d-block mt-1">
                                <i class="ti ti-building me-1"></i>{{ $inspection->service_station }}
                            </small>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="text-center py-3 text-muted">
                    <i class="ti ti-clipboard-off" style="font-size: 32px; opacity: 0.5;"></i>
                    <p class="small mt-2 mb-0">{{ __('No inspection records') }}</p>
                </div>
            @endif
        </div>

        {{-- Action Buttons --}}
        <div class="mobile-actions mt-4">
            @can('vehicle_edit')
                <a href="{{ route('mobile.vehicles.edit', $vehicle->id) }}" 
                   class="btn mobile-btn-outline w-100 mb-2">
                    <i class="ti ti-pencil me-2"></i>{{ __('Edit Vehicle') }}
                </a>
            @endcan
            @can('vehicle_delete')
                <form action="{{ route('vehicles.destroy', $vehicle->id) }}" method="POST"
                      onsubmit="return confirm('{{ __('Are you sure?') . ' ' . __('This action cannot be undone.') }}')">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="redirect_to" value="mobile">
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="ti ti-trash me-2"></i>{{ __('Delete Vehicle') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    {{-- Add Inspection Modal --}}
    @can('technical_inspection_manage')
        <div class="modal fade" id="addInspectionModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('inspections.store', $vehicle->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="redirect_to" value="mobile_vehicle_{{ $vehicle->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Add Inspection') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Inspection Date') }}</label>
                                <input type="date" name="inspection_date" class="form-control" required value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Next Inspection Date') }}</label>
                                <input type="date" name="next_inspection_date" class="form-control" required 
                                       value="{{ date('Y-m-d', strtotime('+1 year')) }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Mileage') }} (km)</label>
                                <input type="number" name="mileage" class="form-control" placeholder="{{ __('Enter mileage') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Cost') }}</label>
                                <input type="number" step="0.01" name="cost" class="form-control" placeholder="{{ __('Enter cost') }}">
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Service Station') }}</label>
                                <input type="text" name="service_station" class="form-control" placeholder="{{ __('Enter service station name') }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn mobile-btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <style>
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
        .mobile-vehicle-placeholder-lg {
            width: 120px;
            height: 80px;
            border-radius: 12px;
            background: linear-gradient(135deg, #FF0049, #FF6B6B);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mobile-badge-overdue {
            background: rgba(220, 53, 69, 0.15);
            color: #dc3545;
        }
        .mobile-badge-soon {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
        }
        .mobile-badge-ok {
            background: rgba(34, 180, 4, 0.15);
            color: #22B404;
        }
        .mobile-badge-none {
            background: rgba(108, 117, 125, 0.15);
            color: #6c757d;
        }
        .mobile-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .mobile-info-list {
            display: flex;
            flex-direction: column;
        }
        .mobile-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .mobile-info-item:last-child {
            border-bottom: none;
        }
        .mobile-info-label {
            color: #666;
            font-size: 13px;
        }
        .mobile-info-value {
            font-weight: 500;
            font-size: 13px;
        }
        .mobile-inspection-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .mobile-inspection-item:last-child {
            border-bottom: none;
        }
    </style>
@endsection
