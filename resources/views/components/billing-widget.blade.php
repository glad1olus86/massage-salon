{{-- Billing Widget for Dashboard --}}
@php
    $billingService = new \App\Services\UserBillingService();
    $billing = $billingService->getBillingBreakdown(Auth::user()->creatorId());
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="ti ti-receipt text-primary me-2"></i>
            {{ __('Billing') }}
        </h5>
        <a href="{{ route('billing.index') }}" class="btn btn-sm btn-primary-subtle">
            {{ __('View Details') }}
        </a>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-muted">{{ __('Current Period') }}</span>
            <span class="badge bg-{{ $billing['status'] == 'active' ? 'success' : 'warning' }}">
                {{ ucfirst($billing['status']) }}
            </span>
        </div>
        
        {{-- Managers --}}
        <div class="mb-3">
            <div class="d-flex justify-content-between small mb-1">
                <span>{{ __('Managers') }}</span>
                <span>{{ $billing['managers']['current'] }}/{{ $billing['base_limit'] }}</span>
            </div>
            <div class="progress" style="height: 6px;">
                @php
                    $mPercent = $billing['base_limit'] > 0 ? min(100, ($billing['managers']['current'] / $billing['base_limit']) * 100) : 0;
                @endphp
                <div class="progress-bar bg-primary" style="width: {{ $mPercent }}%"></div>
            </div>
            @if($billing['managers']['over_limit'] > 0)
                <small class="text-warning">+{{ $billing['managers']['over_limit'] }} {{ __('over limit') }}</small>
            @endif
        </div>
        
        {{-- Curators --}}
        <div class="mb-3">
            <div class="d-flex justify-content-between small mb-1">
                <span>{{ __('Curators') }}</span>
                <span>{{ $billing['curators']['current'] }}/{{ $billing['base_limit'] }}</span>
            </div>
            <div class="progress" style="height: 6px;">
                @php
                    $cPercent = $billing['base_limit'] > 0 ? min(100, ($billing['curators']['current'] / $billing['base_limit']) * 100) : 0;
                @endphp
                <div class="progress-bar bg-info" style="width: {{ $cPercent }}%"></div>
            </div>
            @if($billing['curators']['over_limit'] > 0)
                <small class="text-warning">+{{ $billing['curators']['over_limit'] }} {{ __('over limit') }}</small>
            @endif
        </div>
        
        <hr>
        
        <div class="d-flex justify-content-between">
            <span>{{ __('Projected Total') }}</span>
            <strong class="text-primary">${{ number_format($billing['total_charge'], 2) }}</strong>
        </div>
    </div>
</div>
