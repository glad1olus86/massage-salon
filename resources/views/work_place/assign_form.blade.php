@php
    $unassignedWorkers = \App\Models\Worker::whereDoesntHave('currentWorkAssignment')
        ->where('created_by', \Auth::user()->creatorId())
        ->get();
    $currentEmployees = $workPlace->currentAssignments->count();
    $positions = $workPlace->positions()->orderBy('name')->get();
@endphp

{{ Form::open(['route' => ['work-place.assign.workers.bulk', $workPlace->id], 'method' => 'POST', 'id' => 'assign-workers-form']) }}
<div class="modal-body">
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">
                {{ __('Work Place') }}: <strong>{{ $workPlace->name }}</strong> | 
                {{ __('Employees') }}: <strong>{{ $currentEmployees }}</strong>
            </span>
            <span class="badge bg-primary" id="selected-count-badge">{{ __('Selected') }}: 0</span>
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

    <div class="mb-3">
        <label for="position_id" class="form-label">{{ __('Position') }} <span class="text-danger">*</span></label>
        <select name="position_id" id="position_id" class="form-control" required>
            @if($positions->count() === 1)
                {{-- Auto-select if only one position --}}
                <option value="{{ $positions->first()->id }}" selected>{{ $positions->first()->name }}</option>
            @else
                <option value="">{{ __('Select Position') }}</option>
                @foreach($positions as $position)
                    <option value="{{ $position->id }}">{{ $position->name }}</option>
                @endforeach
            @endif
        </select>
        @if($positions->isEmpty())
            <small class="text-warning">
                <i class="ti ti-alert-triangle"></i> 
                {{ __('First create positions in section') }} 
                <a href="{{ route('work-place.positions', $workPlace->id) }}" target="_blank">{{ __('Positions') }}</a>
            </small>
        @elseif($positions->count() === 1)
            <small class="text-success">
                <i class="ti ti-check"></i> 
                {{ __('Position selected automatically') }}
            </small>
        @endif
    </div>

    <input type="hidden" name="worker_ids" id="worker-ids-input">
    
    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
        <table class="table table-hover" id="workers-select-table">
            <thead class="sticky-top bg-white">
                <tr>
                    <th style="width: 40px;"></th>
                    <th>{{ __('First Name Last Name') }}</th>
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
                        <td colspan="4" class="text-center text-muted py-4">
                            <i class="ti ti-users-minus" style="font-size: 24px;"></i><br>
                            {{ __('No available workers without job') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    <button type="submit" class="btn btn-primary" id="assign-submit-btn" disabled>
        <i class="ti ti-briefcase me-1"></i>{{ __('Assign to Work') }}
    </button>
</div>
{{ Form::close() }}

<script>
(function() {
    var selectedWorkers = [];
    var checkboxes = document.querySelectorAll('.worker-select-checkbox');
    var submitBtn = document.getElementById('assign-submit-btn');
    var workerIdsInput = document.getElementById('worker-ids-input');
    var selectedCountBadge = document.getElementById('selected-count-badge');
    var searchInput = document.getElementById('worker-search');
    var filterButtons = document.querySelectorAll('.filter-gender');
    var currentGenderFilter = 'all';

    function updateUI() {
        selectedCountBadge.textContent = '{{ __("Selected") }}: ' + selectedWorkers.length;
        workerIdsInput.value = selectedWorkers.join(',');
        submitBtn.disabled = selectedWorkers.length === 0;
    }

    function filterRows() {
        var searchTerm = searchInput.value.toLowerCase();
        document.querySelectorAll('.worker-row').forEach(function(row) {
            var name = row.dataset.name;
            var gender = row.dataset.gender;
            var matchesSearch = name.indexOf(searchTerm) !== -1;
            var matchesGender = currentGenderFilter === 'all' || gender === currentGenderFilter;
            row.style.display = (matchesSearch && matchesGender) ? '' : 'none';
        });
    }

    checkboxes.forEach(function(cb) {
        cb.addEventListener('change', function() {
            var workerId = this.value;
            if (this.checked) {
                if (selectedWorkers.indexOf(workerId) === -1) selectedWorkers.push(workerId);
            } else {
                var index = selectedWorkers.indexOf(workerId);
                if (index > -1) selectedWorkers.splice(index, 1);
            }
            updateUI();
        });
    });

    searchInput.addEventListener('input', filterRows);
    
    filterButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterButtons.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            currentGenderFilter = this.dataset.gender;
            filterRows();
        });
    });

    updateUI();
})();
</script>
