@if ($logs->count() > 0)
    <div class="accordion" id="accordionDayDetails">
        @foreach ($logs as $userName => $userLogs)
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-{{ Str::slug($userName) }}">
                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#collapse-{{ Str::slug($userName) }}"
                        aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                        aria-controls="collapse-{{ Str::slug($userName) }}">
                        <div class="d-flex align-items-center w-100">
                            <div class="avatar-sm rounded-circle me-2 bg-primary text-white d-flex align-items-center justify-content-center"
                                style="width: 24px; height: 24px; font-size: 10px;">
                                {{ substr($userName, 0, 2) }}
                            </div>
                            <span class="fw-bold">{{ $userName }}</span>
                            <span class="badge bg-secondary ms-2 rounded-pill">{{ $userLogs->count() }}</span>
                        </div>
                    </button>
                </h2>
                <div id="collapse-{{ Str::slug($userName) }}"
                    class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                    aria-labelledby="heading-{{ Str::slug($userName) }}" data-bs-parent="#accordionDayDetails">
                    <div class="accordion-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach ($userLogs as $log)
                                <div class="list-group-item">
                                    @include('audit_log.partials.event_item', ['log' => $log])
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-5">
        <i class="ti ti-calendar-off" style="font-size: 48px; opacity: 0.3;"></i>
        <p class="mt-3 text-muted">{{ __('No events found for this day') }}</p>
    </div>
@endif
