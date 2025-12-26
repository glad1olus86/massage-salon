@extends('layouts.admin')

@section('page-title')
    {{ __('Notifications') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Notifications') }}</li>
@endsection

@section('action-btn')
    @if($notifications->where('is_read', false)->count() > 0)
        <form action="{{ route('notifications.read.all') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="ti ti-checks me-1"></i>{{ __('Mark all as read') }}
            </button>
        </form>
    @endif
@endsection

@push('css-page')
<style>
    /* Fix giant SVG icons in pagination */
    nav[role="navigation"] svg,
    .pagination svg,
    [aria-label="Pagination Navigation"] svg {
        width: 20px !important;
        height: 20px !important;
        max-width: 20px !important;
        max-height: 20px !important;
    }
    
    .notifications-list .avatar {
        width: 40px;
        height: 40px;
        min-width: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
    }
    .notifications-list .avatar i {
        font-size: 18px !important;
    }
    .pagination svg {
        width: 16px !important;
        height: 16px !important;
    }
    .pagination .page-link {
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body notifications-list">
                @if($notifications->count() > 0)
                    <div class="list-group list-group-flush">
                        @foreach($notifications as $notification)
                            <div class="list-group-item d-flex align-items-start {{ !$notification->is_read ? 'bg-light' : '' }}">
                                <div class="me-3">
                                    <span class="avatar avatar-sm bg-white border">
                                        <i class="{{ $notification->icon }} text-{{ $notification->color }}"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1 {{ !$notification->is_read ? 'fw-bold' : '' }}">
                                                {{ $notification->title }}
                                            </h6>
                                            <p class="mb-1 text-muted" style="white-space: pre-line;">{{ $notification->translated_message }}</p>
                                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                        </div>
                                        <div class="btn-group">
                                            @if($notification->link)
                                                <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                            @endif
                                            @if(!$notification->is_read)
                                                <form action="{{ route('notifications.read', $notification->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success" title="{{ __('Mark as read') }}">
                                                        <i class="ti ti-check"></i>
                                                    </button>
                                                </form>
                                            @endif
                                            <form action="{{ route('notifications.destroy', $notification->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Delete') }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-3">
                        {{ $notifications->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="ti ti-bell-off text-muted" style="font-size: 48px;"></i>
                        <p class="text-muted mt-3">{{ __('No notifications') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
