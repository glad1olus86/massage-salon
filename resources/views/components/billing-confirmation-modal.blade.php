{{-- Billing Confirmation Modal --}}
<div class="modal fade" id="billingConfirmationModal" tabindex="-1" aria-labelledby="billingConfirmationModalLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="billingConfirmationModalLabel">
                    <i class="ti ti-currency-dollar text-warning me-2"></i>
                    {{ __('Additional Subscription Cost') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-info-circle fs-4 me-2"></i>
                        <div>
                            <strong>{{ __('Adding this') }} <span id="billing-role-name"></span> {{ __('will increase your subscription.') }}</strong>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body text-center py-4">
                        <h2 class="text-primary mb-1">
                            +$<span id="billing-role-price">0</span>
                            <small class="text-muted fs-6">/{{ __('month') }}</small>
                        </h2>
                        <p class="text-muted mb-0">{{ __('Additional cost per user') }}</p>
                    </div>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Current') }} <span id="billing-role-name-2"></span>:</span>
                        <strong><span id="billing-current-count">0</span></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>{{ __('Base limit') }}:</span>
                        <strong><span id="billing-base-limit">0</span></strong>
                    </div>
                </div>
                
                <div class="alert alert-info mb-0">
                    <small>
                        <i class="ti ti-info-circle me-1"></i>
                        {{ __('This charge will be added to your subscription at the end of the billing period.') }}
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-primary" id="billing-confirm-btn">
                    <i class="ti ti-check me-1"></i>
                    {{ __('Confirm & Create User') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.BillingConfirmationModal = {
    modal: null,
    onConfirm: null,
    
    init: function() {
        this.modal = new bootstrap.Modal(document.getElementById('billingConfirmationModal'));
        
        document.getElementById('billing-confirm-btn').addEventListener('click', () => {
            if (this.onConfirm) {
                this.onConfirm();
            }
            this.modal.hide();
        });
    },
    
    show: function(limitInfo, onConfirm) {
        this.onConfirm = onConfirm;
        
        // Update modal content
        document.getElementById('billing-role-name').textContent = limitInfo.role || '';
        document.getElementById('billing-role-name-2').textContent = limitInfo.role || '';
        document.getElementById('billing-role-price').textContent = parseFloat(limitInfo.role_price || 0).toFixed(2);
        document.getElementById('billing-current-count').textContent = limitInfo.current_count || 0;
        document.getElementById('billing-base-limit').textContent = limitInfo.base_limit || 0;
        
        this.modal.show();
    },
    
    hide: function() {
        this.modal.hide();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('billingConfirmationModal')) {
        window.BillingConfirmationModal.init();
    }
});
</script>
