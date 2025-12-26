@extends('layouts.admin')

@section('page-title')
    {{ __('Billing') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item">
        <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
    </li>
    <li class="breadcrumb-item active">{{ __('Billing') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="{{ route('billing.history') }}" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" title="{{ __('Billing History') }}">
            <i class="ti ti-history"></i>
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    {{-- Current Period Card --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="ti ti-calendar-stats me-2"></i>
                    {{ __('Current Billing Period') }}
                </h5>
                {{-- Spots remaining badge --}}
                <div>
                    @if($billing['spots_remaining'] > 0)
                        <span class="badge bg-success fs-6 px-3 py-2">
                            <i class="ti ti-users me-1"></i>
                            {{ $billing['spots_remaining'] }} {{ __('spots remaining') }}
                        </span>
                    @else
                        <span class="badge bg-warning fs-6 px-3 py-2">
                            <i class="ti ti-alert-circle me-1"></i>
                            {{ __('Limit reached') }}
                        </span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h6 class="text-muted mb-1">{{ __('Period') }}</h6>
                        <h4 class="mb-0">{{ $billing['period_start'] }} - {{ $billing['period_end'] }}</h4>
                    </div>
                    <div class="text-end">
                        <h6 class="text-muted mb-1">{{ __('Plan') }}</h6>
                        <h4 class="mb-0">{{ $billing['plan_name'] }}</h4>
                    </div>
                </div>

                <hr>

                {{-- User Usage Summary --}}
                <div class="row mb-4">
                    <div class="col-md-4 text-center">
                        <div class="border rounded p-3">
                            <h2 class="text-primary mb-1">{{ $billing['total_current'] }}/{{ $billing['base_limit'] }}</h2>
                            <small class="text-muted">{{ __('Users Used') }}</small>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="border rounded p-3">
                            <h2 class="text-info mb-1">{{ $billing['managers']['current'] }}</h2>
                            <small class="text-muted">{{ __('Managers') }}</small>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div class="border rounded p-3">
                            <h2 class="text-success mb-1">{{ $billing['curators']['current'] }}</h2>
                            <small class="text-muted">{{ __('Curators') }}</small>
                        </div>
                    </div>
                </div>

                {{-- Over limit info --}}
                @if($billing['total_over_limit'] > 0)
                <div class="alert alert-warning">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-triangle fs-4 me-2"></i>
                        <div>
                            <strong>{{ $billing['total_over_limit'] }} {{ __('users over limit') }}</strong>
                            <p class="mb-0 small">
                                {{ __('Max used this period') }}: {{ $billing['total_max_used'] }} 
                                ({{ $billing['managers']['max_used'] }} {{ __('managers') }}, 
                                {{ $billing['curators']['max_used'] }} {{ __('curators') }})
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Cost breakdown --}}
                @php $cs = $billing['currency_symbol'] ?? '$'; @endphp
                @if($billing['managers']['over_limit'] > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ __('Additional Managers') }} ({{ $billing['managers']['over_limit'] }} × {{ $cs }}{{ number_format($billing['managers']['price_per_user'], 2) }})</span>
                    <strong class="text-warning">+{{ $cs }}{{ number_format($billing['managers']['additional_cost'], 2) }}</strong>
                </div>
                @endif
                
                @if($billing['curators']['over_limit'] > 0)
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ __('Additional Curators') }} ({{ $billing['curators']['over_limit'] }} × {{ $cs }}{{ number_format($billing['curators']['price_per_user'], 2) }})</span>
                    <strong class="text-info">+{{ $cs }}{{ number_format($billing['curators']['additional_cost'], 2) }}</strong>
                </div>
                @endif
                @endif

                <hr>

                {{-- Anti-abuse Warning --}}
                <div class="alert alert-secondary mb-0">
                    <div class="d-flex">
                        <i class="ti ti-info-circle fs-4 me-2"></i>
                        <div>
                            <strong>{{ __('Important') }}</strong>
                            <p class="mb-0 small">
                                {{ __('Charges are based on maximum users used during the period. Deleting users will not reduce current period charges.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Card --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ti ti-receipt me-2"></i>
                    {{ __('Billing Summary') }}
                </h5>
            </div>
            <div class="card-body">
                @php $cs = $billing['currency_symbol'] ?? '$'; @endphp
                <div class="d-flex justify-content-between mb-3">
                    <span>{{ __('Base Plan') }}</span>
                    <strong>{{ $cs }}{{ number_format($billing['base_price'], 2) }}</strong>
                </div>
                
                @if($billing['total_additional'] > 0)
                <div class="d-flex justify-content-between mb-3 text-warning">
                    <span>{{ __('Additional Users') }}</span>
                    <strong>+{{ $cs }}{{ number_format($billing['total_additional'], 2) }}</strong>
                </div>
                @endif
                
                <hr>
                
                <div class="d-flex justify-content-between">
                    <h5 class="mb-0">{{ __('Total') }}</h5>
                    <h4 class="mb-0 text-primary">{{ $cs }}{{ number_format($billing['total_charge'], 2) }}</h4>
                </div>
                
                <div class="text-center mt-3">
                    <span class="badge bg-{{ $billing['status'] == 'active' ? 'success' : 'warning' }} px-3 py-2">
                        {{ ucfirst($billing['status']) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Pricing Info Card --}}
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ti ti-currency-dollar me-2"></i>
                    {{ __('Pricing') }}
                </h5>
            </div>
            <div class="card-body">
                @php $cs = $billing['currency_symbol'] ?? '$'; @endphp
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ __('Base users included') }}</span>
                    <strong>{{ $billing['base_limit'] }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>{{ __('Manager (over limit)') }}</span>
                    <strong>{{ $cs }}{{ number_format($billing['managers']['price_per_user'], 2) }}/{{ __('month') }}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>{{ __('Curator (over limit)') }}</span>
                    <strong>{{ $cs }}{{ number_format($billing['curators']['price_per_user'], 2) }}/{{ __('month') }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Billing History --}}
@if($history->count() > 0)
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="ti ti-history me-2"></i>
                    {{ __('Billing History') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Period') }}</th>
                                <th>{{ __('Users') }}</th>
                                <th>{{ __('Base') }}</th>
                                <th>{{ __('Additional') }}</th>
                                <th>{{ __('Total') }}</th>
                                <th>{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $period)
                            <tr>
                                <td>
                                    {{ $period->period_start->format('d.m.Y') }} - 
                                    {{ $period->period_end->format('d.m.Y') }}
                                </td>
                                <td>
                                    {{ $period->max_managers_used + $period->max_curators_used }}
                                    <small class="text-muted">({{ $period->max_managers_used }}M + {{ $period->max_curators_used }}C)</small>
                                </td>
                                <td>${{ number_format($period->base_amount, 2) }}</td>
                                <td>${{ number_format($period->additional_amount, 2) }}</td>
                                <td><strong>${{ number_format($period->total_amount, 2) }}</strong></td>
                                <td>
                                    <span class="badge bg-{{ $period->status == 'paid' ? 'success' : ($period->status == 'failed' ? 'danger' : 'warning') }}">
                                        {{ ucfirst($period->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
