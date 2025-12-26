@extends('layouts.admin')
@php
    $dir = asset(Storage::url('uploads/plan'));
@endphp
@section('page-title')
    {{ __('Manage Plan') }}
@endsection
@push('css-page')
<style>
    /* Plan cards grid */
    .plan_card {
        width: 33.333%;
        padding: 0 10px;
        margin-bottom: 30px;
    }
    
    .plan_card .price-card {
        height: 100%;
        position: relative;
        padding-top: 15px;
    }
    
    .plan_card .card-body {
        padding: 25px 20px 20px;
    }
    
    /* Badge - название плана */
    .plan_card .price-badge {
        position: absolute;
        top: -12px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 10;
        white-space: nowrap;
    }
    
    /* Списки фич */
    .plan-features li, .plan-modules li {
        padding: 5px 0;
        font-size: 12px;
        display: flex;
        align-items: center;
    }
    
    .price-card .list-unstyled .theme-avtar {
        width: 20px;
        min-width: 20px;
        margin-right: 8px !important;
        flex-shrink: 0;
    }
    
    .plan-value {
        font-weight: 600;
        color: #FF0049;
        min-width: 30px;
        margin-right: 5px;
        font-size: 12px;
        flex-shrink: 0;
    }
    
    .plan-label {
        color: #555;
        font-size: 12px;
    }
    
    /* Иконки */
    .ti-circle-check, .ti-circle-x, .ti-circle-plus, .ti-user, .ti-currency-dollar {
        font-size: 16px;
    }

    /* Кнопки */
    .request-btn .btn {
        padding: 8px 15px !important;
        font-size: 13px;
    }
    
    /* Цена */
    .price-card h1 {
        font-size: 1.6rem;
    }
    
    .price-card h1 small {
        font-size: 0.75rem;
    }

    /* Responsive */
    @media screen and (max-width: 1200px) {
        .plan_card {
            width: 50%;
        }
    }
    
    @media screen and (max-width: 767px) {
        .plan_card {
            width: 100%;
        }
        .plan_card .price-card {
            height: auto;
        }
    }
    
    @media screen and (max-width: 481px) {
        .plan_card .card-body .row .col-6 {
            width: 100%;
        }
    }
</style>
@endpush
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ __('Plan') }}</li>
@endsection
@section('action-btn')
    <div class="float-end">
        @can('create plan')
                    <a href="#" data-size="lg" data-url="{{ route('plans.create') }}" data-ajax-popup="true"
                        data-bs-toggle="tooltip" title="{{ __('Create') }}" data-title="{{ __('Create New Plan') }}"
                        class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i>
                    </a>
        @endcan
    </div>
@endsection

@section('content')
    <div class="row">
        @foreach ($plans as $plan)
            <div class="plan_card">
                <div class="card price-card price-1 wow animate__fadeInUp" data-wow-delay="0.2s"
                    style="
                   visibility: visible;
                   animation-delay: 0.2s;
                   animation-name: fadeInUp;
                   ">
                    <div class="card-body">
                        <span class="price-badge bg-primary">{{ $plan->name }}</span>
                        @if (\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id)
                            <div class="d-flex flex-row-reverse m-0 p-0 active-tag">
                                <span class=" align-items-right">
                                    <i class="f-10 lh-1 fas fa-circle text-primary"></i>
                                    <span class="ms-2">{{ __('Active') }}</span>
                                </span>
                            </div>
                        @endif
                        @if (\Auth::user()->type == 'super admin' && $plan->price > 0)
                        <div class="d-flex flex-row-reverse m-0 p-0 active-tag">
                            <div class="form-check form-switch custom-switch-v1 float-end">
                                <input type="checkbox" name="plan_disable"
                                class="form-check-input input-primary is_disable" value="1"
                                data-id='{{ $plan->id }}'
                                data-name="{{ __('plan') }}"
                                {{ $plan->is_disable == 1 ? 'checked' : '' }}>
                            <label class="form-check-label" for="plan_disable"></label>
                            </div>
                        </div>
                    @endif
                        <h1 class="mb-4 f-w-600 ">
                            {{ isset($admin_payment_setting['currency_symbol']) ? $admin_payment_setting['currency_symbol'] : '$' }}{{ number_format($plan->price) }}
                            <small class="text-sm">/{{ __(\App\Models\Plan::$arrDuration[$plan->duration]) }}</small>
                        </h1>
                        <p class="mb-0">
                            {{ __('Free Trial Days : ') . __($plan->trial_days ? $plan->trial_days : 0) }}<br />
                        </p>

                        <div class="row">
                            <div class="col-6">
                                <ul class="list-unstyled my-4 plan-features">
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                        <span class="plan-value">{{ $plan->max_users == -1 ? '∞' : $plan->max_users }}</span>
                                        <span class="plan-label">{{ __('Users') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                        <span class="plan-value">{{ ($plan->max_roles ?? -1) == -1 ? '∞' : $plan->max_roles }}</span>
                                        <span class="plan-label">{{ __('Roles') }}</span>
                                    </li>
                                    @php
                                        $billingSettings = \App\Models\Utility::getAdminBillingSettings();
                                    @endphp
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="text-primary ti ti-user"></i></span>
                                        <span class="plan-value">{{ $plan->base_users_limit ?? $plan->max_users }}</span>
                                        <span class="plan-label">{{ __('Base Users') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                        <span class="plan-value">{{ ($plan->max_workers ?? -1) == -1 ? '∞' : $plan->max_workers }}</span>
                                        <span class="plan-label">{{ __('Workers') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                        <span class="plan-value">{{ ($plan->max_hotels ?? -1) == -1 ? '∞' : $plan->max_hotels }}</span>
                                        <span class="plan-label">{{ __('Hotels') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                        <span class="plan-value">{{ ($plan->max_vehicles ?? -1) == -1 ? '∞' : $plan->max_vehicles }}</span>
                                        <span class="plan-label">{{ __('Vehicles') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="text-primary ti ti-circle-plus"></i></span>
                                        <span class="plan-value">{{ ($plan->max_workplaces ?? -1) == -1 ? '∞' : $plan->max_workplaces }}</span>
                                        <span class="plan-label">{{ __('Workplaces') }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-6">
                                <ul class="list-unstyled my-4 plan-modules">
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="ti {{ ($plan->module_workers ?? 1) == 1 ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }}"></i></span>
                                        <span class="plan-label">{{ __('Workers') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="ti {{ ($plan->module_hotels ?? 1) == 1 ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }}"></i></span>
                                        <span class="plan-label">{{ __('Hotels') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="ti {{ ($plan->module_vehicles ?? 1) == 1 ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }}"></i></span>
                                        <span class="plan-label">{{ __('Vehicles') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="ti {{ ($plan->module_workplaces ?? 1) == 1 ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }}"></i></span>
                                        <span class="plan-label">{{ __('Workplaces') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="ti {{ ($plan->module_cashbox ?? 1) == 1 ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }}"></i></span>
                                        <span class="plan-label">{{ __('Cashbox') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="ti {{ ($plan->module_documents ?? 1) == 1 ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }}"></i></span>
                                        <span class="plan-label">{{ __('Documents') }}</span>
                                    </li>
                                    <li class="d-flex align-items-center mb-2">
                                        <span class="theme-avtar"><i class="ti {{ ($plan->module_attendance ?? 1) == 1 ? 'ti-circle-check text-success' : 'ti-circle-x text-danger' }}"></i></span>
                                        <span class="plan-label">{{ __('Attendance') }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        @if (\Auth::user()->type == 'super admin')
                        <div class="d-flex align-items-center justify-content-center">
                                <a title="{{ __('Edit') }}" href="#" class="btn btn-info btn-sm align-items-center"
                                    data-url="{{ route('plans.edit', $plan->id) }}" data-ajax-popup="true"
                                    data-title="{{ __('Edit Plan') }}" data-size="lg" data-bs-toggle="tooltip"
                                    data-bs-original-title="{{ __('Edit') }}">
                                    <i class="ti ti-pencil text-white"></i>

                                </a>
                            @if($plan->id != 1)
                            {!! Form::open([
                                                            'method' => 'DELETE',
                                                            'route' => ['plans.destroy', $plan->id],
                                                            'id' => 'delete-form-' . $plan->id,
                                                        ]) !!}
                                                        <a href="#!" class="bs-pass-para btn-icon mx-2 btn btn-danger btn-sm align-items-center" data-bs-toggle="tooltip"
                                                        data-bs-original-title="{{ __('Delete') }}">
                                                            <i class="ti ti-trash"></i>
                                                        </a>
                                                        {!! Form::close() !!}
                            @endif
                        </div>

                        @endif
                                    @if (\Auth::user()->type != 'super admin')
                                    <div class="request-btn">
                                            @if (
                                                $plan->price > 0 &&
                                                    \Auth::user()->trial_plan == 0 &&
                                                    \Auth::user()->plan != $plan->id && $plan->trial == 1)
                                                <a href="{{ route('plan.trial', \Illuminate\Support\Facades\Crypt::encrypt($plan->id)) }}"
                                                    class="btn btn-lg btn-primary btn-icon m-1">{{ __('Start Free Trial') }}</a>
                                            @endif
                                            @if ($plan->id != \Auth::user()->plan)
                                                @if ($plan->price > 0)
                                                    <a href="{{ route('stripe', \Illuminate\Support\Facades\Crypt::encrypt($plan->id)) }}"
                                                        class="btn btn-lg btn-primary btn-icon m-1">{{ __('Buy Plan') }}</a>
                                                @endif
                                            @endif
                                            @if ($plan->id != 1 && $plan->id != \Auth::user()->plan)
                                                @if (\Auth::user()->requested_plan != $plan->id)
                                                    <a href="{{ route('send.request', [\Illuminate\Support\Facades\Crypt::encrypt($plan->id)]) }}"
                                                        class="btn btn-lg btn-primary btn-icon m-1"
                                                        data-title="{{ __('Send Request') }}" data-bs-toggle="tooltip"
                                                        title="{{ __('Send Request') }}">
                                                        <span class="btn-inner--icon"><i class="ti ti-corner-up-right"></i></span>
                                                    </a>
                                                @else
                                                    <a href="{{ route('request.cancel', \Auth::user()->id) }}"
                                                        class="btn btn-lg btn-danger btn-icon m-1"
                                                        data-title="{{ __('`Cancle Request') }}" data-bs-toggle="tooltip"
                                                        title="{{ __('Cancle Request') }}">
                                                        <span class="btn-inner--icon"><i class="ti ti-x"></i></span>
                                                    </a>
                                                @endif
                                            @endif
                                        </div>
                                        @endif

                        @if (\Auth::user()->type == 'company' && \Auth::user()->trial_expire_date)
                            @if (\Auth::user()->type == 'company' && \Auth::user()->trial_plan == $plan->id)
                            <p class="display-total-time mb-0">
                                {{ __('Plan Trial Expired : ') }}
                                {{ !empty(\Auth::user()->trial_expire_date) ? \Auth::user()->dateFormat(\Auth::user()->trial_expire_date) : 'lifetime' }}
                            </p>
                            @endif
                        @else
                            @if (\Auth::user()->type == 'company' && \Auth::user()->plan == $plan->id)
                            <p class="display-total-time mb-0">
                                {{ __('Plan Expired : ') }}
                                {{ !empty(\Auth::user()->plan_expire_date) ? \Auth::user()->dateFormat(\Auth::user()->plan_expire_date) : 'lifetime' }}
                            </p>
                            @endif
                        @endif

                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('script-page')
    <script>
        $(document).on('change', '#trial', function() {
            if ($(this).is(':checked')) {
                $('.plan_div').removeClass('d-none');
                $('#trial_days').attr("required", true);

            } else {
                $('.plan_div').addClass('d-none');
                $('#trial_days').removeAttr("required");
            }
        });
    </script>

    <script>
        $(document).on("click", ".is_disable", function() {

        var id = $(this).attr('data-id');
        var is_disable = ($(this).is(':checked')) ? $(this).val() : 0;

        $.ajax({
            url: '{{ route('plan.disable') }}',
            type: 'POST',
            data: {
                "is_disable": is_disable,
                "id": id,
                "_token": "{{ csrf_token() }}",
            },
            success: function(data) {
                if (data.success) {
                    show_toastr('success', data.success);
                } else {
                    show_toastr('error', data.error);

                }

            }
        });
    });
</script>
@endpush
