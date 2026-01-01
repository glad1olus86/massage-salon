@extends('layouts.infinity')

@section('page-title')
    {{ $branch->name }}
@endsection

@section('content')
<section class="branch-show">
    <!-- Header Card -->
    <div class="card">
        <div class="block-header">
            <div class="block-title">
                <span class="block-title__numbers">{{ $branch->address ?? $branch->name }}</span>
            </div>
            <div class="header-actions">
                <a href="{{ route('infinity.branches.index') }}" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    {{ __('Назад') }}
                </a>
                <a href="{{ route('infinity.branches.edit', $branch) }}" class="sm-icon-button" aria-label="{{ __('Редактировать') }}">
                    <img src="{{ asset('infinity/assets/icons/pencil-icon.svg') }}" alt="" class="sm-icon-button__icon">
                </a>
            </div>
        </div>

        <div class="branch-show__info">
            <div class="branch-show__media">
                @if($branch->photos && count($branch->photos) > 0)
                    <img class="branch-show__photo" src="{{ asset('storage/' . $branch->photos[0]) }}" alt="{{ $branch->name }}">
                @else
                    <div class="branch-show__photo-placeholder">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                @endif
            </div>
            <div class="branch-show__details">
                <div class="detail-group">
                    <div class="detail-label">{{ __('Kontakty') }}</div>
                    <div class="detail-value">GSM +WhatsApp</div>
                    <div class="detail-value">{{ $branch->phone ?? '-' }}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">{{ __('Adresa') }}</div>
                    <div class="detail-value">{{ $branch->address ?? '-' }}</div>
                    <div class="detail-value">{{ $branch->name }}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">{{ __('Otevírací doba') }}</div>
                    <div class="detail-value">{{ __('každý den') }}</div>
                    <div class="detail-value">{{ $branch->working_hours ?? '9:00 - 23:00' }}</div>
                </div>
            </div>
            <div class="branch-show__stats">
                <div class="stat-box">
                    <span class="stat-box__value">{{ $branch->rooms->count() }}</span>
                    <span class="stat-box__label">{{ __('Комнат') }}</span>
                </div>
                <div class="stat-box">
                    <span class="stat-box__value">{{ $branch->users->count() }}</span>
                    <span class="stat-box__label">{{ __('Сотрудников') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Rooms Section -->
    <div class="card">
        <div class="block-header">
            <div class="block-title">
                <span class="block-title__numbers">{{ __('Комнаты') }}</span>
            </div>
            <button type="button" class="btn btn--dark sm-button sm-button--dark" onclick="openAddRoomModal()">
                + {{ __('Добавить комнату') }}
            </button>
        </div>

        <div class="rooms-section">
            @if($branch->rooms->count() > 0)
            <div class="rooms-grid">
                @foreach($branch->rooms as $room)
                <div class="room-card">
                    <div class="room-card__photo">
                        @if($room->photo && \Storage::disk('public')->exists($room->photo))
                            <img src="{{ asset('storage/' . $room->photo) }}" alt="{{ __('Комната') }} #{{ $room->room_number }}">
                        @else
                            <div class="room-card__placeholder">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="9" y1="3" x2="9" y2="21"></line>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="room-card__info">
                        <span class="room-card__number">{{ __('Комната') }} #{{ $room->room_number }}</span>
                        <div class="room-card__actions">
                            <button type="button" class="action-btn action-btn--edit" title="{{ __('Редактировать') }}" onclick="openEditRoomModal({{ $room->id }}, '{{ $room->room_number }}', '{{ $room->photo ? asset('storage/' . $room->photo) : '' }}')">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </button>
                            <button type="button" class="action-btn action-btn--delete" title="{{ __('Удалить') }}" onclick="deleteRoom({{ $room->id }})">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="3" x2="9" y2="21"></line>
                </svg>
                <p>{{ __('В этом филиале пока нет комнат') }}</p>
                <button type="button" class="btn btn--brand" onclick="openAddRoomModal()">
                    + {{ __('Добавить комнату') }}
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Employees Section -->
    <div class="card">
        <div class="block-header">
            <div class="block-title">
                <span class="block-title__numbers">{{ __('Сотрудники филиала') }}</span>
            </div>
        </div>

        <div class="employees-section">
            @if($branch->users->count() > 0)
            <ul class="employee-list">
                @foreach($branch->users as $user)
                <li class="employee-item">
                    @if($user->avatar && \Storage::disk('public')->exists($user->avatar))
                        <img class="employee-item__avatar" src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}">
                    @else
                        <div class="employee-item__avatar employee-item__avatar--placeholder">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="employee-item__info">
                        <span class="employee-item__name">{{ $user->name }}</span>
                        <span class="employee-item__email">{{ $user->email }}</span>
                    </div>
                    <form action="{{ route('infinity.branches.remove-user', [$branch, $user]) }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="action-btn action-btn--delete" title="{{ __('Открепить') }}" onclick="return confirm('{{ __('Открепить сотрудника от филиала?') }}')">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    </form>
                </li>
                @endforeach
            </ul>
            @else
            <div class="empty-state empty-state--small">
                <p>{{ __('К этому филиалу не привязаны сотрудники') }}</p>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('css-page')
<style>
.branch-show { margin-top: 20px; }
.branch-show .card { margin-bottom: 20px; }
.header-actions { display: flex; align-items: center; gap: 16px; }
.back-link { display: inline-flex; align-items: center; gap: 8px; color: #fff; text-decoration: none; font-size: 14px; font-weight: 500; opacity: 0.8; transition: opacity 0.2s; }
.back-link:hover { opacity: 1; }
.sm-icon-button { width: 36px; height: 36px; border-radius: 8px; border: none; background: rgba(255, 255, 255, 0.15); cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s; }
.sm-icon-button:hover { background: rgba(255, 255, 255, 0.25); }
.sm-icon-button__icon { width: 18px; height: 18px; }

.branch-show__info { display: grid; grid-template-columns: 200px 1fr auto; gap: 30px; padding: 24px; }
.branch-show__media { width: 200px; height: 150px; border-radius: 12px; overflow: hidden; background: rgba(177, 32, 84, 0.1); }
.branch-show__photo { width: 100%; height: 100%; object-fit: cover; }
.branch-show__photo-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--brand-color); }
.branch-show__details { display: flex; gap: 40px; }
.detail-group { display: flex; flex-direction: column; gap: 4px; }
.detail-label { font-size: 16px; font-weight: 700; color: #333; }
.detail-value { font-size: 15px; color: #666; }
.branch-show__stats { display: flex; flex-direction: column; gap: 12px; }
.stat-box { background: rgba(177, 32, 84, 0.1); border-radius: 12px; padding: 16px 24px; text-align: center; min-width: 100px; }
.stat-box__value { display: block; font-size: 28px; font-weight: 700; color: var(--brand-color); }
.stat-box__label { font-size: 13px; color: #666; }

.rooms-section { padding: 20px; }
.rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; }
.room-card { background: #fff; border: 2px solid var(--brand-color); border-radius: 12px; overflow: hidden; }
.room-card__photo { width: 100%; height: 120px; background: rgba(177, 32, 84, 0.1); }
.room-card__photo img { width: 100%; height: 100%; object-fit: cover; }
.room-card__placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--brand-color); opacity: 0.5; }
.room-card__info { padding: 12px; display: flex; justify-content: space-between; align-items: center; }
.room-card__number { font-size: 15px; font-weight: 600; color: var(--accent-color); }
.room-card__actions { display: flex; gap: 6px; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; }
.action-btn--edit { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.action-btn--edit:hover { background: rgba(59, 130, 246, 0.2); }
.action-btn--delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
.action-btn--delete:hover { background: rgba(239, 68, 68, 0.2); }

.employees-section { padding: 20px; }
.employee-list { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 12px; }
.employee-item { display: flex; align-items: center; gap: 16px; padding: 12px 16px; background: rgba(177, 32, 84, 0.05); border-radius: 10px; }
.employee-item__avatar { width: 44px; height: 44px; border-radius: 10px; object-fit: cover; }
.employee-item__avatar--placeholder { display: flex; align-items: center; justify-content: center; background: rgba(177, 32, 84, 0.15); color: var(--brand-color); font-weight: 700; font-size: 14px; }
.employee-item__info { flex: 1; }
.employee-item__name { display: block; font-weight: 600; color: var(--accent-color); }
.employee-item__email { font-size: 13px; color: #888; }

.empty-state { padding: 60px 20px; text-align: center; color: #888; }
.empty-state svg { margin-bottom: 16px; opacity: 0.5; }
.empty-state p { margin-bottom: 16px; }
.empty-state--small { padding: 30px 20px; }
.btn--brand { background: var(--brand-color); color: #fff; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }

@media (max-width: 900px) {
    .branch-show__info { grid-template-columns: 1fr; }
    .branch-show__details { flex-direction: column; gap: 20px; }
    .branch-show__stats { flex-direction: row; }
}

/* Modal Styles */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-content { background: #fff; border-radius: 16px; width: 100%; max-width: 440px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); }
.modal-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #eee; }
.modal-header h3 { margin: 0; font-size: 18px; font-weight: 700; color: var(--accent-color); }
.modal-close { background: none; border: none; font-size: 28px; color: #999; cursor: pointer; line-height: 1; padding: 0; }
.modal-close:hover { color: #333; }
.modal-body { padding: 24px; }
.modal-footer { display: flex; justify-content: flex-end; gap: 12px; padding: 16px 24px; border-top: 1px solid #eee; }
.form-group { margin-bottom: 16px; }
.form-group:last-child { margin-bottom: 0; }
.form-label { display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 6px; }
.form-label .required { color: #ef4444; }
.form-input { width: 100%; padding: 10px 14px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
.form-input:focus { outline: none; border-color: var(--brand-color); }
.btn--secondary { background: #f3f4f6; color: #374151; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn--secondary:hover { background: #e5e7eb; }
.current-photo-preview { min-height: 40px; display: flex; align-items: center; }
</style>
@endpush

<!-- Add Room Modal -->
<div id="addRoomModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>{{ __('Добавить комнату') }}</h3>
            <button type="button" class="modal-close" onclick="closeAddRoomModal()">&times;</button>
        </div>
        <form action="{{ route('infinity.rooms.store', $branch) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">{{ __('Номер комнаты') }} <span class="required">*</span></label>
                    <input type="text" name="room_number" class="form-input" required placeholder="{{ __('Например: 101') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Фото комнаты') }}</label>
                    <input type="file" name="photo" class="form-input" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeAddRoomModal()">{{ __('Отмена') }}</button>
                <button type="submit" class="btn btn--brand">{{ __('Добавить') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Room Modal -->
<div id="editRoomModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>{{ __('Редактировать комнату') }}</h3>
            <button type="button" class="modal-close" onclick="closeEditRoomModal()">&times;</button>
        </div>
        <form id="editRoomForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">{{ __('Номер комнаты') }} <span class="required">*</span></label>
                    <input type="text" name="room_number" id="edit_room_number" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Текущее фото') }}</label>
                    <div id="edit_current_photo" class="current-photo-preview"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">{{ __('Новое фото') }}</label>
                    <input type="file" name="photo" class="form-input" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn--secondary" onclick="closeEditRoomModal()">{{ __('Отмена') }}</button>
                <button type="submit" class="btn btn--brand">{{ __('Сохранить') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Room Form (hidden) -->
<form id="deleteRoomForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

@push('scripts')
<script>
function openAddRoomModal() {
    document.getElementById('addRoomModal').style.display = 'flex';
}

function closeAddRoomModal() {
    document.getElementById('addRoomModal').style.display = 'none';
}

function openEditRoomModal(roomId, roomNumber, photoUrl) {
    document.getElementById('edit_room_number').value = roomNumber;
    
    var photoPreview = document.getElementById('edit_current_photo');
    if (photoUrl) {
        photoPreview.innerHTML = '<img src="' + photoUrl + '" alt="Current photo" style="max-width: 100%; max-height: 100px; border-radius: 8px;">';
    } else {
        photoPreview.innerHTML = '<span style="color: #888;">{{ __("Нет фото") }}</span>';
    }
    
    document.getElementById('editRoomForm').action = '{{ url("infinitycrm/branches/" . $branch->id . "/rooms") }}/' + roomId;
    document.getElementById('editRoomModal').style.display = 'flex';
}

function closeEditRoomModal() {
    document.getElementById('editRoomModal').style.display = 'none';
}

function deleteRoom(roomId) {
    if (confirm('{{ __("Вы уверены, что хотите удалить эту комнату?") }}')) {
        var form = document.getElementById('deleteRoomForm');
        form.action = '{{ url("infinitycrm/branches/" . $branch->id . "/rooms") }}/' + roomId;
        form.submit();
    }
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(function(modal) {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>
@endpush
