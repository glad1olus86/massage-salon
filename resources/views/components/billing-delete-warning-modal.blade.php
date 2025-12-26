{{-- Billing Delete Warning Modal --}}
<div class="modal fade" id="billingDeleteWarningModal" tabindex="-1" aria-labelledby="billingDeleteWarningModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="billingDeleteWarningModalLabel">
                    <i class="ti ti-alert-triangle me-2"></i>
                    {{ __('Delete User') }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-3">
                    <div class="d-flex">
                        <i class="ti ti-info-circle fs-4 me-2 mt-1"></i>
                        <div>
                            <strong>{{ __('Billing Notice') }}</strong>
                            <p class="mb-0 mt-1">
                                {{ __('This user\'s charge is already fixed for the current billing period.') }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body text-center py-3">
                        <p class="text-muted mb-1">{{ __('Charge for this period') }}</p>
                        <h3 class="text-danger mb-0">
                            $<span id="delete-warning-price">0</span>
                        </h3>
                        <small class="text-muted">{{ __('will still be charged') }}</small>
                    </div>
                </div>
                
                <div class="alert alert-info mb-0">
                    <small>
                        <i class="ti ti-calendar me-1"></i>
                        {{ __('Deletion will reduce charges starting from the next billing period.') }}
                    </small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    {{ __('Cancel') }}
                </button>
                <button type="button" class="btn btn-danger" id="delete-warning-confirm-btn">
                    <i class="ti ti-trash me-1"></i>
                    {{ __('Delete Anyway') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
window.BillingDeleteWarningModal = {
    modal: null,
    onConfirm: null,
    
    init: function() {
        this.modal = new bootstrap.Modal(document.getElementById('billingDeleteWarningModal'));
        
        document.getElementById('delete-warning-confirm-btn').addEventListener('click', () => {
            if (this.onConfirm) {
                this.onConfirm();
            }
            this.modal.hide();
        });
    },
    
    show: function(warning, onConfirm) {
        this.onConfirm = onConfirm;
        
        document.getElementById('delete-warning-price').textContent = parseFloat(warning.role_price || 0).toFixed(2);
        
        this.modal.show();
    },
    
    hide: function() {
        this.modal.hide();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('billingDeleteWarningModal')) {
        window.BillingDeleteWarningModal.init();
    }
});
</script>
