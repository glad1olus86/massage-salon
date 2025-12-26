@extends('layouts.mobile')

@section('content')
    {{-- Header with back button --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <a href="{{ route('mobile.vehicles.show', $vehicle->id) }}" class="mobile-header-btn">
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
        {{-- Page Title --}}
        <div class="mobile-section-title mb-3">
            <div class="mobile-section-title-left">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2">
                    <circle cx="7" cy="17" r="2"></circle>
                    <circle cx="17" cy="17" r="2"></circle>
                    <path d="M5 17h-2v-6l2 -5h9l4 5h1a2 2 0 0 1 2 2v4h-2m-4 0h-6m-6 -6h15m-6 0v-5"></path>
                </svg>
                <span>{{ __('Edit Vehicle') }}</span>
            </div>
        </div>

        {{ Form::model($vehicle, ['route' => ['vehicles.update', $vehicle->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data']) }}
        
        {{-- Hidden field for mobile redirect --}}
        <input type="hidden" name="redirect_to" value="mobile">

        {{-- Basic Information --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>{{ __('Vehicle Information') }}</h6>
            
            <div class="form-group mb-3">
                {{ Form::label('license_plate', __('License Plate'), ['class' => 'form-label']) }}
                <span class="text-danger">*</span>
                {{ Form::text('license_plate', null, ['class' => 'form-control', 'required' => true]) }}
            </div>

            <div class="form-group mb-3">
                {{ Form::label('brand', __('Brand/Model'), ['class' => 'form-label']) }}
                <span class="text-danger">*</span>
                {{ Form::text('brand', null, ['class' => 'form-control', 'required' => true]) }}
            </div>

            <div class="form-group mb-3">
                {{ Form::label('color', __('Color'), ['class' => 'form-label']) }}
                {{ Form::text('color', null, ['class' => 'form-control']) }}
            </div>

            <div class="form-group mb-3">
                {{ Form::label('vin_code', __('VIN Code'), ['class' => 'form-label']) }}
                {{ Form::text('vin_code', null, ['class' => 'form-control', 'maxlength' => 30]) }}
            </div>

            <div class="form-group mb-3">
                {{ Form::label('registration_date', __('Registration Date'), ['class' => 'form-label']) }}
                {{ Form::date('registration_date', $vehicle->registration_date, ['class' => 'form-control']) }}
                <small class="text-muted">{{ __('First registration in state registry') }}</small>
            </div>

            <div class="form-group mb-3">
                {{ Form::label('engine_volume', __('Engine Volume (cm³)'), ['class' => 'form-label']) }}
                {{ Form::number('engine_volume', null, ['class' => 'form-control', 'min' => '0', 'max' => '99999']) }}
            </div>

            <div class="form-group mb-3">
                {{ Form::label('passport_fuel_consumption', __('Passport Fuel Consumption (l/100km)'), ['class' => 'form-label']) }}
                {{ Form::number('passport_fuel_consumption', null, ['class' => 'form-control', 'step' => '0.1', 'min' => '0', 'max' => '99.9']) }}
                <small class="text-muted">{{ __('From tech passport (V.8)') }}</small>
            </div>

            <div class="form-group mb-3">
                {{ Form::label('fuel_consumption', __('Actual Fuel Consumption (l/100km)'), ['class' => 'form-label']) }}
                {{ Form::number('fuel_consumption', null, ['class' => 'form-control', 'step' => '0.1', 'min' => '0', 'max' => '99.9']) }}
            </div>
        </div>

        {{-- Vehicle Photo --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-camera me-2 text-primary"></i>{{ __('Vehicle Photo') }}</h6>
            
            @if ($vehicle->photo)
                <div class="mb-3 text-center">
                    <img src="{{ asset('uploads/vehicle_photos/' . $vehicle->photo) }}" alt="" class="rounded" style="max-width: 100%; max-height: 200px;">
                </div>
            @endif
            
            <div class="form-group">
                {{ Form::file('photo', ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/webp']) }}
                <small class="text-muted">{{ __('Leave empty to keep current photo') }}</small>
            </div>
        </div>

        {{-- Assignment --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-user me-2 text-primary"></i>{{ __('Responsible (optional)') }}</h6>
            
            @php
                $currentType = '';
                if ($vehicle->assigned_type === \App\Models\Worker::class) $currentType = 'worker';
                elseif ($vehicle->assigned_type === \App\Models\User::class) $currentType = 'user';
            @endphp

            <div class="form-group mb-3">
                {{ Form::label('assigned_type', __('Type'), ['class' => 'form-label']) }}
                {{ Form::select('assigned_type', ['' => __('Not assigned'), 'worker' => __('Worker'), 'user' => __('User')], $currentType, ['class' => 'form-control', 'id' => 'assigned_type']) }}
            </div>

            <div class="form-group">
                {{ Form::label('assigned_id', __('Responsible'), ['class' => 'form-label']) }}
                <select name="assigned_id" id="assigned_id" class="form-control">
                    <option value="">{{ __('Select') }}</option>
                </select>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mobile-actions mt-4 mb-4">
            <button type="submit" class="btn mobile-btn-primary w-100 mb-2">
                <i class="ti ti-device-floppy me-2"></i>{{ __('Save') }}
            </button>
            <a href="{{ route('mobile.vehicles.show', $vehicle->id) }}" class="btn btn-outline-secondary w-100">
                {{ __('Cancel') }}
            </a>
        </div>

        {{ Form::close() }}
    </div>

    <style>
        .mobile-btn-primary {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
            color: #fff !important;
        }
        .mobile-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .form-label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 10px 12px;
        }
        .form-control:focus {
            border-color: #FF0049;
            box-shadow: 0 0 0 0.2rem rgba(255, 0, 73, 0.25);
        }
        .text-primary {
            color: #FF0049 !important;
        }
    </style>
@endsection

@push('scripts')
<script>
    var workers = @json($workers->map(fn($w) => ['id' => $w->id, 'name' => $w->first_name . ' ' . $w->last_name]));
    var users = @json($users->map(fn($u) => ['id' => $u->id, 'name' => $u->name]));
    var currentType = '{{ $currentType }}';
    var currentId = {{ $vehicle->assigned_id ?? 'null' }};

    function updateAssignedSelect() {
        var select = document.getElementById('assigned_id');
        var type = document.getElementById('assigned_type').value;

        select.innerHTML = '';

        if (!type) {
            select.innerHTML = '<option value="">{{ __("First select type") }}</option>';
            select.disabled = true;
            return;
        }

        var items = type === 'worker' ? workers : users;
        select.innerHTML = '<option value="">{{ __("Select") }}</option>';
        items.forEach(function(item) {
            var selected = (type === currentType && item.id === currentId) ? ' selected' : '';
            select.innerHTML += '<option value="' + item.id + '"' + selected + '>' + item.name + '</option>';
        });
        select.disabled = false;
    }

    document.getElementById('assigned_type').addEventListener('change', updateAssignedSelect);
    updateAssignedSelect();
</script>
@endpush
