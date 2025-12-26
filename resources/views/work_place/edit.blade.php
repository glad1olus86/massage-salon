{{ Form::model($workPlace, ['route' => ['work-place.update', $workPlace->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate']) }}
@if(request('redirect_to'))
    <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
@endif
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('name', __('Work Place Name'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter name'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('address', __('Address'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('address', null, ['class' => 'form-control', 'placeholder' => __('Enter address'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('phone', __('Contact Phone'), ['class' => 'form-label']) }}
                {{ Form::text('phone', null, ['class' => 'form-control', 'placeholder' => __('Enter contact phone')]) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('email', __('Contact Email'), ['class' => 'form-label']) }}
                {{ Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('Enter contact email')]) }}
            </div>
        </div>
        
        @if(isset($canAssignResponsible) && $canAssignResponsible && isset($assignableUsers) && $assignableUsers->count() > 0)
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('responsible_id', __('Responsible'), ['class' => 'form-label']) }}
                {{ Form::select('responsible_id', $assignableUsers->pluck('name', 'id'), $workPlace->responsible_id, ['class' => 'form-control']) }}
                <small class="text-muted">{{ __('Person responsible for this work place') }}</small>
            </div>
        </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}
