@extends('layouts.admin')

@section('page-title')
    {{ __('Cashbox Settings') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cashbox.index') }}">{{ __('Cashbox') }}</a></li>
    <li class="breadcrumb-item">{{ __('Settings') }}</li>
@endsection

@section('content')
    <div class="row">
        {{-- Currency Settings --}}
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="ti ti-currency-euro me-2"></i>{{ __('Currency') }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('cashbox.settings.save') }}" method="POST">
                        @csrf
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Cashbox Currency') }}</label>
                            <select name="cashbox_currency" class="form-control">
                                <option value="EUR" {{ $currentCurrency === 'EUR' ? 'selected' : '' }}>€ EUR (Euro)</option>
                                <option value="USD" {{ $currentCurrency === 'USD' ? 'selected' : '' }}>$ USD (US Dollar)</option>
                                <option value="PLN" {{ $currentCurrency === 'PLN' ? 'selected' : '' }}>zł PLN (Polish Zloty)</option>
                                <option value="CZK" {{ $currentCurrency === 'CZK' ? 'selected' : '' }}>Kč CZK (Czech Koruna)</option>
                            </select>
                            <small class="text-muted">{{ __('Select currency for displaying amounts in cashbox') }}</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i>{{ __('Save') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Debug Tools (only for Boss) --}}
        @if($isBoss)
        <div class="col-lg-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="ti ti-bug me-2"></i>{{ __('Debug Tools') }}</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-1"></i>
                        {{ __('Warning! These actions are irreversible and intended for testing only.') }}
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">{{ __('Current Period') }}</label>
                        <p class="mb-2">
                            <strong>{{ $currentPeriod->name }}</strong>
                            <br>
                            <small class="text-muted">
                                {{ __('Deposited:') }} {{ number_format($currentPeriod->total_deposited, 2, ',', ' ') }} {{ $currentCurrency }}
                            </small>
                        </p>
                    </div>

                    <form action="{{ route('cashbox.reset-period') }}" method="POST" 
                          onsubmit="return confirm('{{ __('Are you sure? All transactions for the current month will be deleted!') }}');">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="ti ti-trash me-1"></i>{{ __('Reset Current Month') }}
                        </button>
                    </form>
                    <small class="text-muted d-block mt-2">
                        {{ __('Deletes all transactions and resets the balance for the current period') }}
                    </small>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection