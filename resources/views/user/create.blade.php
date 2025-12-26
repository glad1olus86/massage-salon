{{ Form::open(['url' => 'users', 'method' => 'post', 'class'=>'needs-validation', 'novalidate']) }}
<div class="modal-body">
    @if (\Auth::user()->type == 'super admin')
        {{-- Super Admin - Create Company --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
                    {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter Company Name'), 'required' => 'required']) }}
                    @error('name')
                        <small class="invalid-name" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}<x-required></x-required>
                    {{ Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('Enter Company Email'), 'required' => 'required']) }}
                    @error('email')
                        <small class="invalid-email" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
            {!! Form::hidden('role', 'company', null, ['class' => 'form-control select2', 'required' => 'required']) !!}
            <div class="col-md-6 mb-3 form-group mt-4">
                <label for="password_switch">{{ __('Login is enable') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="password_switch" class="form-check-input input-primary pointer" value="on" id="password_switch">
                    <label class="form-check-label" for="password_switch"></label>
                </div>
            </div>
            <div class="col-md-6 ps_div d-none">
                <div class="form-group">
                    {{ Form::label('password', __('Password'), ['class' => 'form-label']) }}<x-required></x-required>
                    {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('Enter Company Password'), 'minlength' => '6']) }}
                    @error('password')
                        <small class="invalid-password" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
        </div>
    @else
        {{-- Company - Create User with Billing Info --}}
        
        {{-- Header with icon --}}
        <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
            <div class="bg-primary rounded-3 p-3 me-3">
                <i class="ti ti-user-plus text-white fs-4"></i>
            </div>
            <div>
                <h5 class="mb-1">{{ __('User Management') }}</h5>
                <p class="text-muted mb-0 small">{{ __('Add new team member to your organization') }}</p>
            </div>
        </div>

        {{-- Billing Info Card --}}
        @if(isset($billingInfo))
        <div class="card bg-light border-0 mb-4">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center border-end">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <i class="ti ti-users text-primary fs-4"></i>
                            <div class="text-start">
                                <h4 class="mb-0 text-primary">{{ $billingInfo['total_current'] }}/{{ $billingInfo['base_limit'] }}</h4>
                                <small class="text-muted">{{ __('Users') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center border-end">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <i class="ti ti-briefcase text-info fs-4"></i>
                            <div class="text-start">
                                <h4 class="mb-0 text-info">{{ $billingInfo['current_managers'] }}</h4>
                                <small class="text-muted">{{ __('Managers') }}</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <i class="ti ti-eye text-success fs-4"></i>
                            <div class="text-start">
                                <h4 class="mb-0 text-success">{{ $billingInfo['current_curators'] }}</h4>
                                <small class="text-muted">{{ __('Curators') }}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Warning if over limit --}}
        @if($billingInfo['spots_remaining'] <= 0)
        <div class="alert alert-warning d-flex align-items-start mb-4" id="billing-warning">
            <i class="ti ti-alert-triangle fs-4 me-2 mt-1"></i>
            <div>
                <strong>{{ __('Limit reached') }}</strong>
                <p class="mb-0 small">
                    {{ __('Adding Manager or Curator will add extra charge to your monthly subscription.') }}
                    <br>
                    <span class="text-dark">
                        {{ __('Manager') }}: <strong>{{ $billingInfo['currency_symbol'] ?? '$' }}{{ number_format($billingInfo['manager_price'], 2) }}</strong>/{{ __('month') }} | 
                        {{ __('Curator') }}: <strong>{{ $billingInfo['currency_symbol'] ?? '$' }}{{ number_format($billingInfo['curator_price'], 2) }}</strong>/{{ __('month') }}
                    </span>
                </p>
            </div>
        </div>
        @elseif($billingInfo['spots_remaining'] <= 2)
        <div class="alert alert-info d-flex align-items-start mb-4" id="billing-info">
            <i class="ti ti-info-circle fs-4 me-2 mt-1"></i>
            <div>
                <strong>{{ $billingInfo['spots_remaining'] }} {{ __('spots remaining') }}</strong>
                <p class="mb-0 small">{{ __('After reaching the limit, additional users will be charged.') }}</p>
            </div>
        </div>
        @endif
        @endif

        {{-- Form Fields --}}
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('name', __('Name'), ['class' => 'form-label']) }}<x-required></x-required>
                    {{ Form::text('name', null, ['class' => 'form-control', 'placeholder' => __('Enter User Name'), 'required' => 'required']) }}
                    @error('name')
                        <small class="invalid-name" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {{ Form::label('email', __('Email'), ['class' => 'form-label']) }}<x-required></x-required>
                    {{ Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('Enter User Email'), 'required' => 'required']) }}
                    @error('email')
                        <small class="invalid-email" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
            <div class="form-group col-md-6">
                {{ Form::label('role', __('User Role'), ['class' => 'form-label']) }}<x-required></x-required>
                {!! Form::select('role', $roles, null, ['class' => 'form-control select', 'required' => 'required', 'id' => 'user-role-select']) !!}
                <div class="text-xs mt-1">
                    {{ __('Create role here.') }} <a href="{{ route('roles.index') }}"><b>{{ __('Create role') }}</b></a>
                </div>
                @error('role')
                    <small class="invalid-role" role="alert">
                        <strong class="text-danger">{{ $message }}</strong>
                    </small>
                @enderror
            </div>
            <div class="col-md-5 mb-3 form-group mt-4">
                <label for="password_switch">{{ __('Login is enable') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="password_switch" class="form-check-input input-primary pointer" value="on" id="password_switch">
                    <label class="form-check-label" for="password_switch"></label>
                </div>
            </div>
            <div class="col-md-6 ps_div d-none">
                <div class="form-group">
                    {{ Form::label('password', __('Password'), ['class' => 'form-label']) }}<x-required></x-required>
                    {{ Form::password('password', ['class' => 'form-control', 'placeholder' => __('Enter User Password'), 'minlength' => '6']) }}
                    @error('password')
                        <small class="invalid-password" role="alert">
                            <strong class="text-danger">{{ $message }}</strong>
                        </small>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Dynamic billing notice based on role selection --}}
        @if(isset($billingInfo))
        <div id="role-billing-notice" class="alert alert-primary d-none mt-3">
            <div class="d-flex align-items-center">
                <i class="ti ti-currency-dollar fs-4 me-2"></i>
                <div>
                    <strong id="role-billing-title"></strong>
                    <p class="mb-0 small" id="role-billing-text"></p>
                </div>
            </div>
        </div>
        @endif
    @endif

    @if (!$customFields->isEmpty())
        @include('customFields.formBuilder')
    @endif
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{ __('Create') }}" class="btn btn-primary">
</div>

{{ Form::close() }}


@if (\Auth::user()->type != 'super admin' && isset($billingInfo))
<script>
$(document).ready(function() {
    var billingInfo = @json($billingInfo);
    var currencySymbol = billingInfo.currency_symbol || '$';
    
    function updateBillingNotice() {
        var selectedRole = $('#user-role-select option:selected').text().toLowerCase();
        var notice = $('#role-billing-notice');
        var title = $('#role-billing-title');
        var text = $('#role-billing-text');
        
        if (selectedRole === 'manager' || selectedRole === 'curator') {
            var price = selectedRole === 'manager' ? billingInfo.manager_price : billingInfo.curator_price;
            var wouldExceed = billingInfo.spots_remaining <= 0;
            
            if (wouldExceed) {
                notice.removeClass('d-none alert-primary alert-info').addClass('alert-warning');
                title.text('{{ __("Additional charge") }}');
                text.html('{{ __("This") }} ' + selectedRole + ' {{ __("will add") }} <strong>' + currencySymbol + price.toFixed(2) + '</strong> {{ __("to your monthly subscription") }}');
            } else {
                notice.removeClass('d-none alert-warning').addClass('alert-info');
                title.text('{{ __("Within plan limit") }}');
                text.text('{{ __("This user is within your plan limit. No additional charges.") }}');
            }
        } else {
            notice.addClass('d-none');
        }
    }
    
    $('#user-role-select').on('change', updateBillingNotice);
    updateBillingNotice();
});
</script>
@endif
