@extends('layouts.admin')

@section('page-title')
    {{ __('Cashbox') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Cashbox') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        @can('cashbox_view_audit')
            <a href="{{ route('cashbox.audit') }}" class="btn btn-sm btn-secondary me-2">
                <i class="ti ti-history"></i> {{ __('Audit') }}
            </a>
        @endcan
        @if($canDeposit)
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#depositModal">
                <i class="ti ti-plus"></i> {{ __('Deposit') }}
            </button>
        @endif
    </div>
@endsection

@section('content')
    <div class="row">
        @forelse($periods as $period)
            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6">
                <a href="{{ route('cashbox.show', $period->id) }}" class="text-decoration-none">
                    <div class="card {{ $period->id === $currentPeriod->id ? 'border-primary' : '' }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="mb-0">{{ $period->name }}</h5>
                                @if($period->is_frozen)
                                    <span class="badge bg-secondary">
                                        <i class="ti ti-lock me-1"></i>{{ __('Frozen') }}
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="ti ti-circle-check me-1"></i>{{ __('Active') }}
                                    </span>
                                @endif
                            </div>
                            
                            @if($canViewTotalDeposited)
                            <div class="d-flex align-items-center">
                                <div class="theme-avtar bg-primary">
                                    <i class="ti ti-cash"></i>
                                </div>
                                <div class="ms-3">
                                    <small class="text-muted">{{ __('Deposited') }}</small>
                                    <h6 class="mb-0">{{ formatCashboxCurrency($period->total_deposited) }}</h6>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="ti ti-cash" style="font-size: 48px; color: #ccc;"></i>
                        </div>
                        <h5 class="text-muted">{{ __('No cashbox periods') }}</h5>
                        <p class="text-muted mb-0">{{ __('Periods are created automatically when depositing money') }}</p>
                    </div>
                </div>
            </div>
        @endforelse
    </div>


    {{-- Deposit Modal --}}
    @if($canDeposit)
        <div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="depositModalLabel">{{ __('Deposit Money') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="depositForm">
                        @csrf
                        <input type="hidden" name="period_id" value="{{ $currentPeriod->id }}">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Period') }}</label>
                                <input type="text" class="form-control" value="{{ $currentPeriod->name }}" disabled>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                </div>
                            </div>
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Comment') }}</label>
                                <textarea name="comment" class="form-control" rows="3" placeholder="{{ __('Optional comment...') }}"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary" id="depositSubmitBtn">{{ __('Deposit') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var depositForm = document.getElementById('depositForm');
    if (depositForm) {
        depositForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var submitBtn = document.getElementById('depositSubmitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>{{ __("Loading...") }}';
            
            var formData = new FormData(depositForm);
            
            fetch('{{ route("cashbox.deposit") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    show_toastr('success', data.message);
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    show_toastr('error', data.error || '{{ __("An error occurred") }}');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '{{ __("Deposit") }}';
                }
            })
            .catch(error => {
                show_toastr('error', '{{ __("An error occurred") }}');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '{{ __("Deposit") }}';
            });
        });
    }
});
</script>
@endpush