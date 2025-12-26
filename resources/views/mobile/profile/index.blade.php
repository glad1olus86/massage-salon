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
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <span>{{ __('Profile') }}</span>
            </div>
        </div>

        {{-- User Avatar & Name --}}
        <div class="mobile-card mb-3 text-center">
            <div class="profile-avatar mx-auto mb-3">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}">
                @else
                    <div class="profile-avatar-placeholder">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                @endif
            </div>
            <h5 class="fw-bold mb-1">{{ $user->name }}</h5>
            <p class="text-muted mb-2">{{ $user->email }}</p>
            <span class="badge bg-primary">{{ ucfirst($user->type) }}</span>
        </div>

        {{-- User Information --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-info-circle me-2 text-primary"></i>{{ __('Account Information') }}</h6>
            
            <div class="profile-info-item">
                <span class="profile-info-label">{{ __('Name') }}</span>
                <span class="profile-info-value">{{ $user->name }}</span>
            </div>
            <div class="profile-info-item">
                <span class="profile-info-label">{{ __('Email') }}</span>
                <span class="profile-info-value">{{ $user->email }}</span>
            </div>
            @if($user->phone)
                <div class="profile-info-item">
                    <span class="profile-info-label">{{ __('Phone') }}</span>
                    <span class="profile-info-value">{{ $user->phone }}</span>
                </div>
            @endif
            <div class="profile-info-item">
                <span class="profile-info-label">{{ __('Role') }}</span>
                <span class="profile-info-value">{{ ucfirst($user->type) }}</span>
            </div>
            <div class="profile-info-item">
                <span class="profile-info-label">{{ __('Member Since') }}</span>
                <span class="profile-info-value">{{ $user->created_at->format('d.m.Y') }}</span>
            </div>
        </div>

        {{-- Language Settings --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-language me-2 text-primary"></i>{{ __('Language') }}</h6>
            <div class="d-flex flex-wrap gap-2">
                @php
                    $languages = [
                        'en' => ['name' => 'English', 'flag' => '🇬🇧'],
                        'ru' => ['name' => 'Русский', 'flag' => ''],
                        'uk' => ['name' => 'Українська', 'flag' => '🇺🇦'],
                        'cs' => ['name' => 'Čeština', 'flag' => '🇨🇿'],
                    ];
                    $currentLang = app()->getLocale();
                @endphp
                @foreach($languages as $code => $lang)
                    <a href="{{ route('change.language', $code) }}" 
                       class="btn btn-sm {{ $currentLang == $code ? 'btn-primary' : 'btn-outline-secondary' }}">
                        <span class="me-1">{{ $lang['flag'] }}</span>{{ $lang['name'] }}
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="mobile-card mb-3">
            <h6 class="mb-3"><i class="ti ti-settings me-2 text-primary"></i>{{ __('Actions') }}</h6>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="openEditProfileModal()">
                    <i class="ti ti-pencil me-2"></i>{{ __('Edit Profile') }}
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openChangePasswordModal()">
                    <i class="ti ti-lock me-2"></i>{{ __('Change Password') }}
                </button>
                <a href="{{ route('mobile.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left me-2"></i>{{ __('Back to Dashboard') }}
                </a>
            </div>
        </div>

        {{-- Logout --}}
        <div class="mobile-card mb-3 bg-light">
            <form method="POST" action="{{ route('logout') }}" id="logoutForm">
                @csrf
                <button type="submit" class="btn btn-danger w-100">
                    <i class="ti ti-logout me-2"></i>{{ __('Logout') }}
                </button>
            </form>
        </div>

        {{-- App Info --}}
        <div class="text-center text-muted small mb-3">
            <p class="mb-1">JOBSI Mobile v1.0</p>
            <p class="mb-0">© {{ date('Y') }} JOBSI. {{ __('All rights reserved.') }}</p>
        </div>
    </div>

    {{-- Edit Profile Modal --}}
    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form id="editProfileForm" method="POST" action="{{ route('update.profile') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Edit Profile') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Phone') }}</label>
                            <input type="tel" name="phone" class="form-control" value="{{ $user->phone ?? '' }}">
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Avatar') }}</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                            <small class="text-muted">{{ __('Max 2MB. JPG, PNG, GIF') }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn mobile-btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Change Password Modal --}}
    <div class="modal fade" id="changePasswordModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form id="changePasswordForm" method="POST" action="{{ route('update.password') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Change Password') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Current Password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('New Password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                            <small class="text-muted">{{ __('Minimum 8 characters') }}</small>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Confirm New Password') }} <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn mobile-btn-primary">{{ __('Change Password') }}</button>
                    </div>
                </form>
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
        .btn-primary {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #FF0049;
        }
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-avatar-placeholder {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #FF0049 0%, #ff4d7a 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 600;
        }
        
        .profile-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .profile-info-item:last-child {
            border-bottom: none;
        }
        .profile-info-label {
            color: #666;
            font-size: 14px;
        }
        .profile-info-value {
            font-weight: 500;
            color: #333;
            font-size: 14px;
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
function openEditProfileModal() {
    var modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
    modal.show();
}

function openChangePasswordModal() {
    var modal = new bootstrap.Modal(document.getElementById('changePasswordModal'));
    modal.show();
}

// Handle form submissions
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            show_toastr('success', data.message || '{{ __("Profile updated successfully") }}');
            setTimeout(function() {
                window.location.reload();
            }, 1000);
        } else {
            show_toastr('error', data.error || '{{ __("Error updating profile") }}');
        }
    })
    .catch(error => {
        show_toastr('error', '{{ __("Error updating profile") }}');
    });
});

document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            show_toastr('success', data.message || '{{ __("Password changed successfully") }}');
            bootstrap.Modal.getInstance(document.getElementById('changePasswordModal')).hide();
            form.reset();
        } else {
            show_toastr('error', data.error || '{{ __("Error changing password") }}');
        }
    })
    .catch(error => {
        show_toastr('error', '{{ __("Error changing password") }}');
    });
});
</script>
@endpush
