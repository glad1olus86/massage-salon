@extends('layouts.admin')

@section('page-title')
    {{ __('Edit Vehicle') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">{{ __('Vehicles') }}</a></li>
    <li class="breadcrumb-item">{{ __('Edit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Vehicle Information') }}</h5>
                </div>
                <div class="card-body">
                    {{ Form::model($vehicle, ['route' => ['vehicles.update', $vehicle->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data']) }}
                    
                    {{-- Hidden field for mobile redirect --}}
                    @if(request('redirect_to'))
                        <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {{ Form::label('license_plate', __('License Plate'), ['class' => 'form-label']) }}
                                <x-required></x-required>
                                {{ Form::text('license_plate', null, ['class' => 'form-control', 'required' => true]) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {{ Form::label('brand', __('Brand/Model'), ['class' => 'form-label']) }}
                                <x-required></x-required>
                                {{ Form::text('brand', null, ['class' => 'form-control', 'required' => true]) }}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('color', __('Color'), ['class' => 'form-label']) }}
                                {{ Form::text('color', null, ['class' => 'form-control']) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('vin_code', __('VIN Code'), ['class' => 'form-label']) }}
                                {{ Form::text('vin_code', null, ['class' => 'form-control', 'maxlength' => 30]) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('registration_date', __('Registration Date'), ['class' => 'form-label']) }}
                                {{ Form::date('registration_date', $vehicle->registration_date, ['class' => 'form-control']) }}
                                <small class="text-muted">{{ __('First registration in state registry') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('engine_volume', __('Engine Volume (cm³)'), ['class' => 'form-label']) }}
                                {{ Form::number('engine_volume', null, ['class' => 'form-control', 'min' => '0', 'max' => '99999']) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('passport_fuel_consumption', __('Passport Fuel Consumption (l/100km)'), ['class' => 'form-label']) }}
                                {{ Form::number('passport_fuel_consumption', null, ['class' => 'form-control', 'step' => '0.1', 'min' => '0', 'max' => '99.9']) }}
                                <small class="text-muted">{{ __('From tech passport (V.8)') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('fuel_consumption', __('Actual Fuel Consumption (l/100km)'), ['class' => 'form-label']) }}
                                {{ Form::number('fuel_consumption', null, ['class' => 'form-control', 'step' => '0.1', 'min' => '0', 'max' => '99.9']) }}
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        {{ Form::label('photo', __('Vehicle Photo'), ['class' => 'form-label']) }}
                        @if ($vehicle->photo)
                            <div class="mb-2">
                                <img src="{{ asset('uploads/vehicle_photos/' . $vehicle->photo) }}" alt="" class="rounded" style="max-width: 200px;">
                            </div>
                        @endif
                        {{ Form::file('photo', ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/webp']) }}
                        <small class="text-muted">{{ __('Leave empty to keep current photo') }}</small>
                    </div>

                    <hr>
                    <h6 class="mb-3">{{ __('Responsible (optional)') }}</h6>

                    @php
                        $currentType = '';
                        if ($vehicle->assigned_type === \App\Models\Worker::class) $currentType = 'worker';
                        elseif ($vehicle->assigned_type === \App\Models\User::class) $currentType = 'user';
                    @endphp

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('assigned_type', __('Type'), ['class' => 'form-label']) }}
                                {{ Form::select('assigned_type', ['' => __('Not assigned'), 'worker' => __('Worker'), 'user' => __('User')], $currentType, ['class' => 'form-control', 'id' => 'assigned_type']) }}
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                {{ Form::label('assigned_id', __('Responsible'), ['class' => 'form-label']) }}
                                <select name="assigned_id" id="assigned_id" class="form-control">
                                    <option value="">{{ __('Select') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ request('redirect_to') === 'mobile' ? route('mobile.vehicles.show', $vehicle->id) : route('vehicles.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>

                    {{ Form::close() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
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
