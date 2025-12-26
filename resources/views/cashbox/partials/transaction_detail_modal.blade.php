{{-- Transaction Detail Modal --}}
<div class="modal fade" id="transactionDetailModal" tabindex="-1" aria-labelledby="transactionDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="transactionDetailModalLabel">{{ __('Transaction Details') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="transactionDetailContent">
                {{-- Content loaded dynamically via JavaScript --}}
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" id="transactionDetailFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- Template for transaction detail content (used by JavaScript) --}}
<template id="transactionDetailTemplate">
    <div class="row">
        <div class="col-6 mb-3">
            <label class="text-muted small d-block">{{ __('Operation Type') }}</label>
            <span class="fw-bold transaction-type"></span>
        </div>
        <div class="col-6 mb-3">
            <label class="text-muted small d-block">{{ __('Status') }}</label>
            <span class="badge transaction-status"></span>
        </div>
    </div>
    
    <div class="transaction-sender mb-3" style="display: none;">
        <label class="text-muted small d-block">{{ __('Sender') }}</label>
        <span class="sender-name"></span>
    </div>
    
    <div class="mb-3">
        <label class="text-muted small d-block">{{ __('Recipient') }}</label>
        <span class="recipient-name"></span>
    </div>
    
    <div class="mb-3">
        <label class="text-muted small d-block">{{ __('Amount') }}</label>
        <span class="fw-bold text-success fs-5 transaction-amount"></span>
    </div>
    
    <div class="transaction-task mb-3" style="display: none;">
        <label class="text-muted small d-block">{{ __('Task') }}</label>
        <span class="task-text"></span>
    </div>
    
    <div class="transaction-comment mb-3" style="display: none;">
        <label class="text-muted small d-block">{{ __('Comment') }}</label>
        <span class="comment-text"></span>
    </div>
    
    <div class="mb-3">
        <label class="text-muted small d-block">{{ __('Date') }}</label>
        <span class="transaction-date"></span>
    </div>
</template>