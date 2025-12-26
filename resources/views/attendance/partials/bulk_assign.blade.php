<div class="modal fade" id="bulkAssignModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('attendance.schedule.assign-bulk') }}" method="POST" id="bulkAssignForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Bulk Assign Shifts') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label fw-bold">1. {{ __('Select Work Days') }}</label>
                        <div class="d-flex gap-2 flex-wrap">
                            @foreach(['Mon' => 1, 'Tue' => 2, 'Wed' => 3, 'Thu' => 4, 'Fri' => 5, 'Sat' => 6, 'Sun' => 7] as $day => $num)
                                <div class="form-check">
                                    <input type="checkbox" name="work_days[]" value="{{ $num }}" 
                                           class="form-check-input" id="day{{ $num }}"
                                           {{ $num <= 5 ? 'checked' : '' }}>
                                    <label class="form-check-label" for="day{{ $num }}">{{ __($day) }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">2. {{ __('Valid From') }}</label>
                            <input type="date" name="valid_from" class="form-control" 
                                   value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">{{ __('Valid Until') }} <small class="text-muted">({{ __('optional') }})</small></label>
                            <input type="date" name="valid_until" class="form-control">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">3. {{ __('Select Shift Template') }}</label>
                        <select name="shift_template_id" class="form-select" required>
                            <option value="">{{ __('Select Shift') }}</option>
                            @foreach($shiftTemplates as $template)
                                <option value="{{ $template->id }}" data-color="{{ $template->color }}">
                                    {{ $template->name }} ({{ \Carbon\Carbon::parse($template->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($template->end_time)->format('H:i') }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">4. {{ __('Select Workers') }}</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input type="text" id="workerSearch" class="form-control" 
                                   placeholder="{{ __('Search workers...') }}">
                        </div>
                        <div class="border rounded p-2" style="max-height: 250px; overflow-y: auto;" id="workersList">
                            <div class="text-center text-muted py-3" id="workersLoading">
                                {{ __('Loading workers...') }}
                            </div>
                        </div>
                        <div class="mt-2 text-muted small">
                            {{ __('Selected') }}: <span id="selectedCount">0</span> {{ __('workers') }}
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="assignBtn" disabled>
                        <i class="ti ti-check"></i> {{ __('Assign') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script-page')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('bulkAssignModal');
    var workersList = document.getElementById('workersList');
    var searchInput = document.getElementById('workerSearch');
    var selectedCount = document.getElementById('selectedCount');
    var assignBtn = document.getElementById('assignBtn');
    var searchTimeout;

    modal.addEventListener('shown.bs.modal', function() {
        loadWorkers('');
    });

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadWorkers(searchInput.value);
        }, 300);
    });

    function loadWorkers(search) {
        workersList.innerHTML = '<div class="text-center text-muted py-3">{{ __("Loading...") }}</div>';
        
        fetch('{{ route("attendance.api.workers") }}?search=' + encodeURIComponent(search) + '&work_place_id={{ $selectedWorkPlaceId }}')
            .then(response => response.json())
            .then(data => {
                if (data.data.length === 0) {
                    workersList.innerHTML = '<div class="text-center text-muted py-3">{{ __("No workers found") }}</div>';
                    return;
                }
                
                var html = '';
                data.data.forEach(function(worker) {
                    html += '<div class="form-check py-1 border-bottom">' +
                        '<input type="checkbox" name="worker_ids[]" value="' + worker.id + '" ' +
                        'class="form-check-input worker-checkbox" id="worker' + worker.id + '">' +
                        '<label class="form-check-label" for="worker' + worker.id + '">' +
                        '<strong>' + worker.name + '</strong>' +
                        (worker.work_place ? ' <small class="text-muted">(' + worker.work_place + ')</small>' : '') +
                        '</label></div>';
                });
                workersList.innerHTML = html;
                
                document.querySelectorAll('.worker-checkbox').forEach(function(cb) {
                    cb.addEventListener('change', updateSelectedCount);
                });
            })
            .catch(function() {
                workersList.innerHTML = '<div class="text-center text-danger py-3">{{ __("Error loading workers") }}</div>';
            });
    }

    function updateSelectedCount() {
        var count = document.querySelectorAll('.worker-checkbox:checked').length;
        selectedCount.textContent = count;
        assignBtn.disabled = count === 0;
    }
});
</script>
@endpush
