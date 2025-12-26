@extends('layouts.admin')

@section('page-title')
    {{ __('Create Shift Template') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('attendance.index') }}">{{ __('Attendance') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('attendance.shifts.index') }}">{{ __('Shift Templates') }}</a></li>
    <li class="breadcrumb-item">{{ __('Create') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-8 col-md-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('New Shift Template') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('attendance.shifts.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Work Place') }} <span class="text-danger">*</span></label>
                                <select name="work_place_id" class="form-select @error('work_place_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Work Place') }}</option>
                                    @foreach($workPlaces as $wp)
                                        <option value="{{ $wp->id }}" {{ old('work_place_id', $selectedWorkPlaceId) == $wp->id ? 'selected' : '' }}>
                                            {{ $wp->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('work_place_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">{{ __('Shift Name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name') }}" placeholder="{{ __('e.g. Morning Shift') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Start Time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control @error('start_time') is-invalid @enderror" 
                                       value="{{ old('start_time', '06:00') }}" required>
                                @error('start_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('End Time') }} <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control @error('end_time') is-invalid @enderror" 
                                       value="{{ old('end_time', '14:00') }}" required>
                                @error('end_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Break (minutes)') }}</label>
                                <input type="number" name="break_minutes" class="form-control @error('break_minutes') is-invalid @enderror" 
                                       value="{{ old('break_minutes', 30) }}" min="0" max="480">
                                @error('break_minutes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Color') }}</label>
                                <input type="color" name="color" class="form-control form-control-color" 
                                       value="{{ old('color', '#3788d8') }}" style="height: 38px; width: 100%;">
                            </div>
                        </div>

                        <hr class="my-4">
                        <h6 class="mb-3">{{ __('Payment Settings') }}</h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Pay Type') }} <span class="text-danger">*</span></label>
                                <select name="pay_type" class="form-select @error('pay_type') is-invalid @enderror" required>
                                    <option value="per_shift" {{ old('pay_type', 'per_shift') == 'per_shift' ? 'selected' : '' }}>
                                        {{ __('Per Shift') }}
                                    </option>
                                    <option value="hourly" {{ old('pay_type') == 'hourly' ? 'selected' : '' }}>
                                        {{ __('Hourly') }}
                                    </option>
                                </select>
                                @error('pay_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label class="form-label">{{ __('Pay Rate') }}</label>
                                <input type="number" name="pay_rate" class="form-control @error('pay_rate') is-invalid @enderror" 
                                       value="{{ old('pay_rate') }}" min="0" step="0.01" placeholder="0.00">
                                @error('pay_rate')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="form-check">
                                    <input type="checkbox" name="night_bonus_enabled" class="form-check-input" 
                                           id="nightBonusEnabled" value="1" {{ old('night_bonus_enabled') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="nightBonusEnabled">
                                        {{ __('Enable Night Bonus') }}
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3" id="nightBonusPercent" style="{{ old('night_bonus_enabled') ? '' : 'display: none;' }}">
                                <label class="form-label">{{ __('Night Bonus (%)') }}</label>
                                <input type="number" name="night_bonus_percent" class="form-control" 
                                       value="{{ old('night_bonus_percent', 20) }}" min="0" max="100" step="0.01">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('attendance.shifts.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn btn-primary">{{ __('Create Shift Template') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script-page')
<script>
document.getElementById('nightBonusEnabled').addEventListener('change', function() {
    document.getElementById('nightBonusPercent').style.display = this.checked ? 'block' : 'none';
});
</script>
@endpush
