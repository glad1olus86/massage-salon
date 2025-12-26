{{-- Audit log detail modals --}}
@foreach($auditLogs as $log)
    @if (!empty($log->old_values) || !empty($log->new_values))
        <div class="modal fade" id="audit-details-{{ $log->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Event Details') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            @if (!empty($log->old_values))
                                <div class="col-md-6">
                                    <h6 class="text-danger">{{ __('Old Values') }}</h6>
                                    <pre class="bg-light p-2 rounded">@json($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                </div>
                            @endif
                            @if (!empty($log->new_values))
                                <div class="col-md-6">
                                    <h6 class="text-success">{{ __('New Values') }}</h6>
                                    <pre class="bg-light p-2 rounded">@json($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                </div>
                            @endif
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">{{ __('IP Address:') }}</small>
                                <p>{{ $log->ip_address }}</p>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted">{{ __('User Agent:') }}</small>
                                <p>{{ $log->user_agent }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
