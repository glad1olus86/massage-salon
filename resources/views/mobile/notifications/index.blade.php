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
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                <span>{{ __('Notifications') }}</span>
            </div>
            @if($notifications->where('is_read', false)->count() > 0)
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="markAllAsRead()">
                    <i class="ti ti-checks"></i>
                </button>
            @endif
        </div>

        {{-- Unread Count --}}
        @php
            $unreadCount = $notifications->where('is_read', false)->count();
        @endphp
        @if($unreadCount > 0)
            <div class="alert alert-info d-flex align-items-center mb-3" style="border-radius: 10px;">
                <i class="ti ti-bell-ringing me-2"></i>
                <span>{{ __('You have :count unread notifications', ['count' => $unreadCount]) }}</span>
            </div>
        @endif

        {{-- Notifications List --}}
        <div class="mobile-card mb-3">
            <div id="notificationsList">
                @forelse($notifications as $notification)
                    <div class="notification-item {{ $notification->is_read ? '' : 'unread' }}" 
                         data-id="{{ $notification->id }}"
                         onclick="showNotificationDetail({{ $notification->id }})">
                        <div class="d-flex align-items-start">
                            <div class="notification-icon bg-{{ $notification->color ?? 'primary' }}">
                                <i class="{{ $notification->icon ?? 'ti ti-bell' }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="notification-title">{{ $notification->title ?? __('Notification') }}</div>
                                <div class="notification-text">{{ Str::limit($notification->translated_message, 60) }}</div>
                                <div class="notification-time">
                                    <i class="ti ti-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                </div>
                            </div>
                            @if(!$notification->is_read)
                                <span class="unread-dot"></span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-bell-off" style="font-size: 48px; opacity: 0.5;"></i>
                        <p class="mt-2 mb-0">{{ __('No notifications') }}</p>
                        <p class="small text-muted">{{ __('You will see notifications here when they arrive') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Quick Actions --}}
        @if($notifications->count() > 0)
            <div class="mobile-card mb-3">
                <h6 class="mb-3"><i class="ti ti-settings me-2 text-primary"></i>{{ __('Actions') }}</h6>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="markAllAsRead()">
                        <i class="ti ti-checks me-2"></i>{{ __('Mark all as read') }}
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="clearAllNotifications()">
                        <i class="ti ti-trash me-2"></i>{{ __('Clear all notifications') }}
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Notification Detail Modal --}}
    <div class="modal fade" id="notificationDetailModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Notification') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="notificationDetailBody">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-outline-danger btn-sm" id="deleteNotificationBtn">
                        <i class="ti ti-trash me-1"></i>{{ __('Delete') }}
                    </button>
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
        
        .notification-item {
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-item:active {
            background: #f8f9fa;
        }
        .notification-item.unread {
            background: linear-gradient(to right, rgba(255, 0, 73, 0.05), transparent);
        }
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .notification-icon i {
            font-size: 18px;
            color: #fff;
        }
        .notification-icon.bg-primary { background: #FF0049; }
        .notification-icon.bg-info { background: #17a2b8; }
        .notification-icon.bg-success { background: #28a745; }
        .notification-icon.bg-warning { background: #ffc107; }
        .notification-icon.bg-warning i { color: #333; }
        .notification-icon.bg-danger { background: #dc3545; }
        
        .notification-title {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 2px;
        }
        .notification-text {
            font-size: 13px;
            color: #666;
            margin-bottom: 4px;
        }
        .notification-time {
            font-size: 11px;
            color: #999;
        }
        .unread-dot {
            width: 8px;
            height: 8px;
            background: #FF0049;
            border-radius: 50%;
            flex-shrink: 0;
            margin-left: 8px;
            margin-top: 4px;
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
@endsection

@push('scripts')
<script>
var notificationsData = @json($notifications->keyBy('id'));
var currentNotificationId = null;

function showNotificationDetail(id) {
    currentNotificationId = id;
    var modal = new bootstrap.Modal(document.getElementById('notificationDetailModal'));
    var body = document.getElementById('notificationDetailBody');
    var notification = notificationsData[id];
    
    if (notification) {
        var colorClass = 'bg-' + (notification.color || 'primary');
        var iconClass = notification.icon || 'ti ti-bell';
        
        var html = '<div class="text-center mb-4">';
        html += '<div class="notification-icon ' + colorClass + '" style="width: 60px; height: 60px; margin: 0 auto;">';
        html += '<i class="' + iconClass + '" style="font-size: 28px;"></i>';
        html += '</div>';
        html += '</div>';
        
        html += '<div class="mobile-info-list">';
        html += '<div class="mobile-info-item py-2 border-bottom"><span class="text-muted d-block mb-1">{{ __("Title") }}</span><span class="fw-medium">' + (notification.title || '{{ __("Notification") }}') + '</span></div>';
        html += '<div class="mobile-info-item py-2 border-bottom"><span class="text-muted d-block mb-1">{{ __("Message") }}</span><span>' + (notification.message || '') + '</span></div>';
        html += '<div class="mobile-info-item d-flex justify-content-between py-2 border-bottom"><span class="text-muted">{{ __("Date") }}</span><span>' + formatDate(notification.created_at) + '</span></div>';
        html += '<div class="mobile-info-item d-flex justify-content-between py-2"><span class="text-muted">{{ __("Status") }}</span><span class="badge ' + (notification.is_read ? 'bg-secondary' : 'bg-primary') + '">' + (notification.is_read ? '{{ __("Read") }}' : '{{ __("Unread") }}') + '</span></div>';
        html += '</div>';
        
        if (notification.link && notification.link !== '#') {
            html += '<div class="mt-3"><a href="' + notification.link + '" class="btn mobile-btn-primary w-100"><i class="ti ti-external-link me-2"></i>{{ __("View Details") }}</a></div>';
        }
        
        body.innerHTML = html;
        
        // Mark as read
        if (!notification.is_read) {
            markAsRead(id);
        }
    }
    
    modal.show();
}

function markAsRead(id) {
    fetch('{{ url("/notifications") }}/' + id + '/read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (response.ok) {
            var item = document.querySelector('.notification-item[data-id="' + id + '"]');
            if (item) {
                item.classList.remove('unread');
                var dot = item.querySelector('.unread-dot');
                if (dot) dot.remove();
            }
            notificationsData[id].is_read = true;
        }
    })
    .catch(function(error) {
        console.log('Error marking as read:', error);
    });
}

function markAllAsRead() {
    fetch('{{ route("notifications.mark-all-read") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (response.ok) {
            document.querySelectorAll('.notification-item.unread').forEach(function(item) {
                item.classList.remove('unread');
                var dot = item.querySelector('.unread-dot');
                if (dot) dot.remove();
            });
            show_toastr('success', '{{ __("All notifications marked as read") }}');
        }
    })
    .catch(function(error) {
        show_toastr('error', '{{ __("Error occurred") }}');
    });
}

function clearAllNotifications() {
    if (confirm('{{ __("Are you sure you want to delete all notifications?") }}')) {
        fetch('{{ route("notifications.clear-all") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                window.location.reload();
            }
        })
        .catch(function(error) {
            show_toastr('error', '{{ __("Error occurred") }}');
        });
    }
}

function formatDate(dateStr) {
    var date = new Date(dateStr);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
}

document.getElementById('deleteNotificationBtn').addEventListener('click', function() {
    if (currentNotificationId && confirm('{{ __("Delete this notification?") }}')) {
        fetch('{{ url("/notifications") }}/' + currentNotificationId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (response.ok) {
                var item = document.querySelector('.notification-item[data-id="' + currentNotificationId + '"]');
                if (item) item.remove();
                bootstrap.Modal.getInstance(document.getElementById('notificationDetailModal')).hide();
                show_toastr('success', '{{ __("Notification deleted") }}');
            }
        })
        .catch(function(error) {
            show_toastr('error', '{{ __("Error occurred") }}');
        });
    }
});
</script>
@endpush
