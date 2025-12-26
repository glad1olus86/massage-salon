<div class="modal-body">
    {{-- Search Field --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="form-group mb-0">
                <input type="text" id="export-search" class="form-control" placeholder="{{ __('Search by first or last name...') }}">
            </div>
        </div>
    </div>

    {{-- Select All Checkbox --}}
    <div class="row mb-2">
        <div class="col-12">
            <div class="form-check">
                <input type="checkbox" class="form-check-input" id="export-select-all">
                <label class="form-check-label fw-bold" for="export-select-all">{{ __('Select All') }}</label>
            </div>
        </div>
    </div>

    {{-- Workers Table --}}
    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
        <table class="table table-hover" id="export-workers-table">
            <thead class="sticky-top bg-white">
                <tr>
                    <th style="width: 40px;"></th>
                    <th>{{ __('First Name') }}</th>
                    <th>{{ __('Last Name') }}</th>
                    <th>{{ __('Gender') }}</th>
                    <th>{{ __('Nationality') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($workers as $worker)
                    <tr class="export-worker-row" 
                        data-name="{{ strtolower($worker->first_name . ' ' . $worker->last_name) }}">
                        <td>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input export-worker-checkbox" 
                                    value="{{ $worker->id }}">
                            </div>
                        </td>
                        <td>{{ $worker->first_name }}</td>
                        <td>{{ $worker->last_name }}</td>
                        <td>{{ $worker->gender == 'male' ? __('Male') : __('Female') }}</td>
                        <td>{{ $worker->nationality }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Selected Count --}}
    <div class="row mt-3">
        <div class="col-12">
            <span class="fw-bold">{{ __('Selected:') }} <span id="export-selected-count">0</span> {{ __('workers') }}</span>
        </div>
    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    
    <form id="export-pdf-form" method="POST" action="{{ route('worker.export.pdf') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-danger" id="export-pdf-btn" disabled>
            <i class="ti ti-file-type-pdf me-1"></i>{{ __('Export to PDF') }}
        </button>
    </form>
    
    <form id="export-excel-form" method="POST" action="{{ route('worker.export.excel') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-success" id="export-excel-btn" disabled>
            <i class="ti ti-file-spreadsheet me-1"></i>{{ __('Export to Excel') }}
        </button>
    </form>
</div>

<script>
(function() {
    setTimeout(function() {
        var searchInput = document.getElementById('export-search');
        var selectAllCheckbox = document.getElementById('export-select-all');
        var selectedCountEl = document.getElementById('export-selected-count');
        var pdfBtn = document.getElementById('export-pdf-btn');
        var excelBtn = document.getElementById('export-excel-btn');
        var pdfForm = document.getElementById('export-pdf-form');
        var excelForm = document.getElementById('export-excel-form');

        // Store selected IDs (persists across filtering)
        var selectedWorkerIds = new Set();

        function getVisibleRows() {
            return document.querySelectorAll('.export-worker-row:not([style*="display: none"])');
        }

        function getAllCheckboxes() {
            return document.querySelectorAll('.export-worker-checkbox');
        }

        function getVisibleCheckboxes() {
            var checkboxes = [];
            getVisibleRows().forEach(function(row) {
                var cb = row.querySelector('.export-worker-checkbox');
                if (cb) checkboxes.push(cb);
            });
            return checkboxes;
        }

        function syncCheckboxesWithSelection() {
            getAllCheckboxes().forEach(function(cb) {
                cb.checked = selectedWorkerIds.has(cb.value);
            });
        }

        function updateUI() {
            var count = selectedWorkerIds.size;
            
            selectedCountEl.textContent = count;
            pdfBtn.disabled = count === 0;
            excelBtn.disabled = count === 0;

            // Update select all checkbox state
            var visibleCheckboxes = getVisibleCheckboxes();
            var allVisibleChecked = visibleCheckboxes.length > 0 && 
                visibleCheckboxes.every(function(cb) { return cb.checked; });
            selectAllCheckbox.checked = allVisibleChecked;
            selectAllCheckbox.indeterminate = !allVisibleChecked && 
                visibleCheckboxes.some(function(cb) { return cb.checked; });
        }

        function updateHiddenInputs(form) {
            // Remove old hidden inputs
            form.querySelectorAll('input[name="worker_ids[]"]').forEach(function(input) {
                input.remove();
            });
            // Add new hidden inputs for each selected ID
            selectedWorkerIds.forEach(function(id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'worker_ids[]';
                input.value = id;
                form.appendChild(input);
            });
        }

        // Search filter
        searchInput.addEventListener('input', function() {
            var searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.export-worker-row').forEach(function(row) {
                var name = row.dataset.name;
                if (name.indexOf(searchTerm) !== -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            updateUI();
        });

        // Select all checkbox
        selectAllCheckbox.addEventListener('change', function() {
            var checked = this.checked;
            getVisibleCheckboxes().forEach(function(cb) {
                cb.checked = checked;
                if (checked) {
                    selectedWorkerIds.add(cb.value);
                } else {
                    selectedWorkerIds.delete(cb.value);
                }
            });
            updateUI();
        });

        // Individual checkboxes - use event delegation
        document.getElementById('export-workers-table').addEventListener('change', function(e) {
            if (e.target.classList.contains('export-worker-checkbox')) {
                if (e.target.checked) {
                    selectedWorkerIds.add(e.target.value);
                } else {
                    selectedWorkerIds.delete(e.target.value);
                }
                updateUI();
            }
        });

        // Form submission handlers
        pdfForm.addEventListener('submit', function(e) {
            if (selectedWorkerIds.size === 0) {
                e.preventDefault();
                return false;
            }
            updateHiddenInputs(pdfForm);
        });

        excelForm.addEventListener('submit', function(e) {
            if (selectedWorkerIds.size === 0) {
                e.preventDefault();
                return false;
            }
            updateHiddenInputs(excelForm);
        });

        // Initial state
        syncCheckboxesWithSelection();
        updateUI();
    }, 100);
})();
</script>
