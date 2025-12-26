{{ Form::open(['route' => ['room.assignment.update-payment', $assignment->id], 'method' => 'POST']) }}
<div class="modal-body">
    <div class="mb-3">
        <div class="d-flex align-items-center gap-2 mb-2">
            <div class="avatar avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
                {{ strtoupper(substr($assignment->worker->first_name, 0, 1)) }}
            </div>
            <div>
                <strong>{{ $assignment->worker->first_name }} {{ $assignment->worker->last_name }}</strong>
                <br><small class="text-muted">{{ __('Room') }}: {{ $assignment->room->room_number }}</small>
            </div>
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Payment Type') }}</label>
        <div class="form-check">
            <input type="radio" class="form-check-input" name="payment_type" id="payment-agency" 
                   value="agency" {{ $assignment->payment_type === 'agency' ? 'checked' : '' }}>
            <label class="form-check-label" for="payment-agency">
                <i class="ti ti-building text-success me-1"></i>{{ __('Agency pays') }}
            </label>
        </div>
        <div class="form-check">
            <input type="radio" class="form-check-input" name="payment_type" id="payment-worker" 
                   value="worker" {{ $assignment->payment_type === 'worker' ? 'checked' : '' }}>
            <label class="form-check-label" for="payment-worker">
                <i class="ti ti-user text-info me-1"></i>{{ __('Worker pays') }}
            </label>
        </div>
    </div>

    <div class="mb-3" id="payment-amount-section" style="{{ $assignment->payment_type === 'worker' ? '' : 'display: none;' }}">
        <label class="form-label">{{ __('Monthly payment amount') }}</label>
        <div class="input-group">
            <input type="number" class="form-control" name="payment_amount" id="payment-amount" 
                   step="0.01" min="0" placeholder="0.00" 
                   value="{{ $assignment->payment_amount ?? $assignment->room->monthly_price }}">
            <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
        </div>
        <small class="text-muted">{{ __('Room price') }}: {{ formatCashboxCurrency($assignment->room->monthly_price) }}</small>
    </div>

    <div class="mb-3">
        <label class="form-label">{{ __('Comment') }} <small class="text-muted">({{ __('optional') }})</small></label>
        <input type="text" class="form-control" name="comment" placeholder="{{ __('Reason for change...') }}">
    </div>

    {{-- Payment History --}}
    @if($assignment->paymentHistory->count() > 0)
        <div class="mt-4">
            <h6 class="text-muted mb-2">
                <i class="ti ti-history me-1"></i>{{ __('Payment History') }}
            </h6>
            <div class="table-responsive" style="max-height: 150px; overflow-y: auto;">
                <table class="table table-sm table-borderless">
                    <tbody>
                        @foreach($assignment->paymentHistory as $history)
                            <tr class="border-bottom">
                                <td class="small">
                                    {{ $history->created_at->format('d.m.Y H:i') }}
                                </td>
                                <td>
                                    @if($history->payment_type === 'worker')
                                        <span class="badge" style="background-color: #FF0049;">{{ formatCashboxCurrency($history->payment_amount ?? 0) }}</span>
                                    @else
                                        <span class="badge bg-success">{{ __('Agency') }}</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    {{ $history->changed_by_name }}
                                    @if($history->comment)
                                        <br><em>{{ $history->comment }}</em>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
</div>
{{ Form::close() }}

<script>
(function() {
    var paymentAgency = document.getElementById('payment-agency');
    var paymentWorker = document.getElementById('payment-worker');
    var paymentAmountSection = document.getElementById('payment-amount-section');

    function togglePaymentAmount() {
        paymentAmountSection.style.display = paymentWorker.checked ? 'block' : 'none';
    }

    paymentAgency.addEventListener('change', togglePaymentAmount);
    paymentWorker.addEventListener('change', togglePaymentAmount);
})();
</script>
