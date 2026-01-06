@extends('layouts.masseuse')

@section('page-title')
    {{ __('Мой профиль') }}
@endsection

@section('content')
<div class="form-page">
    <form action="{{ route('masseuse.profile.update') }}" method="POST" enctype="multipart/form-data" class="form-card">
        @csrf
        @method('PUT')
        
        <!-- Avatar Section -->
        <div class="avatar-section">
            <div class="avatar-preview" id="avatarPreview">
                @if($user->avatar && Storage::disk('public')->exists($user->avatar))
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" id="avatarImage">
                @else
                    <span class="avatar-initials">{{ mb_substr($user->name, 0, 2) }}</span>
                @endif
            </div>
            <div class="avatar-upload">
                <label for="avatar" class="btn btn--outlined-dark btn--sm">{{ __('Загрузить фото') }}</label>
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp" style="display: none;">
                <p class="avatar-hint">{{ __('JPEG, PNG или WebP. Максимум 2MB.') }}</p>
            </div>
        </div>
        
        <div class="form-grid">
            <div class="form-group">
                <label for="name" class="form-label">{{ __('Имя') }} *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" class="form-input" required>
            </div>
            
            <div class="form-group">
                <label for="email" class="form-label">{{ __('Email') }}</label>
                <input type="email" id="email" value="{{ $user->email }}" class="form-input" disabled>
                <p class="form-hint">{{ __('Для изменения email обратитесь к администратору') }}</p>
            </div>
            
            <div class="form-group">
                <label for="phone" class="form-label">{{ __('Телефон') }}</label>
                <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->company_phone) }}" class="form-input">
            </div>
            
            <div class="form-group">
                <label class="form-label">{{ __('Роль') }}</label>
                <input type="text" value="{{ __('Массажист') }}" class="form-input" disabled>
            </div>
            
            <div class="form-group form-group--full">
                <label for="bio" class="form-label">{{ __('О себе') }}</label>
                <textarea id="bio" name="bio" class="form-textarea" rows="4" placeholder="{{ __('Расскажите немного о себе...') }}">{{ old('bio', $user->bio ?? '') }}</textarea>
            </div>
        </div>
        
        <!-- Gallery Section -->
        <div class="profile-section">
            <h3 class="section-title">{{ __('Галерея') }} <span class="section-hint">({{ __('до 8 фото') }})</span></h3>
            <div class="gallery-grid" id="galleryGrid">
                @if($user->photos)
                    @foreach($user->photos as $index => $photo)
                        @if(Storage::disk('public')->exists($photo))
                        <div class="gallery-item" data-photo="{{ $photo }}">
                            <img src="{{ asset('storage/' . $photo) }}" alt="Photo {{ $index + 1 }}">
                            <button type="button" class="gallery-remove" onclick="removePhoto(this)">×</button>
                            <input type="hidden" name="existing_photos[]" value="{{ $photo }}">
                        </div>
                        @endif
                    @endforeach
                @endif
                <label class="gallery-add" id="galleryAdd">
                    <input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple style="display: none;" onchange="previewPhotos(this)">
                    <span class="gallery-add-icon">+</span>
                </label>
            </div>
            <p class="form-hint">{{ __('Максимум 5MB на фото. JPEG, PNG или WebP.') }}</p>
        </div>

        <!-- Services Section -->
        <div class="profile-section">
            <h3 class="section-title">{{ __('Мои услуги') }}</h3>
            
            <div class="services-columns">
                <div class="services-column">
                    <h4 class="services-column-title">{{ __('Основные услуги') }}</h4>
                    <div class="services-list">
                        @forelse($regularServices as $service)
                        <label class="service-checkbox">
                            <input type="checkbox" name="services[]" value="{{ $service->id }}" 
                                {{ $user->massageServices->where('id', $service->id)->where('pivot.is_extra', false)->count() ? 'checked' : '' }}>
                            <span class="service-checkbox-box"></span>
                            <span class="service-name">{{ $service->name }}</span>
                            <span class="service-price">{{ number_format($service->price, 0, ',', ' ') }} CZK</span>
                        </label>
                        @empty
                        <p class="services-empty">{{ __('Нет доступных услуг') }}</p>
                        @endforelse
                    </div>
                </div>
                
                <div class="services-column">
                    <h4 class="services-column-title">{{ __('Экстра услуги') }}</h4>
                    <div class="services-list">
                        @forelse($extraServices as $service)
                        <label class="service-checkbox service-checkbox--extra">
                            <input type="checkbox" name="extra_services[]" value="{{ $service->id }}" 
                                {{ $user->massageServices->where('id', $service->id)->where('pivot.is_extra', true)->count() ? 'checked' : '' }}>
                            <span class="service-checkbox-box"></span>
                            <span class="service-name">{{ $service->name }}</span>
                            <span class="service-price">{{ number_format($service->price, 0, ',', ' ') }} CZK</span>
                        </label>
                        @empty
                        <p class="services-empty">{{ __('Нет экстра услуг') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <div style="flex: 1;"></div>
            <button type="submit" class="btn btn--dark">{{ __('Сохранить изменения') }}</button>
        </div>
    </form>
</div>
@endsection

@push('css-page')
<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse.css') }}">
<style>
/* Gallery */
.gallery-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 8px;
}
.gallery-item {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: 8px;
    overflow: hidden;
}
.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.gallery-remove {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    background: rgba(0,0,0,0.6);
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
}
.gallery-add {
    width: 100px;
    height: 100px;
    border: 2px dashed #ccc;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color 0.2s;
}
.gallery-add:hover {
    border-color: var(--brand-color, #8B1538);
}
.gallery-add-icon {
    font-size: 32px;
    color: #999;
}

/* Services */
.profile-section {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #eee;
}
.section-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
}
.section-hint {
    font-weight: 400;
    color: #666;
    font-size: 14px;
}
.services-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
}
@media (max-width: 768px) {
    .services-columns {
        grid-template-columns: 1fr;
    }
}
.services-column-title {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #333;
}
.services-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}
.service-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}
.service-checkbox:hover {
    border-color: #999;
}
.service-checkbox input {
    display: none;
}
.service-checkbox-box {
    width: 20px;
    height: 20px;
    border: 2px solid #ccc;
    border-radius: 4px;
    flex-shrink: 0;
    position: relative;
}
.service-checkbox input:checked + .service-checkbox-box {
    background: var(--brand-color, #8B1538);
    border-color: var(--brand-color, #8B1538);
}
.service-checkbox input:checked + .service-checkbox-box::after {
    content: '✓';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 12px;
}
.service-checkbox--extra .service-checkbox-box {
    border-color: #d4a574;
}
.service-checkbox--extra input:checked + .service-checkbox-box {
    background: #d4a574;
    border-color: #d4a574;
}
.service-name {
    flex: 1;
}
.service-price {
    font-weight: 500;
    color: #666;
}
.service-checkbox--disabled {
    opacity: 0.4;
    pointer-events: none;
    background: #f5f5f5;
}
.services-empty {
    color: #999;
    font-size: 14px;
    padding: 12px;
    text-align: center;
}
</style>
@endpush

@push('scripts')
<script>
// Avatar preview
document.getElementById('avatar').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').innerHTML = '<img src="' + e.target.result + '" alt="Preview" id="avatarImage">';
        };
        reader.readAsDataURL(file);
    }
});

// Gallery functions
function removePhoto(btn) {
    btn.closest('.gallery-item').remove();
    updateGalleryAddVisibility();
}

function previewPhotos(input) {
    const files = Array.from(input.files);
    const grid = document.getElementById('galleryGrid');
    const addBtn = document.getElementById('galleryAdd');
    const currentCount = grid.querySelectorAll('.gallery-item').length;
    
    files.forEach((file, index) => {
        if (currentCount + index >= 8) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const div = document.createElement('div');
            div.className = 'gallery-item gallery-item--new';
            div.innerHTML = `
                <img src="${e.target.result}" alt="New photo">
                <button type="button" class="gallery-remove" onclick="removePhoto(this)">×</button>
            `;
            grid.insertBefore(div, addBtn);
            updateGalleryAddVisibility();
        };
        reader.readAsDataURL(file);
    });
}

function updateGalleryAddVisibility() {
    const grid = document.getElementById('galleryGrid');
    const addBtn = document.getElementById('galleryAdd');
    const count = grid.querySelectorAll('.gallery-item').length;
    addBtn.style.display = count >= 8 ? 'none' : 'flex';
}
</script>
@endpush
