@extends('layouts.admin')

@section('page-title')
    {{ __('Add Vehicle') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('vehicles.index') }}">{{ __('Vehicles') }}</a></li>
    <li class="breadcrumb-item">{{ __('Add') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8">
            {{-- Scan Document Card --}}
            <div class="card mb-3">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1"><i class="ti ti-scan me-2"></i>{{ __('Auto-fill from Tech Passport') }}</h6>
                            <small class="text-muted">{{ __('Upload photos of both sides of vehicle registration document') }}</small>
                        </div>
                        <div>
                            <input type="file" id="scan_document_input" accept="image/*" multiple style="display: none;">
                            <button type="button" class="btn btn-info" id="scan_document_btn">
                                <i class="ti ti-camera me-1"></i>{{ __('Scan Document') }}
                            </button>
                        </div>
                    </div>
                    <div id="scan_preview" class="mt-2 d-flex gap-2 flex-wrap" style="display: none;"></div>
                    <div id="scan_status" class="mt-2" style="display: none;">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm text-info me-2" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="text-info">{{ __('Scanning document...') }}</span>
                        </div>
                    </div>
                    <div id="scan_result" class="mt-2" style="display: none;"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Vehicle Information') }}</h5>
                </div>
                <div class="card-body">
                    {{ Form::open(['route' => 'vehicles.store', 'method' => 'POST', 'enctype' => 'multipart/form-data']) }}
                    
                    {{-- Hidden field for mobile redirect --}}
                    @if(request('redirect_to'))
                        <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
                    @endif
                    
                    {{-- Hidden fields for scanned tech passport photos --}}
                    <input type="hidden" name="scanned_tech_passport_front" id="scanned_tech_passport_front" value="">
                    <input type="hidden" name="scanned_tech_passport_back" id="scanned_tech_passport_back" value="">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                {{ Form::label('license_plate', __('License Plate'), ['class' => 'form-label']) }}
                                <x-required></x-required>
                                {{ Form::text('license_plate', null, ['class' => 'form-control', 'required' => true, 'placeholder' => __('For example: 1A2 3456'), 'id' => 'license_plate']) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                {{ Form::label('brand', __('Brand/Model'), ['class' => 'form-label']) }}
                                <x-required></x-required>
                                {{ Form::text('brand', null, ['class' => 'form-control', 'required' => true, 'placeholder' => __('For example: Škoda Octavia'), 'id' => 'brand']) }}
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('color', __('Color'), ['class' => 'form-label']) }}
                                {{ Form::text('color', null, ['class' => 'form-control', 'placeholder' => __('For example: White'), 'id' => 'color']) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('vin_code', __('VIN Code'), ['class' => 'form-label']) }}
                                {{ Form::text('vin_code', null, ['class' => 'form-control', 'maxlength' => 30, 'placeholder' => __('17-20 characters'), 'id' => 'vin_code']) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('registration_date', __('Registration Date'), ['class' => 'form-label']) }}
                                {{ Form::date('registration_date', null, ['class' => 'form-control', 'id' => 'registration_date']) }}
                                <small class="text-muted">{{ __('First registration in state registry') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('engine_volume', __('Engine Volume (cm³)'), ['class' => 'form-label']) }}
                                {{ Form::number('engine_volume', null, ['class' => 'form-control', 'min' => '0', 'max' => '99999', 'placeholder' => '1498', 'id' => 'engine_volume']) }}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('passport_fuel_consumption', __('Passport Fuel Consumption (l/100km)'), ['class' => 'form-label']) }}
                                {{ Form::number('passport_fuel_consumption', null, ['class' => 'form-control', 'step' => '0.1', 'min' => '0', 'max' => '99.9', 'placeholder' => '5.4', 'id' => 'passport_fuel_consumption']) }}
                                <small class="text-muted">{{ __('From tech passport (V.8)') }}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('fuel_consumption', __('Actual Fuel Consumption (l/100km)'), ['class' => 'form-label']) }}
                                {{ Form::number('fuel_consumption', null, ['class' => 'form-control', 'step' => '0.1', 'min' => '0', 'max' => '99.9', 'placeholder' => '7.5', 'id' => 'fuel_consumption']) }}
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        {{ Form::label('photo', __('Vehicle Photo'), ['class' => 'form-label']) }}
                        {{ Form::file('photo', ['class' => 'form-control', 'accept' => 'image/jpeg,image/png,image/webp']) }}
                        <small class="text-muted">{{ __('JPG, PNG or WebP. Maximum 5MB.') }}</small>
                    </div>

                    <hr>
                    <h6 class="mb-3">{{ __('Responsible (optional)') }}</h6>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                {{ Form::label('assigned_type', __('Type'), ['class' => 'form-label']) }}
                                {{ Form::select('assigned_type', ['' => __('Not assigned'), 'worker' => __('Worker'), 'user' => __('User')], null, ['class' => 'form-control', 'id' => 'assigned_type']) }}
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                {{ Form::label('assigned_id', __('Responsible'), ['class' => 'form-label']) }}
                                <select name="assigned_id" id="assigned_id" class="form-control" disabled>
                                    <option value="">{{ __('First select type') }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <a href="{{ request('redirect_to') === 'mobile' ? route('mobile.vehicles.index') : route('vehicles.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
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

    document.getElementById('assigned_type').addEventListener('change', function() {
        var select = document.getElementById('assigned_id');
        var type = this.value;

        select.innerHTML = '';

        if (!type) {
            select.innerHTML = '<option value="">{{ __("First select type") }}</option>';
            select.disabled = true;
            return;
        }

        var items = type === 'worker' ? workers : users;
        select.innerHTML = '<option value="">{{ __("Select") }}</option>';
        items.forEach(function(item) {
            select.innerHTML += '<option value="' + item.id + '">' + item.name + '</option>';
        });
        select.disabled = false;
    });

    // Document Scanner
    (function() {
        var scanBtn = document.getElementById('scan_document_btn');
        var scanInput = document.getElementById('scan_document_input');
        var scanStatus = document.getElementById('scan_status');
        var scanResult = document.getElementById('scan_result');
        var scanPreview = document.getElementById('scan_preview');
        var selectedFiles = [];

        scanBtn.onclick = function(e) {
            e.preventDefault();
            scanInput.click();
        };

        scanInput.onchange = function() {
            if (this.files && this.files.length > 0) {
                selectedFiles = Array.from(this.files);
                
                // Show preview
                scanPreview.innerHTML = '';
                scanPreview.style.display = 'flex';
                
                selectedFiles.forEach(function(file, index) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'width: 80px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;';
                        img.title = file.name;
                        scanPreview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });

                // Add scan button
                var scanNowBtn = document.createElement('button');
                scanNowBtn.type = 'button';
                scanNowBtn.className = 'btn btn-sm btn-success align-self-center';
                scanNowBtn.innerHTML = '<i class="ti ti-scan me-1"></i>{{ __("Scan") }} (' + selectedFiles.length + ')';
                scanNowBtn.onclick = performScan;
                scanPreview.appendChild(scanNowBtn);
            }
        };

        function performScan() {
            if (selectedFiles.length === 0) return;

            var formData = new FormData();
            selectedFiles.forEach(function(file) {
                formData.append('document_images[]', file);
            });
            formData.append('_token', '{{ csrf_token() }}');

            // Show loading
            scanStatus.style.display = 'block';
            scanResult.style.display = 'none';
            scanBtn.disabled = true;

            fetch('{{ route("vehicles.scan.document") }}', {
                method: 'POST',
                body: formData
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                scanStatus.style.display = 'none';
                scanBtn.disabled = false;

                if (data.success && data.data) {
                    // Fill form fields
                    if (data.data.license_plate) {
                        document.getElementById('license_plate').value = data.data.license_plate;
                    }
                    if (data.data.brand) {
                        document.getElementById('brand').value = data.data.brand;
                    }
                    if (data.data.color) {
                        document.getElementById('color').value = data.data.color;
                    }
                    if (data.data.vin_code) {
                        document.getElementById('vin_code').value = data.data.vin_code;
                    }
                    if (data.data.registration_date) {
                        document.getElementById('registration_date').value = data.data.registration_date;
                    }
                    if (data.data.engine_volume) {
                        document.getElementById('engine_volume').value = data.data.engine_volume;
                    }
                    if (data.data.passport_fuel_consumption) {
                        document.getElementById('passport_fuel_consumption').value = data.data.passport_fuel_consumption;
                    }

                    // Save scanned document paths
                    if (data.scanned_documents) {
                        if (data.scanned_documents.front) {
                            document.getElementById('scanned_tech_passport_front').value = data.scanned_documents.front;
                        }
                        if (data.scanned_documents.back) {
                            document.getElementById('scanned_tech_passport_back').value = data.scanned_documents.back;
                        }
                    }

                    scanResult.innerHTML = '<div class="alert alert-success mb-0 py-2"><i class="ti ti-check me-1"></i>{{ __("Data extracted successfully! Tech passport photos attached.") }}</div>';
                    scanResult.style.display = 'block';
                } else if (data.error) {
                    scanResult.innerHTML = '<div class="alert alert-danger mb-0 py-2"><i class="ti ti-alert-circle me-1"></i>' + data.error + '</div>';
                    scanResult.style.display = 'block';
                }
            })
            .catch(function(error) {
                scanStatus.style.display = 'none';
                scanBtn.disabled = false;
                scanResult.innerHTML = '<div class="alert alert-danger mb-0 py-2"><i class="ti ti-alert-circle me-1"></i>{{ __("Error scanning document") }}</div>';
                scanResult.style.display = 'block';
                console.error('Scan error:', error);
            });

            // Reset
            scanInput.value = '';
            selectedFiles = [];
        }
    })();
</script>
@endpush
