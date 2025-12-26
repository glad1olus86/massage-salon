<div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>{{ __('Date/Time') }}</th>
                <th>{{ __('User') }}</th>
                <th>{{ __('Event') }}</th>
                <th>{{ __('Description') }}</th>
                <th>{{ __('Details') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($auditLogs as $log)
                <tr>
                    <td>
                        {{ \Auth::user()->dateFormat($log->created_at) }}
                        <br>
                        <small class="text-muted">{{ \Auth::user()->timeFormat($log->created_at) }}</small>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm rounded-circle me-2 bg-primary text-white d-flex align-items-center justify-content-center"
                                style="width: 30px; height: 30px; font-size: 12px;">
                                {{ substr($log->user_name, 0, 2) }}
                            </div>
                            <span>{{ $log->user_name }}</span>
                        </div>
                    </td>
                    <td>
                        <span class="badge rounded-pill" style="background-color: {{ $log->event_color }}; color: white;">
                            <i class="{{ $log->event_icon }} me-1"></i>
                            {{ $log->event_type }}
                        </span>
                    </td>
                    <td>
                        {{ $log->description }}
                    </td>
                    <td>
                        @if (!empty($log->old_values) || !empty($log->new_values))
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                data-bs-target="#audit-details-{{ $log->id }}">
                                <i class="ti ti-eye"></i>
                            </button>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="text-muted">
                            <i class="ti ti-search" style="font-size: 48px; opacity: 0.5;"></i>
                            <p class="mt-2">{{ __('No events found') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">
    {{ $auditLogs->appends(request()->query())->links() }}
</div>

{{-- Modals moved outside of table --}}
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
