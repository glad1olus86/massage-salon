@php
    use App\Models\MassageService;
    $regularServices = MassageService::where('created_by', 2)->where('is_active', true)->where('is_extra', false)->ordered()->get();
    $extraServices = MassageService::where('created_by', 2)->where('is_active', true)->where('is_extra', true)->ordered()->get();
@endphp

<link rel="stylesheet" href="{{ asset('infinity/styles/masseuse-register.css') }}">

<div class="modal-wrapper" id="masseuseRegisterModal">
    <div class="modal">
        <button type="button" class="modal-close" onclick="closeMasseuseRegisterModal()" aria-label="Close">
            &times;
        </button>
        
        <h2 class="main-content__title" style="text-align: center;">{{ __('Новый сотрудник') }}</h2>
        
        <form action="{{ route('register.masseuse') }}" method="POST" class="adding-form" id="masseuseRegisterForm" enctype="multipart/form-data">
            @csrf
            
            <!-- Profile Photo Section -->
            <div class="adding-form-section">
                <div class="adding-form__avatar-wrapper">
                    <div class="adding-form__avatar-label">
                        <label for="avatar-input" class="adding-form__avatar-input-label">
                            <input type="file" id="avatar-input" name="avatar" class="adding-form__avatar-input" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewAvatar(event)">
                            <img id="avatar-preview" class="adding-form__avatar-preview" alt="Avatar preview">
                            <img src="{{ asset('infinity/assets/icons/avatar-icon.svg') }}" alt="Upload avatar icon" class="adding-form__avatar-icon" id="avatar-icon">
                            <span class="adding-form__avatar-input-text" id="avatar-text">
                                {{ __('подходящие форматы:') }}<br>jpg, webp, png
                            </span>
                            <button type="button" class="adding-form__avatar-input-button" onclick="document.getElementById('avatar-input').click()">
                                {{ __('загрузить') }}
                            </button>
                        </label>
                    </div>
                    <div class="adding-form__avatar-description">
                        <h3 class="adding-form__title">{{ __('Фотография профиля') }}</h3><br><br>
                        <p class="adding-form__subtitle">
                            {{ __('Ваше фото — это ваша визитная карточка.') }}
                            <br><br>
                            {{ __('Загружайте только качественные и четкие снимки.') }}
                            <br><br>
                            <strong>{{ __('Помните:') }}</strong> {{ __('визуальное оформление профиля напрямую влияет на вашу популярность и успех.') }}
                        </p>
                    </div>
                </div>
                
                <div class="adding-form__bio-wrapper">
                    <input type="text" name="name" class="adding-form__bio-input" placeholder="{{ __('Имя или Псевдоним') }}" required>
                    <input type="tel" name="phone" class="adding-form__bio-input" placeholder="{{ __('Ваш телефон') }}">
                    <input type="email" name="email" class="adding-form__bio-input" placeholder="{{ __('Ваш email') }}" required>
                    <input type="date" name="birth_date" class="adding-form__bio-input" placeholder="{{ __('Дата рождения') }}">
                    <input type="text" name="nationality" class="adding-form__bio-input" placeholder="{{ __('Национальность') }}">
                    <input type="password" name="password" class="adding-form__bio-input" placeholder="{{ __('Пароль') }}" required minlength="8">
                    <input type="number" name="height" class="adding-form__bio-input" placeholder="{{ __('Рост (см)') }}" min="100" max="250">
                    <input type="number" name="weight" class="adding-form__bio-input" placeholder="{{ __('Вес (кг)') }}" min="30" max="200">
                    <input type="number" name="breast_size" class="adding-form__bio-input" placeholder="{{ __('Размер груди') }}" min="0" max="10">
                    <div class="adding-form__languages-wrapper">
                        <label class="adding-form__languages-label">{{ __('Языки') }}</label>
                        <div class="adding-form__languages-grid">
                            <label class="language-option">
                                <input type="checkbox" name="languages[]" value="Čeština" class="language-option__input">
                                <span class="language-option__text">Čeština</span>
                            </label>
                            <label class="language-option">
                                <input type="checkbox" name="languages[]" value="Русский" class="language-option__input">
                                <span class="language-option__text">Русский</span>
                            </label>
                            <label class="language-option">
                                <input type="checkbox" name="languages[]" value="English" class="language-option__input">
                                <span class="language-option__text">English</span>
                            </label>
                            <label class="language-option">
                                <input type="checkbox" name="languages[]" value="Українська" class="language-option__input">
                                <span class="language-option__text">Українська</span>
                            </label>
                            <label class="language-option">
                                <input type="checkbox" name="languages[]" value="Deutsch" class="language-option__input">
                                <span class="language-option__text">Deutsch</span>
                            </label>
                            <label class="language-option">
                                <input type="checkbox" name="languages[]" value="Español" class="language-option__input">
                                <span class="language-option__text">Español</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Photo Gallery Section -->
            <div class="adding-form-section">
                <div class="adding-form-section__header">
                    <h3 class="adding-form__title">{{ __('Фотогаллерея') }}</h3>
                    <span class="adding-form__subtitle">{{ __('загрузите ваши лучшие фотографии') }}</span>
                </div>
                <div class="adding-form__gallery-grid">
                    @for ($i = 0; $i < 8; $i++)
                        <div class="adding-form__gallery-cell">
                            <label class="adding-form__gallery-input-label">
                                <input type="file" name="photos[]" class="adding-form__gallery-input" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewGalleryImage(event, {{ $i }})">
                                <span class="adding-form__gallery-plus" id="gallery-plus-{{ $i }}">
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="24" cy="24" r="23" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4"/>
                                        <path d="M24 14V34M14 24H34" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                                    </svg>
                                </span>
                                <img id="gallery-preview-{{ $i }}" class="adding-form__gallery-preview" alt="Gallery preview">
                            </label>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Services Section -->
            <div class="adding-form-section">
                <div class="adding-form-section__header">
                    <h3 class="adding-form__title">{{ __('Услуги') }}</h3>
                    <span class="adding-form__subtitle">{{ __('выберите услуги которые вы предоставляете') }}</span>
                </div>

                <div class="services-grid">
                    <div class="services-grid__left">
                        <!-- Regular Services -->
                        @if($regularServices->count() > 0)
                            <div class="services-group">
                                <h4 class="services-group__title">{{ __('Основные') }}</h4>
                                <div class="services-group__list">
                                    @foreach($regularServices as $service)
                                        <label class="service-option">
                                            <input class="service-option__input" type="checkbox" name="services[]" value="{{ $service->id }}">
                                            <span class="service-option__icon" aria-hidden="true">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect x="1" y="1" width="22" height="22" rx="4" stroke="#27AE60" stroke-width="2" />
                                                    <path d="M6 12L10 16L18 8" stroke="#27AE60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                            <span class="service-option__text">{{ $service->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Extra Services -->
                        @if($extraServices->count() > 0)
                            <div class="services-group">
                                <h4 class="services-group__title">{{ __('Дополнительные') }}</h4>
                                <div class="services-group__list">
                                    @foreach($extraServices as $service)
                                        <label class="service-option">
                                            <input class="service-option__input" type="checkbox" name="extra_services[]" value="{{ $service->id }}">
                                            <span class="service-option__icon" aria-hidden="true">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <rect x="1" y="1" width="22" height="22" rx="4" stroke="#27AE60" stroke-width="2" />
                                                    <path d="M6 12L10 16L18 8" stroke="#27AE60" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                                </svg>
                                            </span>
                                            <span class="service-option__text">{{ $service->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="services-grid__right">
                        <h4 class="services-group__title">{{ __('Напишите текст о себе') }}</h4>
                        <textarea class="services-about" name="about" aria-label="{{ __('Напишите текст о себе') }}" placeholder="{{ __('Расскажите о себе, своем опыте и преимуществах...') }}"></textarea>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="adding-form-row">
                <input type="hidden" name="is_active" value="1">
                <button type="submit" class="btn btn--brand lg-button">{{ __('Зарегистрироваться') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openMasseuseRegisterModal() {
    document.getElementById('masseuseRegisterModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMasseuseRegisterModal() {
    document.getElementById('masseuseRegisterModal').classList.remove('active');
    document.body.style.overflow = '';
}

function previewAvatar(event) {
    const file = event.target.files[0];
    if (file) {
        console.log('Avatar file selected:', file.name, file.size, 'bytes');
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('avatar-preview');
            preview.src = e.target.result;
            preview.classList.add('active');
            document.getElementById('avatar-icon').style.display = 'none';
            document.getElementById('avatar-text').style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
}

function previewGalleryImage(event, index) {
    const file = event.target.files[0];
    if (file) {
        console.log('Gallery file selected at index', index, ':', file.name, file.size, 'bytes');
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('gallery-preview-' + index);
            const plus = document.getElementById('gallery-plus-' + index);
            preview.src = e.target.result;
            preview.classList.add('active');
            if (plus) plus.style.display = 'none';
        }
        reader.readAsDataURL(file);
    }
}

// Form submission handler
document.getElementById('masseuseRegisterForm').addEventListener('submit', function(e) {
    const formData = new FormData(this);
    
    // Log all form data for debugging
    console.log('=== Form Submission Debug ===');
    for (let [key, value] of formData.entries()) {
        if (value instanceof File) {
            console.log(key + ':', value.name, value.size, 'bytes');
        } else {
            console.log(key + ':', value);
        }
    }
    console.log('=== End Debug ===');
    
    // Let the form submit normally
});

// Close modal on outside click
document.getElementById('masseuseRegisterModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMasseuseRegisterModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMasseuseRegisterModal();
    }
});
</script>
