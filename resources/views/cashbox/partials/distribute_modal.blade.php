{{-- Distribute Modal --}}
<div class="modal fade" id="distributeModal" tabindex="-1" aria-labelledby="distributeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="distributeModalLabel">{{ __('Distribute Money') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="distributeForm">
                @csrf
                <input type="hidden" name="period_id" value="{{ $period->id }}">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Distribution Type') }} <span class="text-danger">*</span></label>
                        <select name="distribution_type" id="distributionType" class="form-control" required>
                            <option value="">{{ __('Select distribution type') }}</option>
                            <option value="salary">{{ __('Employee Salary') }}</option>
                            <option value="transfer">{{ __('Fund Transfer') }}</option>
                        </select>
                        <small class="text-muted" id="distributionTypeHint"></small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Recipient') }} <span class="text-danger">*</span></label>
                        <select name="recipient" class="form-control" required>
                            <option value="">{{ __('Select recipient') }}</option>
                            @foreach($recipients as $recipient)
                                @if(!isset($recipient['is_self']))
                                    <option value="{{ $recipient['type'] === 'App\\Models\\Worker' ? 'worker' : 'user' }}_{{ $recipient['id'] }}" data-role="{{ $recipient['role'] }}">
                                        {{ $recipient['name'] }} 
                                        @if($recipient['role'] === 'manager')
                                            ({{ __('Manager') }})
                                        @elseif($recipient['role'] === 'curator')
                                            ({{ __('Curator') }})
                                        @else
                                            ({{ __('Worker') }})
                                        @endif
                                    </option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" name="amount" class="form-control" step="0.01" min="0.01" required placeholder="0.00">
                            <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                        </div>
                        <small class="text-muted">
                            {{ __('Available:') }} 
                            <span id="availableBalance">{{ formatCashboxCurrency($balance['received'] - $balance['sent']) }}</span>
                        </small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label">{{ __('Task') }}</label>
                        <input type="text" name="task" class="form-control" placeholder="{{ __('Task description...') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">{{ __('Comment') }}</label>
                        <textarea name="comment" class="form-control" rows="2" placeholder="{{ __('Additional information...') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Distribute') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const distributionTypeSelect = document.getElementById('distributionType');
    const distributionTypeHint = document.getElementById('distributionTypeHint');
    
    if (distributionTypeSelect) {
        distributionTypeSelect.addEventListener('change', function() {
            const value = this.value;
            if (value === 'salary') {
                distributionTypeHint.textContent = '{{ __("Final salary payment. Transaction will be completed immediately.") }}';
                distributionTypeHint.className = 'text-muted';
            } else if (value === 'transfer') {
                distributionTypeHint.textContent = '{{ __("Money transfer for further distribution to other employees.") }}';
                distributionTypeHint.className = 'text-muted';
            } else {
                distributionTypeHint.textContent = '';
            }
        });
    }
});
</script>