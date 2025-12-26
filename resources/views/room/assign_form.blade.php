@php
    $unassignedWorkers = \App\Models\Worker::whereDoesntHave('currentAssignment')
        ->where('created_by', \Auth::user()->creatorId())
        ->get();
    $availableSpots = $room->availableSpots();
@endphp

{{ Form::open(['route' => ['room.assign.workers.bulk', $room->id], 'method' => 'POST', 'id' => 'assign-workers-form']) }}
@if(request('redirect_to'))
    <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
@endif
<div class="modal-body">
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">
                {{ __('Room') }}: <strong>{{ $room->room_number }}</strong> | 
                {{ __('Available spots') }}: <strong id="available-spots">{{ $availableSpots }}</strong> {{ __('of') }} {{ $room->capacity }}
            </span>
            <span class="badge bg-primary" id="selected-count-badge">{{ __('Selected') }}: 0</span>
        </div>
    </div>

    {{-- Payment Settings --}}
    <div class="mb-3 p-3 bg-light rounded">
        <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" id="worker-pays-checkbox" name="worker_pays" value="1">
            <label class="form-check-label" for="worker-pays-checkbox">
                <strong>{{ __('Worker pays for accommodation') }}</strong>
            </label>
        </div>
        <div id="payment-amount-wrapper" style="display: none;">
            <label class="form-label">{{ __('Monthly payment amount') }}</label>
            <div class="input-group">
                <input type="number" class="form-control" name="payment_amount" id="payment-amount-input" 
                       step="0.01" min="0" placeholder="0.00" value="{{ $room->monthly_price }}">
                <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
            </div>
            <small class="text-muted">{{ __('Room price') }}: {{ formatCashboxCurrency($room->monthly_price) }}</small>
        </div>
    </div>
    
    <div class="mb-3">
        <input type="text" class="form-control" id="worker-search" placeholder="{{ __('Search by name...') }}">
    </div>
    
    <div class="mb-2">
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary filter-gender active" data-gender="all">{{ __('All') }}</button>
            <button type="button" class="btn btn-outline-primary filter-gender" data-gender="male">{{ __('Men') }}</button>
            <button type="button" class="btn btn-outline-danger filter-gender" data-gender="female">{{ __('Women') }}</button>
        </div>
    </div>

    <input type="hidden" name="worker_ids" id="worker-ids-input">
    
    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
        <table class="table table-hover" id="workers-select-table">
            <thead class="sticky-top bg-white">
                <tr>
                    <th style="width: 40px;"></th>
                    <th>{{ __('Full Name') }}</th>
                    <th>{{ __('Gender') }}</th>
                    <th>{{ __('Nationality') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($unassignedWorkers as $worker)
                    <tr class="worker-row" data-gender="{{ $worker->gender }}" data-name="{{ strtolower($worker->first_name . ' ' . $worker->last_name) }}">
                        <td>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input worker-select-checkbox" 
                                    value="{{ $worker->id }}" 
                                    data-name="{{ $worker->first_name }} {{ $worker->last_name }}">
                            </div>
                        </td>
                        <td>{{ $worker->first_name }} {{ $worker->last_name }}</td>
                        <td>
                            @if($worker->gender == 'male')
                                <span class="badge bg-primary">{{ __('M') }}</span>
                            @else
                                <span class="badge bg-danger">{{ __('F') }}</span>
                            @endif
                        </td>
                        <td>{{ $worker->nationality }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">{{ __('No available workers for check-in') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn btn-primary" id="assign-submit-btn" disabled>{{ __('Check In') }}</button>
</div>
{{ Form::close() }}

<script>
(function() {
    var maxSelectable = {{ $availableSpots }};
    var selectedWorkers = [];
    var checkboxes = document.querySelectorAll('.worker-select-checkbox');
    var submitBtn = document.getElementById('assign-submit-btn');
    var workerIdsInput = document.getElementById('worker-ids-input');
    var selectedCountBadge = document.getElementById('selected-count-badge');
    var availableSpotsEl = document.getElementById('available-spots');
    var searchInput = document.getElementById('worker-search');
    var filterButtons = document.querySelectorAll('.filter-gender');
    var currentGenderFilter = 'all';

    function updateUI() {
        selectedCountBadge.textContent = '{{ __("Selected") }}: ' + selectedWorkers.length + ' / ' + maxSelectable;
        workerIdsInput.value = selectedWorkers.join(',');
        submitBtn.disabled = selectedWorkers.length === 0;
        
        // Update checkbox states
        checkboxes.forEach(function(cb) {
            var row = cb.closest('tr');
            if (row.style.display !== 'none') {
                if (selectedWorkers.length >= maxSelectable && !cb.checked) {
                    cb.disabled = true;
                    row.classList.add('text-muted');
                } else {
                    cb.disabled = false;
                    row.classList.remove('text-muted');
                }
            }
        });
    }

    function filterRows() {
        var searchTerm = searchInput.value.toLowerCase();
        var rows = document.querySelectorAll('.worker-row');
        
        rows.forEach(function(row) {
            var name = row.dataset.name;
            var gender = row.dataset.gender;
            
            var matchesSearch = name.indexOf(searchTerm) !== -1;
            var matchesGender = currentGenderFilter === 'all' || gender === currentGenderFilter;
            
            row.style.display = (matchesSearch && matchesGender) ? '' : 'none';
        });
        
        updateUI();
    }

    // Checkbox change handler
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            var workerId = this.value;
            
            if (this.checked) {
                if (selectedWorkers.length >= maxSelectable) {
                    // Remove first selected worker
                    var firstWorkerId = selectedWorkers.shift();
                    var firstCheckbox = document.querySelector('.worker-select-checkbox[value="' + firstWorkerId + '"]');
                    if (firstCheckbox) firstCheckbox.checked = false;
                }
                selectedWorkers.push(workerId);
            } else {
                var index = selectedWorkers.indexOf(workerId);
                if (index > -1) selectedWorkers.splice(index, 1);
            }
            
            updateUI();
        });
    });

    // Search handler
    searchInput.addEventListener('input', filterRows);

    // Gender filter handler
    filterButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterButtons.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            currentGenderFilter = this.dataset.gender;
            filterRows();
        });
    });

    updateUI();

    // Payment checkbox toggle
    var workerPaysCheckbox = document.getElementById('worker-pays-checkbox');
    var paymentAmountWrapper = document.getElementById('payment-amount-wrapper');
    
    workerPaysCheckbox.addEventListener('change', function() {
        paymentAmountWrapper.style.display = this.checked ? 'block' : 'none';
    });
})();
</script>
