@php
    $currentEmployees = $position->currentAssignments;
    $unassignedWorkers = \App\Models\Worker::whereDoesntHave('currentWorkAssignment')
        ->where('created_by', \Auth::user()->creatorId())
        ->get();
@endphp

<div class="mb-3 px-2 d-flex justify-content-between align-items-center">
    <div>
        <span class="text-muted">
            {{ __('Position') }}: <strong>{{ $position->name }}</strong> | 
            {{ __('Work Place') }}: <strong>{{ $position->workPlace->name }}</strong> |
            {{ __('Employees') }}: <strong>{{ $currentEmployees->count() }}</strong>
        </span>
    </div>
    <button type="button" class="btn btn-sm btn-primary" id="show-assign-form-btn">
        <i class="ti ti-plus"></i> {{ __('Assign') }}
    </button>
</div>

{{-- Assign Form (hidden by default) --}}
<div id="assign-form-section" style="display: none;" class="mb-3 p-3 bg-light rounded">
    {{ Form::open(['route' => ['positions.assign.workers', $position->id], 'method' => 'POST', 'id' => 'position-assign-form']) }}
    <input type="hidden" name="worker_ids" id="assign-worker-ids">
    
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <strong>{{ __('Select workers to assign') }}</strong>
        <span class="badge bg-primary" id="assign-selected-count">{{ __('Selected') }}: 0</span>
    </div>
    
    <div class="mb-2">
        <input type="text" class="form-control form-control-sm" id="assign-worker-search" placeholder="{{ __('Search by name...') }}">
    </div>
    
    <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
        <table class="table table-sm table-hover" id="assign-workers-table">
            <tbody>
                @forelse ($unassignedWorkers as $worker)
                    <tr class="assign-worker-row" data-name="{{ strtolower($worker->first_name . ' ' . $worker->last_name) }}">
                        <td style="width: 30px;">
                            <input type="checkbox" class="form-check-input assign-worker-checkbox" value="{{ $worker->id }}">
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
                        <td colspan="4" class="text-center text-muted py-2">
                            {{ __('No available workers') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-2 d-flex gap-2">
        <button type="button" class="btn btn-sm btn-secondary" id="cancel-assign-btn">{{ __('Cancel') }}</button>
        <button type="submit" class="btn btn-sm btn-success" id="assign-submit-btn" disabled>
            <i class="ti ti-briefcase me-1"></i>{{ __('Assign to Work') }}
        </button>
    </div>
    {{ Form::close() }}
</div>

@if($currentEmployees->count() > 0)
    {{-- Bulk Actions Panel --}}
    <div id="position-bulk-actions" class="mb-3 p-2 bg-light rounded" style="display: none;">
        <div class="d-flex align-items-center gap-2">
            <span class="small"><strong id="position-selected-count">0</strong> {{ __('selected') }}</span>
            <button type="button" class="btn btn-sm btn-warning" id="position-bulk-dismiss-btn">
                <i class="ti ti-user-off me-1"></i>{{ __('Dismiss Selected') }}
            </button>
        </div>
    </div>

    {{ Form::open(['route' => ['positions.dismiss.workers', $position->id], 'method' => 'POST', 'id' => 'position-bulk-dismiss-form']) }}
    <input type="hidden" name="worker_ids" id="position-dismiss-worker-ids">
    {{ Form::close() }}
@endif

<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                @if($currentEmployees->count() > 0)
                    <th style="width: 40px;">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="position-select-all">
                        </div>
                    </th>
                @endif
                <th>{{ __('Full Name') }}</th>
                <th>{{ __('Gender') }}</th>
                <th>{{ __('Employment Date') }}</th>
                <th>{{ __('Action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($currentEmployees as $assignment)
                <tr>
                    <td>
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input position-employee-checkbox" 
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
                    <td>{{ \Auth::user()->dateFormat($assignment->started_at) }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <a href="{{ route('worker.show', $assignment->worker->id) }}" target="_blank"
                                class="btn btn-sm btn-info d-flex align-items-center" data-bs-toggle="tooltip" title="{{ __('Profile') }}">
                                <i class="ti ti-user text-white"></i>
                            </a>
                            {{ Form::open(['route' => ['positions.dismiss.workers', $position->id], 'method' => 'POST', 'class' => 'd-inline']) }}
                            <input type="hidden" name="worker_ids" value="{{ $assignment->worker->id }}">
                            <button type="submit" class="btn btn-sm btn-warning d-flex align-items-center gap-1"
                                onclick="return confirm('{{ __('Dismiss this worker?') }}')"
                                data-bs-toggle="tooltip" title="{{ __('Dismiss') }}">
                                <i class="ti ti-user-off text-white"></i>
                                <span class="text-white">{{ __('Dismiss') }}</span>
                            </button>
                            {{ Form::close() }}
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        <i class="ti ti-briefcase-off" style="font-size: 24px;"></i><br>
                        {{ __('No one works in this position') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<script>
(function() {
    // Show/hide assign form
    var showAssignBtn = document.getElementById('show-assign-form-btn');
    var assignFormSection = document.getElementById('assign-form-section');
    var cancelAssignBtn = document.getElementById('cancel-assign-btn');
    
    if (showAssignBtn && assignFormSection) {
        showAssignBtn.onclick = function() {
            assignFormSection.style.display = 'block';
            showAssignBtn.style.display = 'none';
        };
        
        cancelAssignBtn.onclick = function() {
            assignFormSection.style.display = 'none';
            showAssignBtn.style.display = 'inline-block';
        };
    }
    
    // Assign form logic
    var assignCheckboxes = document.querySelectorAll('.assign-worker-checkbox');
    var assignWorkerIds = document.getElementById('assign-worker-ids');
    var assignSelectedCount = document.getElementById('assign-selected-count');
    var assignSubmitBtn = document.getElementById('assign-submit-btn');
    var assignSearchInput = document.getElementById('assign-worker-search');
    
    function updateAssignUI() {
        var selected = [];
        assignCheckboxes.forEach(function(cb) {
            if (cb.checked) selected.push(cb.value);
        });
        assignWorkerIds.value = selected.join(',');
        assignSelectedCount.textContent = '{{ __("Selected") }}: ' + selected.length;
        assignSubmitBtn.disabled = selected.length === 0;
    }
    
    assignCheckboxes.forEach(function(cb) {
        cb.addEventListener('change', updateAssignUI);
    });
    
    if (assignSearchInput) {
        assignSearchInput.addEventListener('input', function() {
            var search = this.value.toLowerCase();
            document.querySelectorAll('.assign-worker-row').forEach(function(row) {
                var name = row.dataset.name;
                row.style.display = name.indexOf(search) !== -1 ? '' : 'none';
            });
        });
    }
    
    // Bulk dismiss logic for current employees
    var selectAllCheckbox = document.getElementById('position-select-all');
    var checkboxes = document.querySelectorAll('.position-employee-checkbox');
    var bulkActionsPanel = document.getElementById('position-bulk-actions');
    var selectedCountEl = document.getElementById('position-selected-count');
    var bulkDismissBtn = document.getElementById('position-bulk-dismiss-btn');
    var workerIdsInput = document.getElementById('position-dismiss-worker-ids');
    var bulkForm = document.getElementById('position-bulk-dismiss-form');

    function getSelectedWorkers() {
        var selected = [];
        checkboxes.forEach(function(cb) {
            if (cb.checked) selected.push(cb.value);
        });
        return selected;
    }

    function updateUI() {
        var selected = getSelectedWorkers();
        if (selectedCountEl) selectedCountEl.textContent = selected.length;
        if (bulkActionsPanel) bulkActionsPanel.style.display = selected.length > 0 ? 'block' : 'none';
        if (workerIdsInput) workerIdsInput.value = selected.join(',');
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            checkboxes.forEach(function(cb) {
                cb.checked = selectAllCheckbox.checked;
            });
            updateUI();
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            updateUI();
            var allChecked = Array.from(checkboxes).every(function(c) { return c.checked; });
            if (selectAllCheckbox) selectAllCheckbox.checked = allChecked;
        });
    });

    if (bulkDismissBtn) {
        bulkDismissBtn.addEventListener('click', function() {
            var selected = getSelectedWorkers();
            if (selected.length === 0) return;
            
            if (confirm('{{ __("Are you sure you want to dismiss") }} ' + selected.length + ' {{ __("workers?") }}')) {
                bulkForm.submit();
            }
        });
    }
})();
</script>
