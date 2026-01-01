@php
    $rating = $rating ?? 0;
@endphp
<div class="rating" aria-label="Рейтинг {{ $rating }} из 5">
    @for($star = 1; $star <= 5; $star++)
        @php
            $fill = $star <= floor($rating) ? 100 : ($star - 1 < $rating ? 50 : 0);
        @endphp
        <span class="rating__star" style="--fill: {{ $fill }}%" aria-hidden="true">
            <svg class="rating__star-base" viewBox="0 0 24 24">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
            </svg>
            <svg class="rating__star-fill" viewBox="0 0 24 24">
                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
            </svg>
        </span>
    @endfor
</div>
