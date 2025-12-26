{{ Form::open(['url' => 'hotel', 'method' => 'post', 'class' => 'needs-validation', 'novalidate', 'id' => 'hotel_create_form']) }}
<input type="hidden" name="redirect_to" id="redirect_to_field" value="{{ request('redirect_to', '') }}">
<div class="modal-body">
    <div class="row ">
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter hotel name'), 'required' => 'required']) }}
                @error('name')
                    <span class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
            </div>
        </div>
        <div class="col-12">
            <div class="form-group">
                {{ Form::label('address', __('Address'), ['class' => 'form-label']) }}<x-required></x-required>
                {{ Form::text('address', null, ['class' => 'form-control', 'placeholder' => __('Enter hotel address'), 'required' => 'required']) }}
                @error('address')
                    <span class="invalid-name" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </span>
                @enderror
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
    </div>
</div>
<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn  btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn  btn-primary">
</div>
{{ Form::close() }}

<script>
(function() {
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