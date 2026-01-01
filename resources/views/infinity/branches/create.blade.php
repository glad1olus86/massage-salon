@extends('layouts.infinity')

@section('page-title')
    {{ __('Создать филиал') }}
@endsection

@section('content')
<section class="page-section">
    <div class="card">
        <div class="block-header">
            <div class="block-title">{{ __('Новый филиал') }}</div>
        </div>
        <div class="form-content">
            <form action="{{ route('infinity.branches.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                <div class="form-grid">
                    <!-- Левая колонка - основная информация -->
                    <div class="form-column">
                        <div class="form-group">
                            <label for="name" class="form-label">{{ __('Название') }} <span class="required">*</span></label>
                            <input type="text" id="name" name="name" class="form-input @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" required placeholder="{{ __('Например: Филиал на Hálkova') }}">
                            @error('name')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="address" class="form-label">{{ __('Адрес') }} <span class="required">*</span></label>
                            <input type="text" id="address" name="address" class="form-input @error('address') is-invalid @enderror" 
                                   value="{{ old('address') }}" required placeholder="{{ __('Полный адрес филиала') }}">
                            @error('address')
                                <span class="error-message">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone" class="form-label">{{ __('Телефон') }}</label>
                                <input type="tel" id="phone" name="phone" class="form-input" 
                                       value="{{ old('phone') }}" placeholder="+420 XXX XXX XXX">
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label">{{ __('Email') }}</label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="{{ old('email') }}" placeholder="branch@example.com">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="working_hours" class="form-label">{{ __('Часы работы') }}</label>
                            <input type="text" id="working_hours" name="working_hours" class="form-input" 
                                   value="{{ old('working_hours', '9:00 - 23:00') }}" placeholder="9:00 - 23:00">
                        </div>
                    </div>

                    <!-- Правая колонка - карта -->
                    <div class="form-column">
                        <div class="form-group">
                            <label class="form-label">{{ __('Точка на карте') }}</label>
                            <div class="map-container" id="map"></div>
                            <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', '50.0755') }}">
                            <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', '14.4378') }}">
                            <p class="map-hint">{{ __('Кликните на карту чтобы указать местоположение филиала') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Фото филиала -->
                <div class="form-group form-group--full">
                    <label class="form-label">{{ __('Фото филиала') }} <span class="hint">({{ __('до 10 фото') }})</span></label>
                    <div class="photos-upload" id="photos-upload">
                        <div class="photos-preview" id="photos-preview"></div>
                        <label class="photo-add-btn" id="photo-add-btn">
                            <input type="file" name="photos[]" id="photos-input" multiple accept="image/*" style="display: none;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>{{ __('Добавить фото') }}</span>
                        </label>
                    </div>
                    <p class="photos-hint">{{ __('Рекомендуемый размер: 800x600 px. Форматы: JPG, PNG') }}</p>
                </div>

                <div class="form-actions">
                    <a href="{{ route('infinity.branches.index') }}" class="btn btn--outlined-dark">{{ __('Отмена') }}</a>
                    <button type="submit" class="btn btn--dark">{{ __('Создать филиал') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

@push('css-page')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<style>
.form-content { padding: 30px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
.form-column { display: flex; flex-direction: column; gap: 20px; }
.form-group { margin-bottom: 0; }
.form-group--full { grid-column: 1 / -1; margin-top: 20px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-label { display: block; font-size: 15px; font-weight: 600; color: var(--accent-color); margin-bottom: 8px; }
.form-label .required { color: var(--danger-color); }
.form-label .hint { font-weight: 400; color: #888; font-size: 13px; }
.form-input { width: 100%; padding: 12px 16px; font-size: 16px; border: 2px solid rgba(22, 11, 14, 0.15); border-radius: 10px; background: #fff; transition: border-color 0.2s; }
.form-input:focus { outline: none; border-color: var(--brand-color); }
.error-message { display: block; color: var(--danger-color); font-size: 13px; margin-top: 6px; }

/* Map */
.map-container { width: 100%; height: 300px; border-radius: 12px; overflow: hidden; border: 2px solid rgba(22, 11, 14, 0.15); }
.map-hint { font-size: 13px; color: #888; margin-top: 8px; }

/* Photos */
.photos-upload { display: flex; flex-wrap: wrap; gap: 12px; padding: 16px; background: rgba(177, 32, 84, 0.05); border-radius: 12px; min-height: 120px; }
.photos-preview { display: contents; }
.photo-item { position: relative; width: 100px; height: 100px; border-radius: 10px; overflow: hidden; }
.photo-item img { width: 100%; height: 100%; object-fit: cover; }
.photo-item .photo-remove { position: absolute; top: 4px; right: 4px; width: 24px; height: 24px; background: rgba(239, 68, 68, 0.9); color: #fff; border: none; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; line-height: 1; }
.photo-add-btn { width: 100px; height: 100px; border: 2px dashed var(--brand-color); border-radius: 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; cursor: pointer; color: var(--brand-color); font-size: 12px; transition: all 0.2s; }
.photo-add-btn:hover { background: rgba(177, 32, 84, 0.1); }
.photos-hint { font-size: 13px; color: #888; margin-top: 8px; }

/* Actions */
.form-actions { display: flex; gap: 16px; justify-content: flex-end; margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(22, 11, 14, 0.1); }
.btn { height: 44px; padding: 0 24px; border-radius: 10px; font-size: 16px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; }
.btn--dark { background: var(--accent-color); color: #fff; border: 2px solid var(--accent-color); }
.btn--outlined-dark { background: transparent; color: var(--accent-color); border: 2px solid var(--accent-color); }

@media (max-width: 900px) {
    .form-grid { grid-template-columns: 1fr; }
    .map-container { height: 250px; }
}
@media (max-width: 600px) {
    .form-row { grid-template-columns: 1fr; }
    .form-actions { flex-direction: column; }
    .btn { width: 100%; }
}
</style>
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const defaultLat = {{ old('latitude', 50.0755) }};
    const defaultLng = {{ old('longitude', 14.4378) }};
    
    const map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);
    
    let marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);
    const addressInput = document.getElementById('address');
    
    // Обратный геокодинг - получить адрес по координатам
    function reverseGeocode(lat, lng) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1`)
            .then(r => r.json())
            .then(data => {
                if (data && data.display_name) {
                    // Формируем короткий адрес
                    const addr = data.address;
                    let shortAddress = '';
                    if (addr.road) shortAddress += addr.road;
                    if (addr.house_number) shortAddress += ', ' + addr.house_number;
                    if (addr.city || addr.town || addr.village) {
                        shortAddress += ', ' + (addr.city || addr.town || addr.village);
                    }
                    if (!shortAddress) shortAddress = data.display_name.split(',').slice(0, 3).join(',');
                    addressInput.value = shortAddress;
                }
            })
            .catch(err => console.log('Geocode error:', err));
    }
    
    // Клик по карте
    map.on('click', function(e) {
        marker.setLatLng(e.latlng);
        document.getElementById('latitude').value = e.latlng.lat.toFixed(8);
        document.getElementById('longitude').value = e.latlng.lng.toFixed(8);
        reverseGeocode(e.latlng.lat, e.latlng.lng);
    });
    
    // Перетаскивание маркера
    marker.on('dragend', function(e) {
        const pos = marker.getLatLng();
        document.getElementById('latitude').value = pos.lat.toFixed(8);
        document.getElementById('longitude').value = pos.lng.toFixed(8);
        reverseGeocode(pos.lat, pos.lng);
    });
    
    // Поиск по адресу (прямой геокодинг)
    let searchTimeout;
    addressInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            const query = addressInput.value;
            if (query.length > 5) {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                    .then(r => r.json())
                    .then(data => {
                        if (data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);
                            map.setView([lat, lng], 16);
                            marker.setLatLng([lat, lng]);
                            document.getElementById('latitude').value = lat.toFixed(8);
                            document.getElementById('longitude').value = lng.toFixed(8);
                        }
                    });
            }
        }, 800);
    });
    
    // Загрузка фото
    const photosInput = document.getElementById('photos-input');
    const photosPreview = document.getElementById('photos-preview');
    const photoAddBtn = document.getElementById('photo-add-btn');
    let selectedFiles = [];
    
    photosInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        
        files.forEach(file => {
            if (selectedFiles.length >= 10) return;
            if (!file.type.startsWith('image/')) return;
            
            selectedFiles.push(file);
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'photo-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="photo-remove" data-index="${selectedFiles.length - 1}">&times;</button>
                `;
                photosPreview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
        
        updatePhotosInput();
        updateAddButton();
    });
    
    photosPreview.addEventListener('click', function(e) {
        if (e.target.classList.contains('photo-remove')) {
            const index = parseInt(e.target.dataset.index);
            selectedFiles.splice(index, 1);
            rebuildPreview();
            updatePhotosInput();
            updateAddButton();
        }
    });
    
    function rebuildPreview() {
        photosPreview.innerHTML = '';
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'photo-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview">
                    <button type="button" class="photo-remove" data-index="${index}">&times;</button>
                `;
                photosPreview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
    
    function updatePhotosInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        photosInput.files = dt.files;
    }
    
    function updateAddButton() {
        photoAddBtn.style.display = selectedFiles.length >= 10 ? 'none' : 'flex';
    }
});
</script>
@endpush
