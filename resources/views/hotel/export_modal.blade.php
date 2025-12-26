<div class="modal-body">
    {{-- Search Field --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="form-group mb-0">
                <input type="text" id="export-search" class="form-control" placeholder="{{ __('Search by name...') }}">
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

    {{-- Hotels Table --}}
    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
        <table class="table table-hover" id="export-hotels-table">
            <thead class="sticky-top bg-white">
                <tr>
                    <th style="width: 40px;"></th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Address') }}</th>
                    <th>{{ __('Rooms') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($hotels as $hotel)
                    <tr class="export-hotel-row" 
                        data-name="{{ strtolower($hotel->name) }}">
                        <td>
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input export-hotel-checkbox" 
                                    value="{{ $hotel->id }}">
                            </div>
                        </td>
                        <td>{{ $hotel->name }}</td>
                        <td>{{ $hotel->address ?? '-' }}</td>
                        <td>{{ $hotel->rooms_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Selected Count --}}
    <div class="row mt-3">
        <div class="col-12">
            <span class="fw-bold">{{ __('Selected:') }} <span id="export-selected-count">0</span></span>
        </div>
    </div>
</div>

<div class="modal-footer">
    <input type="button" value="{{ __('Cancel') }}" class="btn btn-light" data-bs-dismiss="modal">
    
    <form id="export-pdf-form" method="POST" action="{{ route('hotel.export.pdf') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-danger" id="export-pdf-btn" disabled>
            <i class="ti ti-file-type-pdf me-1"></i>{{ __('Export to PDF') }}
        </button>
    </form>
    
    <form id="export-excel-form" method="POST" action="{{ route('hotel.export.excel') }}" class="d-inline">
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

        var selectedIds = new Set();

        function getVisibleRows() {
            return document.querySelectorAll('.export-hotel-row:not([style*="display: none"])');
        }

        function getAllCheckboxes() {
            return document.querySelectorAll('.export-hotel-checkbox');
        }

        function getVisibleCheckboxes() {
            var checkboxes = [];
            getVisibleRows().forEach(function(row) {
                var cb = row.querySelector('.export-hotel-checkbox');
                if (cb) checkboxes.push(cb);
            });
            return checkboxes;
        }

        function syncCheckboxesWithSelection() {
            getAllCheckboxes().forEach(function(cb) {
                cb.checked = selectedIds.has(cb.value);
            });
        }

        function updateUI() {
            var count = selectedIds.size;
            
            selectedCountEl.textContent = count;
            pdfBtn.disabled = count === 0;
            excelBtn.disabled = count === 0;

            var visibleCheckboxes = getVisibleCheckboxes();
            var allVisibleChecked = visibleCheckboxes.length > 0 && 
                visibleCheckboxes.every(function(cb) { return cb.checked; });
            selectAllCheckbox.checked = allVisibleChecked;
            selectAllCheckbox.indeterminate = !allVisibleChecked && 
                visibleCheckboxes.some(function(cb) { return cb.checked; });
        }

        function updateHiddenInputs(form) {
            form.querySelectorAll('input[name="hotel_ids[]"]').forEach(function(input) {
                input.remove();
            });
            selectedIds.forEach(function(id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'hotel_ids[]';
                input.value = id;
                form.appendChild(input);
            });
        }

        searchInput.addEventListener('input', function() {
            var searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.export-hotel-row').forEach(function(row) {
                var name = row.dataset.name;
                row.style.display = name.indexOf(searchTerm) !== -1 ? '' : 'none';
            });
            updateUI();
        });

        selectAllCheckbox.addEventListener('change', function() {
            var checked = this.checked;
            getVisibleCheckboxes().forEach(function(cb) {
                cb.checked = checked;
                if (checked) {
                    selectedIds.add(cb.value);
                } else {
                    selectedIds.delete(cb.value);
                }
            });
            updateUI();
        });

        document.getElementById('export-hotels-table').addEventListener('change', function(e) {
            if (e.target.classList.contains('export-hotel-checkbox')) {
                if (e.target.checked) {
                    selectedIds.add(e.target.value);
                } else {
                    selectedIds.delete(e.target.value);
                }
                updateUI();
            }
        });

        pdfForm.addEventListener('submit', function(e) {
            if (selectedIds.size === 0) {
                e.preventDefault();
                return false;
            }
            updateHiddenInputs(pdfForm);
        });

        excelForm.addEventListener('submit', function(e) {
            if (selectedIds.size === 0) {
                e.preventDefault();
                return false;
            }
            updateHiddenInputs(excelForm);
        });

        syncCheckboxesWithSelection();
        updateUI();
    }, 100);
})();
</script>
