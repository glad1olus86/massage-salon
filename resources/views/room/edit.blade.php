{{ Form::model($room, ['route' => ['room.update', $room->id], 'method' => 'PUT', 'class' => 'needs-validation', 'novalidate', 'id' => 'room_edit_form']) }}
<input type="hidden" name="redirect_to" id="redirect_to_field" value="{{ request('redirect_to', '') }}">
<div class="modal-body">
    <div class="row">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('hotel_id', __('Hotel'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::select('hotel_id', $hotels, null, ['class' => 'form-control select', 'placeholder' => __('Select Hotel'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('room_number', __('Room Number'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('room_number', null, ['class' => 'form-control', 'placeholder' => __('Enter number'), 'required' => 'required']) }}
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                {{ Form::label('capacity', __('Capacity'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::number('capacity', null, ['class' => 'form-control', 'placeholder' => __('Number of beds'), 'required' => 'required', 'min' => 1]) }}
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('monthly_price', __('Monthly Price'), ['class' => 'form-label']) }}<x-required></x-required>
                <div class="input-group">
                    {{ Form::number('monthly_price', null, ['class' => 'form-control', 'placeholder' => __('Enter price'), 'step' => '0.01', 'required' => 'required', 'min' => 0]) }}
                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                </div>
                <small class="text-muted">{{ __('Reference price for the room. Payment settings are configured per resident.') }}</small>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Update') }}" class="btn btn-primary">
</div>
{{ Form::close() }}

<script>
(function() {
    // Detect mobile page and set redirect field
    setTimeout(function() {
        var redirectField = document.getElementById('redirect_to_field');
        if (redirectField && !redirectField.value) {
            if (window.location.pathname.indexOf('/mobile') === 0) {
                redirectField.value = 'mobile';
            }
        }
    }, 100);
})();
</script>
