{{ Form::model($worker, ['route' => ['worker.update', $worker->id], 'method' => 'PUT', 'enctype' => 'multipart/form-data', 'id' => 'worker_edit_form']) }}
<input type="hidden" name="redirect_to" id="redirect_to_field" value="{{ request('redirect_to', '') }}">
<div class="modal-body">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('first_name', __('First Name'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('first_name', null, ['class' => 'form-control', 'placeholder' => __('Enter first name'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('last_name', __('Last Name'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('last_name', null, ['class' => 'form-control', 'placeholder' => __('Enter last name'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('dob', __('Date of Birth'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::date('dob', $worker->dob ? $worker->dob->format('Y-m-d') : null, ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('gender', __('Gender'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::select('gender', ['male' => __('Male'), 'female' => __('Female')], null, ['class' => 'form-control select2', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('nationality', __('Nationality'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('nationality', null, ['class' => 'form-control', 'placeholder' => __('Enter nationality'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('registration_date', __('Registration Date'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::date('registration_date', $worker->registration_date ? $worker->registration_date->format('Y-m-d') : null, ['class' => 'form-control', 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('phone', __('Phone'), ['class' => 'form-label']) }}
                {{ Form::text('phone', null, ['class' => 'form-control', 'placeholder' => __('Enter phone')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}
                {{ Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('Enter email')]) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('document_photo', __('Document Photo'), ['class' => 'form-label']) }}
                <div class="choose-file form-group">
                    <label for="document_photo" class="form-label">
                        <input type="file" class="form-control" name="document_photo" id="document_photo"
                            data-filename="document_photo_update">
                    </label>
                    <p class="document_photo_update"></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('photo', __('Appearance Photo'), ['class' => 'form-label']) }}
                <div class="choose-file form-group">
                    <label for="photo" class="form-label">
                        <input type="file" class="form-control" name="photo" id="photo"
                            data-filename="photo_update">
                    </label>
                    <p class="photo_update"></p>
                </div>
            </div>
        </div>
        
        @if(isset($canAssignResponsible) && $canAssignResponsible && isset($assignableUsers) && $assignableUsers->count() > 0)
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('responsible_id', __('Responsible'), ['class' => 'form-label']) }}
                {{ Form::select('responsible_id', $assignableUsers->pluck('name', 'id'), $worker->responsible_id, ['class' => 'form-control']) }}
                <small class="text-muted">{{ __('Person responsible for this worker') }}</small>
            </div>
        </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-light" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Save') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}

<script>
(function() {
    setTimeout(function() {
        // Detect if opened from mobile page and set redirect field
        var redirectField = document.getElementById('redirect_to_field');
        if (redirectField && !redirectField.value) {
            if (window.location.pathname.indexOf('/mobile') === 0) {
                redirectField.value = 'mobile';
            }
        }
    }, 100);
})();
</script>