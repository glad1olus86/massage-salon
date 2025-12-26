{{-- Refund Modal --}}
<div class="modal fade" id="refundModal" tabindex="-1" aria-labelledby="refundModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="refundModalLabel">{{ __('Refund Money') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="refundForm">
                @csrf
                <input type="hidden" name="transaction_id" id="refundTransactionId">
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="ti ti-info-circle me-1"></i>
                        {{ __('Select a transaction on the diagram to refund money to the sender') }}
                    </div>
                    
                    <div id="refundTransactionInfo" class="mb-3 p-3 bg-light rounded" style="display: none;">
                        <small class="text-muted">{{ __('Refund for transaction:') }}</small>
                        <p class="mb-0 fw-bold" id="refundTransactionName"></p>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Refund Amount') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                            <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                        </div>
                        <small class="text-muted" id="refundMaxAmount"></small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Refund Reason') }} <span class="text-danger">*</span></label>
                        <textarea name="comment" class="form-control" rows="3" required placeholder="{{ __('Specify the reason for the refund...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-warning">{{ __('Refund') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>