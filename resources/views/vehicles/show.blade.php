@extends('layouts.admin')

@section('page-title')
    {{ $vehicle->brand }} - {{ $vehicle->license_plate }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">{{ __('Vehicles') }}</a></li>
    <li class="breadcrumb-item">{{ $vehicle->license_plate }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('vehicle_edit')
            <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-sm btn-info" data-bs-toggle="tooltip"
                title="{{ __('Edit') }}">
                <i class="ti ti-pencil"></i>
            </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    @if ($vehicle->photo)
                        <img src="{{ asset('uploads/vehicle_photos/' . $vehicle->photo) }}" alt="" class="rounded mb-3"
                            style="max-width: 100%; max-height: 200px; object-fit: cover;">
                    @else
                        <div class="bg-light rounded d-flex align-items-center justify-content-center mb-3 mx-auto"
                            style="width: 150px; height: 100px;">
                            <i class="ti ti-car text-muted" style="font-size: 48px;"></i>
                        </div>
                    @endif
                    <h4>{{ $vehicle->brand }}</h4>
                    <h5 class="text-primary">{{ $vehicle->license_plate }}</h5>
                    <span class="badge {{ $vehicle->inspection_status_badge }}">
                        {{ $vehicle->inspection_status_label }}
                    </span>
                </div>
            </div>

            <div class="card">
                <div class="card-header"">
                    <h5>{{ __('Information') }}</h5>
                </div>
                <div class="card-body" style="min-height: 602px;">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('Color') }}</span>
                            <span>{{ $vehicle->color ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('VIN Code') }}</span>
                            <span style="font-size: 0.85em;">{{ $vehicle->vin_code ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('Registration Date') }}</span>
                            <span>{{ $vehicle->registration_date ? $vehicle->registration_date->format('d.m.Y') : '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('Engine Volume') }}</span>
                            <span>{{ $vehicle->engine_volume ? $vehicle->engine_volume . ' cm³' : '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('Passport Fuel Consumption') }}</span>
                            <span>{{ $vehicle->passport_fuel_consumption ? $vehicle->passport_fuel_consumption . ' l/100km' : '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('Actual Fuel Consumption') }}</span>
                            <span>{{ $vehicle->fuel_consumption ? $vehicle->fuel_consumption . ' l/100km' : '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">{{ __('Responsible') }}</span>
                            <span>{{ $vehicle->assigned_name ?? '-' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            @can('vehicle_read')
                @include('vehicles.partials.tracking_map')
                @include('vehicles.partials.fuel_consumption')
            @endcan
        </div>
    </div>

    {{-- Second row: Tech Passport and Inspection History side by side --}}
    <div class="row">
        @if ($vehicle->tech_passport_front || $vehicle->tech_passport_back)
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Tech Passport Photos') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if ($vehicle->tech_passport_front)
                        <div class="col-6">
                            <p class="text-muted small mb-1">{{ __('Front Side') }}</p>
                            <a href="{{ asset('uploads/vehicle_documents/' . $vehicle->tech_passport_front) }}" target="_blank">
                                <img src="{{ asset('uploads/vehicle_documents/' . $vehicle->tech_passport_front) }}" 
                                    alt="{{ __('Front Side') }}" class="img-fluid rounded" style="max-height: 150px;">
                            </a>
                        </div>
                        @endif
                        @if ($vehicle->tech_passport_back)
                        <div class="col-6">
                            <p class="text-muted small mb-1">{{ __('Back Side') }}</p>
                            <a href="{{ asset('uploads/vehicle_documents/' . $vehicle->tech_passport_back) }}" target="_blank">
                                <img src="{{ asset('uploads/vehicle_documents/' . $vehicle->tech_passport_back) }}" 
                                    alt="{{ __('Back Side') }}" class="img-fluid rounded" style="max-height: 150px;">
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
        @else
        <div class="col-12">
        @endif
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center"  style="min-height: 135px;>
                    <h5 class="mb-0">{{ __('Inspection History') }}</h5>
                    @can('technical_inspection_manage')
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                            data-bs-target="#addInspectionModal">
                            <i class="ti ti-plus me-1"></i>{{ __('Add Inspection') }}
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    @if ($vehicle->inspections->count() > 0)
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>{{ __('Inspection Date') }}</th>
                                        <th>{{ __('Next Inspection') }}</th>
                                        <th>{{ __('Mileage') }}</th>
                                        <th>{{ __('Cost') }}</th>
                                        <th>{{ __('Service Station') }}</th>
                                        <th>{{ __('Action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($vehicle->inspections as $inspection)
                                        <tr>
                                            <td>{{ $inspection->formatted_inspection_date }}</td>
                                            <td>{{ $inspection->formatted_next_inspection_date }}</td>
                                            <td>{{ $inspection->mileage ? number_format($inspection->mileage, 0, '', ' ') . ' km' : '-' }}</td>
                                            <td>{{ $inspection->formatted_cost ?? '-' }}</td>
                                            <td>{{ $inspection->service_station ?? '-' }}</td>
                                            <td>
                                                @can('technical_inspection_manage')
                                                    <div class="action-btn">
                                                        {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['inspections.destroy', $inspection->id],
                                                            'id' => 'delete-inspection-' . $inspection->id,
                                                        ]) !!}
                                                        <a href="#" class="btn btn-sm bg-danger bs-pass-para"
                                                            data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                            data-confirm="{{ __('Delete record?') . '|' . __('This action cannot be undone.') }}"
                                                            data-confirm-yes="document.getElementById('delete-inspection-{{ $inspection->id }}').submit();">
                                                            <i class="ti ti-trash text-white"></i>
                                                        </a>
                                                        {!! Form::close() !!}
                                                    </div>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-clipboard-check" style="font-size: 48px;"></i>
                            <p class="mt-2">{{ __('No inspection records') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @can('technical_inspection_manage')
        @include('vehicles.partials.inspection_form')
    @endcan
@endsection