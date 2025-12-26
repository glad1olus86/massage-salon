<div class="mb-3 px-2">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <span class="text-muted">
                {{ __('Room') }}: <strong>{{ $room->room_number }}</strong> | 
                {{ __('Occupied') }}: <strong>{{ $room->currentAssignments->count() }}</strong> {{ __('of') }} {{ $room->capacity }}
            </span>
        </div>
        @if (!$room->isFull())
            <a href="#" data-url="{{ route('room.assign.form', $room->id) }}" data-ajax-popup="true"
                data-title="{{ __('Check In Workers') }}" data-size="lg" class="btn btn-sm btn-primary">
                <i class="ti ti-plus"></i> {{ __('Check In') }}
            </a>
        @endif
    </div>
    <div class="d-flex gap-3 flex-wrap">
        <span class="badge bg-secondary">
            <i class="ti ti-coin me-1"></i>{{ __('Room price') }}: {{ formatCashboxCurrency($room->monthly_price) }}
        </span>
    </div>
</div>

@if($room->currentAssignments->count() > 0)
    {{-- Bulk Actions Panel --}}
    <div id="room-bulk-actions" class="mb-3 p-2 bg-light rounded" style="display: none;">
        <div class="d-flex align-items-center gap-2">
            <span class="small"><strong id="room-selected-count">0</strong> {{ __('selected') }}</span>
            <button type="button" class="btn btn-sm btn-danger" id="room-bulk-checkout-btn">
                <i class="ti ti-door-exit me-1"></i>{{ __('Check Out Selected') }}
            </button>
        </div>
    </div>

    {{ Form::open(['route' => ['room.checkout.bulk', $room->id], 'method' => 'POST', 'id' => 'room-bulk-checkout-form']) }}
    <input type="hidden" name="worker_ids" id="room-checkout-worker-ids">
    {{ Form::close() }}
@endif

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                @if($room->currentAssignments->count() > 0)
                    <th style="width: 40px;">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="room-select-all">
                        </div>
                    </th>
                @endif
                <th>{{ __('Full Name') }}</th>
                <th>{{ __('Gender') }}</th>
                <th>{{ __('Payment') }}</th>
                <th>{{ __('Check-in Date') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($room->currentAssignments as $assignment)
                <tr>
                    <td>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input room-resident-checkbox" 
                                value="{{ $assignment->worker->id }}" 
                                data-name="{{ $assignment->worker->first_name }} {{ $assignment->worker->last_name }}">
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('worker.show', $assignment->worker->id) }}" target="_blank" class="text-primary fw-medium">
                            {{ $assignment->worker->first_name }} {{ $assignment->worker->last_name }}
                        </a>
                    </td>
                    <td>
                        @if($assignment->worker->gender == 'male')
                            <span class="badge bg-primary">{{ __('Male') }}</span>
                        @else
                            <span class="badge bg-danger">{{ __('Female') }}</span>
                        @endif
                    </td>
                    <td>
                        @if($assignment->payment_type === 'worker')
                            <span class="badge" style="background-color: #FF0049;" data-bs-toggle="tooltip" title="{{ __('Worker pays for accommodation') }}">
                                {{ formatCashboxCurrency($assignment->payment_amount ?? 0) }}
                            </span>
                        @else
                            <span class="badge bg-success" data-bs-toggle="tooltip" title="{{ __('Agency pays for accommodation') }}">
                                {{ __('Agency') }}
                            </span>
                        @endif
                        <a href="#" class="btn btn-sm btn-link p-0 ms-1" 
                           data-url="{{ route('room.assignment.edit-payment', $assignment->id) }}"
                           data-ajax-popup="true" 
                           data-title="{{ __('Edit Payment') }}"
                           data-size="md">
                            <i class="ti ti-pencil"></i>
                        </a>
                    </td>
                    <td>{{ \Auth::user()->dateFormat($assignment->check_in_date) }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('worker.show', $assignment->worker->id) }}" target="_blank"
                                class="btn btn-sm btn-info d-flex align-items-center" data-bs-toggle="tooltip" title="{{ __('Profile') }}">
                                <i class="ti ti-user text-white"></i>
                            </a>
                            {!! Form::open([
                                'method' => 'POST',
                                'route' => ['worker.unassign.room', $assignment->worker->id],
                                'id' => 'unassign-form-' . $assignment->worker->id,
                                'class' => 'd-inline'
                            ]) !!}
                            <a href="#" class="btn btn-sm btn-danger d-flex align-items-center gap-1 bs-pass-para"
                                data-bs-toggle="tooltip" title="{{ __('Check Out') }}"
                                data-confirm="{{ __('Are you sure?') . '|' . __('This action will check out the worker from the room.') }}"
                                data-confirm-yes="document.getElementById('unassign-form-{{ $assignment->worker->id }}').submit();">
                                <i class="ti ti-door-exit text-white"></i>
                                <span class="text-white">{{ __('Check Out') }}</span>
                            </a>
                            {!! Form::close() !!}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="ti ti-home-off" style="font-size: 24px;"></i><br>
                        {{ __('No one lives in this room') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($room->currentAssignments->count() > 0)
<script>
(function() {
    var selectAllCheckbox = document.getElementById('room-select-all');
    var checkboxes = document.querySelectorAll('.room-resident-checkbox');
    var bulkActionsPanel = document.getElementById('room-bulk-actions');
    var selectedCountEl = document.getElementById('room-selected-count');
    var bulkCheckoutBtn = document.getElementById('room-bulk-checkout-btn');
    var workerIdsInput = document.getElementById('room-checkout-worker-ids');
    var bulkForm = document.getElementById('room-bulk-checkout-form');

    function getSelectedWorkers() {
        var selected = [];
        checkboxes.forEach(function(cb) {
            if (cb.checked) {
                selected.push(cb.value);
            }
        });
        return selected;
    }

    function updateUI() {
        var selected = getSelectedWorkers();
        selectedCountEl.textContent = selected.length;
        bulkActionsPanel.style.display = selected.length > 0 ? 'block' : 'none';
        workerIdsInput.value = selected.join(',');
    }

    // Select all
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            checkboxes.forEach(function(cb) {
                cb.checked = selectAllCheckbox.checked;
            });
            updateUI();
        });
    }

    // Individual checkboxes
    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            updateUI();
            // Update select-all state
            var allChecked = Array.from(checkboxes).every(function(c) { return c.checked; });
            if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
        });
    });

    // Bulk checkout button
    if (bulkCheckoutBtn) {
        bulkCheckoutBtn.addEventListener('click', function() {
            var selected = getSelectedWorkers();
            if (selected.length === 0) return;
            
            if (confirm('{{ __("Are you sure you want to check out") }} ' + selected.length + ' {{ __("workers?") }}')) {
                bulkForm.submit();
            }
        });
    }
})();
</script>
@endif
