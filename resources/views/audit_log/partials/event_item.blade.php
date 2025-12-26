<div class="d-flex align-items-start mb-3 pb-3 border-bottom">
    <div class="avatar-sm rounded-circle me-3 d-flex align-items-center justify-content-center text-white"
        style="background-color: {{ $log->event_color }}; width: 40px; height: 40px; min-width: 40px;">
        <i class="{{ $log->event_icon }} fs-4"></i>
    </div>
    <div class="flex-grow-1">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-1">{{ $log->translated_description }}</h6>
            <small class="text-muted">{{ \Auth::user()->timeFormat($log->created_at) }}</small>
        </div>
        <p class="mb-1 text-muted small">
            <i class="ti ti-user me-1"></i> {{ $log->user_name }}
        </p>

        @if (!empty($log->old_values) || !empty($log->new_values))
            <button class="btn btn-xs btn-link p-0" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse-{{ $log->id }}" aria-expanded="false">
                {{ __('Show details') }} <i class="ti ti-chevron-down"></i>
            </button>
            <div class="collapse mt-2" id="collapse-{{ $log->id }}">
                <div class="card card-body bg-light mb-0 p-2">
                    <div class="row">
                        @if (!empty($log->old_values))
                            <div class="col-md-6">
                                <strong class="text-danger d-block mb-1">{{ __('Was:') }}</strong>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach ($log->old_values as $key => $val)
                                        <li><span class="text-muted">{{ $key }}:</span>
                                            {{ is_array($val) ? json_encode($val) : $val }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        @if (!empty($log->new_values))
                            <div class="col-md-6">
                                <strong class="text-success d-block mb-1">{{ __('Became:') }}</strong>
                                <ul class="list-unstyled mb-0 small">
                                    @foreach ($log->new_values as $key => $val)
                                        <li><span class="text-muted">{{ $key }}:</span>
                                            {{ is_array($val) ? json_encode($val) : $val }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
