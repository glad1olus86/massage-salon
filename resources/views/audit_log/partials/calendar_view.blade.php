<div class="calendar-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 id="calendar-month-year" class="mb-0 text-capitalize"></h4>
        <div class="btn-group">
            <button class="btn btn-outline-secondary" id="prev-month">
                <i class="ti ti-chevron-left"></i>
            </button>
            <button class="btn btn-outline-secondary" id="next-month">
                <i class="ti ti-chevron-right"></i>
            </button>
        </div>
    </div>

    <div class="calendar-grid" id="calendar-grid">
        {{-- Days will be rendered here via JS --}}
    </div>
</div>

{{-- Modal for Day Details --}}
<div class="modal fade" id="day-details-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="day-details-title">{{ __('Events for the day') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="day-details-body">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


