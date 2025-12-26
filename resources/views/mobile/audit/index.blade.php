@extends('layouts.mobile')

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            <div class="mobile-header-left">
                <button type="button" class="mobile-header-btn" onclick="openSidebar()">
                    <img src="{{ asset('fromfigma/menu_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><line x1=3 y1=6 x2=21 y2=6></line><line x1=3 y1=12 x2=21 y2=12></line><line x1=3 y1=18 x2=21 y2=18></line></svg>'">
                </button>
                <a href="{{ route('mobile.notifications.index') }}" class="mobile-header-btn">
                    <img src="{{ asset('fromfigma/bell_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\'></path><path d=\'M13.73 21a2 2 0 0 1-3.46 0\'></path></svg>'">
                </a>
            </div>
            <div class="mobile-header-right">
                <div class="mobile-logo">
                    <img src="{{ asset('fromfigma/jobsi_mobile.png') }}" alt="JOBSI">
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-content">
        {{-- Page Title --}}
        <div class="mobile-section-title">
            <div class="mobile-section-title-left">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14,2 14,8 20,8"></polyline>
                    <line x1="16" y1="13" x2="8" y2="13"></line>
                    <line x1="16" y1="17" x2="8" y2="17"></line>
                </svg>
                <span>{{ __('Audit Log') }}</span>
            </div>
            <a href="{{ route('mobile.calendar.index') }}" class="mobile-add-btn">
                <i class="ti ti-calendar" style="font-size: 20px; color: #FF0049;"></i>
            </a>
        </div>

        {{-- Filter --}}
        <div class="mobile-card mb-3">
            <div class="d-flex gap-2 align-items-center">
                <select id="eventTypeFilter" class="form-control form-control-sm flex-grow-1">
                    <option value="">{{ __('All Events') }}</option>
                    <option value="worker.created">{{ __('Worker created') }}</option>
                    <option value="worker.updated">{{ __('Worker updated') }}</option>
                    <option value="worker.checked_in">{{ __('Check-in') }}</option>
                    <option value="worker.checked_out">{{ __('Check-out') }}</option>
                    <option value="worker.hired">{{ __('Employment') }}</option>
                    <option value="worker.dismissed">{{ __('Dismissal') }}</option>
                </select>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearFilter()">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        </div>

        {{-- Audit Log List --}}
        <div class="mobile-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="ti ti-history me-2 text-primary"></i>{{ __('Recent Activity') }}</h6>
                <span class="badge bg-light text-dark">{{ $logs->count() }}</span>
            </div>

            <div id="auditLogList">
                @forelse($logs as $log)
                    <div class="audit-item" data-event-type="{{ $log->event_type }}" onclick="showLogDetail({{ $log->id }})">
                        <div class="d-flex align-items-start">
                            <span class="event-type-dot" style="background-color: {{ getEventColor($log->event_type) }};"></span>
                            <div class="flex-grow-1">
                                <div class="fw-medium text-dark mb-1">{{ getEventLabel($log->event_type) }}</div>
                                <small class="text-muted d-block">
                                    @if($log->subject_type === 'App\Models\Worker' && $log->subject)
                                        {{ $log->subject->first_name ?? '' }} {{ $log->subject->last_name ?? '' }}
                                    @elseif($log->description)
                                        {{ Str::limit($log->translated_description, 40) }}
                                    @endif
                                </small>
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">
                                        <i class="ti ti-user me-1"></i>{{ $log->user_name ?? __('System') }}
                                    </small>
                                    <small class="text-muted">{{ $log->created_at->format('d.m.Y H:i') }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-history-off" style="font-size: 48px; opacity: 0.5;"></i>
                        <p class="mt-2 mb-0">{{ __('No audit records') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Statistics --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-chart-bar me-2 text-primary"></i>{{ __('Statistics') }}</h6>
            <div class="row g-2">
                @php
                    $stats = $logs->groupBy('event_type')->map->count();
                @endphp
                @foreach($stats->take(6) as $type => $count)
                    <div class="col-6">
                        <div class="stat-item">
                            <span class="stat-dot" style="background-color: {{ getEventColor($type) }};"></span>
                            <span class="stat-label">{{ getEventLabel($type) }}</span>
                            <span class="stat-count">{{ $count }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Log Detail Modal --}}
    <div class="modal fade" id="logDetailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Event Details') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="logDetailBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .mobile-btn-primary {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
            color: #fff !important;
        }
        .mobile-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .text-primary {
            color: #FF0049 !important;
        }
        .mobile-add-btn {
            border: none !important;
            background: transparent;
        }
        
        .audit-item {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
        }
        .audit-item:last-child {
            border-bottom: none;
        }
        .audit-item:active {
            background: #f8f9fa;
        }
        .event-type-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 12px;
            margin-top: 5px;
            flex-shrink: 0;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 12px;
        }
        .stat-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .stat-label {
            flex-grow: 1;
            color: #666;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .stat-count {
            font-weight: 600;
            color: #333;
        }
        
        @media (max-width: 576px) {
            .modal-fullscreen-sm-down .modal-content {
                height: 100%;
                border: 0;
                border-radius: 0;
            }
            .modal-fullscreen-sm-down .modal-dialog {
                width: 100%;
                max-width: none;
                height: 100%;
                margin: 0;
            }
        }
    </style>

    @php
        function getEventColor($eventType) {
            $colors = [
                'worker.created' => '#28a745',
                'worker.updated' => '#17a2b8',
                'worker.deleted' => '#6c757d',
                'worker.checked_in' => '#007bff',
                'worker.checked_out' => '#fd7e14',
                'worker.hired' => '#6f42c1',
                'worker.dismissed' => '#dc3545',
                'room.created' => '#20c997',
                'room.updated' => '#17a2b8',
                'room.deleted' => '#6c757d',
                'work_place.created' => '#20c997',
                'work_place.updated' => '#17a2b8',
                'work_place.deleted' => '#6c757d',
                'hotel.created' => '#28a745',
                'hotel.updated' => '#17a2b8',
                'hotel.deleted' => '#6c757d',
            ];
            return $colors[$eventType] ?? '#6c757d';
        }
        
        function getEventLabel($eventType) {
            $labels = [
                'worker.created' => __('Worker created'),
                'worker.updated' => __('Worker updated'),
                'worker.deleted' => __('Worker deleted'),
                'worker.checked_in' => __('Check-in'),
                'worker.checked_out' => __('Check-out'),
                'worker.hired' => __('Employment'),
                'worker.dismissed' => __('Dismissal'),
                'room.created' => __('Room created'),
                'room.updated' => __('Room updated'),
                'room.deleted' => __('Room deleted'),
                'work_place.created' => __('Work place created'),
                'work_place.updated' => __('Work place updated'),
                'work_place.deleted' => __('Work place deleted'),
                'hotel.created' => __('Hotel created'),
                'hotel.updated' => __('Hotel updated'),
                'hotel.deleted' => __('Hotel deleted'),
            ];
            return $labels[$eventType] ?? $eventType;
        }
    @endphp
@endsection

@push('scripts')
<script>
var logsData = @json($logs->keyBy('id'));

document.addEventListener('DOMContentLoaded', function() {
    var filterSelect = document.getElementById('eventTypeFilter');
    if (filterSelect) {
        filterSelect.addEventListener('change', function() {
            filterLogs(this.value);
        });
    }
});

function filterLogs(eventType) {
    document.querySelectorAll('.audit-item').forEach(function(item) {
        if (!eventType || item.dataset.eventType === eventType) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

function clearFilter() {
    document.getElementById('eventTypeFilter').value = '';
    filterLogs('');
}

function showLogDetail(logId) {
    var modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
    var body = document.getElementById('logDetailBody');
    var log = logsData[logId];
    
    if (log) {
        var html = '<div class="mobile-info-list">';
        html += '<div class="mobile-info-item d-flex justify-content-between py-2 border-bottom"><span class="text-muted">{{ __("Event Type") }}</span><span class="fw-medium">' + getEventLabel(log.event_type) + '</span></div>';
        html += '<div class="mobile-info-item d-flex justify-content-between py-2 border-bottom"><span class="text-muted">{{ __("User") }}</span><span>' + (log.user_name || '{{ __("System") }}') + '</span></div>';
        html += '<div class="mobile-info-item d-flex justify-content-between py-2 border-bottom"><span class="text-muted">{{ __("Date") }}</span><span>' + formatDate(log.created_at) + '</span></div>';
        if (log.description) {
            html += '<div class="mobile-info-item py-2 border-bottom"><span class="text-muted d-block mb-1">{{ __("Description") }}</span><span>' + log.description + '</span></div>';
        }
        if (log.ip_address) {
            html += '<div class="mobile-info-item d-flex justify-content-between py-2"><span class="text-muted">{{ __("IP Address") }}</span><span>' + log.ip_address + '</span></div>';
        }
        html += '</div>';
        
        body.innerHTML = html;
    }
    
    modal.show();
}

function getEventLabel(eventType) {
    var labels = {
        'worker.created': '{{ __("Worker created") }}',
        'worker.updated': '{{ __("Worker updated") }}',
        'worker.deleted': '{{ __("Worker deleted") }}',
        'worker.checked_in': '{{ __("Check-in") }}',
        'worker.checked_out': '{{ __("Check-out") }}',
        'worker.hired': '{{ __("Employment") }}',
        'worker.dismissed': '{{ __("Dismissal") }}',
        'room.created': '{{ __("Room created") }}',
        'room.updated': '{{ __("Room updated") }}',
        'room.deleted': '{{ __("Room deleted") }}',
        'work_place.created': '{{ __("Work place created") }}',
        'work_place.updated': '{{ __("Work place updated") }}',
        'work_place.deleted': '{{ __("Work place deleted") }}',
        'hotel.created': '{{ __("Hotel created") }}',
        'hotel.updated': '{{ __("Hotel updated") }}',
        'hotel.deleted': '{{ __("Hotel deleted") }}'
    };
    return labels[eventType] || eventType;
}

function formatDate(dateStr) {
    var date = new Date(dateStr);
    return date.toLocaleDateString('ru-RU') + ' ' + date.toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'});
}
</script>
@endpush
