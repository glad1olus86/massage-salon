{{-- Deposit Modal (only for Boss) --}}
<div class="modal fade" id="depositModal" tabindex="-1" aria-labelledby="depositModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="depositModalLabel">{{ __('Deposit Money') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="depositForm">
                @csrf
                <input type="hidden" name="period_id" value="{{ $period->id }}">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Period') }}</label>
                        <input type="text" class="form-control" value="{{ $period->name }}" disabled>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01"
                                required placeholder="0.00">
                            <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Comment') }}</label>
                        <textarea name="comment" class="form-control" rows="3" placeholder="{{ __('Optional comment...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Deposit') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>