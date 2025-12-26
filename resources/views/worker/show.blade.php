@extends('layouts.admin')

@section('page-title')
    {{ __('Worker Profile') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('worker.index') }}">{{ __('Workers') }}</a></li>
    <li class="breadcrumb-item">{{ $worker->first_name }} {{ $worker->last_name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="theme-avtar bg-primary">
                                    <i class="ti ti-user"></i>
                                </div>
                                <div class="ms-2">
                                    <p class="text-muted text-sm mb-0">{{ __('Full Name') }}</p>
                                    <h5 class="mb-0 mt-1">{{ $worker->first_name }} {{ $worker->last_name }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="theme-avtar bg-info">
                                    <i class="ti ti-calendar"></i>
                                </div>
                                <div class="ms-2">
                                    <p class="text-muted text-sm mb-0">{{ __('Date of Birth') }}</p>
                                    <h5 class="mb-0 mt-1">{{ \Auth::user()->dateFormat($worker->dob) }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="d-flex align-items-start">
                                <div class="theme-avtar bg-warning">
                                    <i class="ti ti-gender-bigender"></i>
                                </div>
                                <div class="ms-2">
                                    <p class="text-muted text-sm mb-0">{{ __('Gender') }}</p>
                                    <h5 class="mb-0 mt-1">{{ $worker->gender == 'male' ? __('Male') : __('Female') }}
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-12 text-center">
                            <h5 class="mb-0">{{ __('Photo') }}</h5>
                            <div class="mt-3">
                                @if (!empty($worker->photo))
                                    <img src="{{ asset('uploads/worker_photos/' . $worker->photo) }}" alt="photo"
                                        class="img-fluid rounded-circle"
                                        style="width: 150px; height: 150px; object-fit: cover;">
                                @else
                                    @php
                                        $workerInitials = mb_strtoupper(mb_substr($worker->first_name, 0, 1) . mb_substr($worker->last_name, 0, 1));
                                    @endphp
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle"
                                        style="width: 150px; height: 150px; background: linear-gradient(135deg, #FF0049 0%, #ff6b8a 100%); color: white; font-weight: 600; font-size: 48px;">
                                        {{ $workerInitials }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Detailed Information') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-group">
                                <h6>{{ __('Nationality') }}</h6>
                                <p class="mb-0">{{ $worker->nationality }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-group">
                                <h6>{{ __('Registration Date') }}</h6>
                                <p class="mb-0">{{ \Auth::user()->dateFormat($worker->registration_date) }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mt-4">
                            <div class="info-group">
                                <h6>{{ __('Phone') }}</h6>
                                <p class="mb-0">{{ !empty($worker->phone) ? $worker->phone : '-' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mt-4">
                            <div class="info-group">
                                <h6>{{ __('Email') }}</h6>
                                <p class="mb-0">{{ !empty($worker->email) ? $worker->email : '-' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6 mt-4">
                            <div class="info-group">
                                <h6>{{ __('Responsible') }}</h6>
                                <p class="mb-0">{{ $worker->responsible ? $worker->responsible->name : '-' }}</p>
                            </div>
                        </div>
                        <div class="col-md-12 mt-4">
                            <div class="info-group">
                                <h6>{{ __('Document Photo') }}</h6>
                                @if (!empty($worker->document_photo))
                                    <div class="mt-2">
                                        <a href="{{ asset('uploads/worker_documents/' . $worker->document_photo) }}"
                                            target="_blank" class="btn btn-sm btn-primary">
                                            <i class="ti ti-file"></i> {{ __('View Document') }}
                                        </a>
                                    </div>
                                @else
                                    <p class="mb-0">-</p>
                                @endif
                            </div>
                        </div>
                        {{-- Generate Document Button --}}
                        @can('document_generate')
                        <div class="col-md-12 mt-4">
                            <div class="info-group">
                                <h6>{{ __('Document Generation') }}</h6>
                                <div class="mt-2">
                                    <a href="#" class="btn btn-sm btn-success"
                                        onclick="event.preventDefault(); $('#generate-document-modal').modal('show');">
                                        <i class="ti ti-file-text"></i> {{ __('Generate Document') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endcan
                    </div>
                </div>
            </div>
        </div>

        {{-- Accommodation Section --}}
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>{{ __('Accommodation') }}</h5>
                    @if ($worker->currentAssignment)
                        <form action="{{ route('worker.unassign.room', $worker->id) }}" method="POST"
                            style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="ti ti-door-exit"></i> {{ __('Check Out') }}
                            </button>
                        </form>
                    @else
                        @can('manage worker')
                            <a href="#" class="btn btn-sm btn-primary"
                                onclick="event.preventDefault(); $('#assign-room-modal').modal('show');">
                                <i class="ti ti-home-plus"></i> {{ __('Check In') }}
                            </a>
                        @endcan
                    @endif
                </div>
                <div class="card-body">
                    @if ($worker->currentAssignment)
                        <div class="row">
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="theme-avtar bg-success">
                                        <i class="ti ti-building"></i>
                                    </div>
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ __('Hotel') }}</p>
                                        <h5 class="mb-0 mt-1">{{ $worker->currentAssignment->hotel->name }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="theme-avtar bg-info">
                                        <i class="ti ti-bed"></i>
                                    </div>
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ __('Room Number') }}</p>
                                        <h5 class="mb-0 mt-1">{{ $worker->currentAssignment->room->room_number }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-start">
                                    <div class="theme-avtar bg-warning">
                                        <i class="ti ti-calendar"></i>
                                    </div>
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ __('Check-in Date') }}</p>
                                        <h5 class="mb-0 mt-1">
                                            {{ \Auth::user()->dateFormat($worker->currentAssignment->check_in_date) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-home-off" style="font-size: 48px; opacity: 0.3;"></i>
                            <h5 class="mt-3">{{ __('Worker is not housed') }}</h5>
                            <p class="text-muted">{{ __('Click "Check In" to assign a room') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Employment Section --}}
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>{{ __('Employment') }}</h5>
                    @if ($worker->currentWorkAssignment)
                        <form action="{{ route('worker.dismiss', $worker->id) }}" method="POST"
                            style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger"
                                onclick="return confirm('{{ __('Are you sure you want to dismiss this worker?') }}')">
                                <i class="ti ti-briefcase-off"></i> {{ __('Dismiss') }}
                            </button>
                        </form>
                    @else
                        @can('manage worker')
                            <a href="#" class="btn btn-sm btn-primary"
                                onclick="event.preventDefault(); $('#assign-work-modal').modal('show');">
                                <i class="ti ti-briefcase-off"></i> {{ __('Assign') }}
                            </a>
                        @endcan
                    @endif
                </div>
                <div class="card-body">
                    @if ($worker->currentWorkAssignment)
                        @php
                            $startDate = \Carbon\Carbon::parse($worker->currentWorkAssignment->started_at);
                            $today = \Carbon\Carbon::now();
                            $daysWorked = max(1, (int) floor($startDate->diffInDays($today)) + 1);

                            // Format for display
                            if ($daysWorked == 1) {
                                $workDuration = '1 ' . __('day');
                            } else {
                                $workDuration = $daysWorked . ' ' . __('days');
                            }

                            $createdBy = \App\Models\User::find($worker->currentWorkAssignment->created_by);
                        @endphp
                        <div class="row">
                            <div class="col-md-3">
                                <div class="d-flex align-items-start">
                                    <div class="theme-avtar bg-primary">
                                        <i class="ti ti-building"></i>
                                    </div>
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ __('Work Place') }}</p>
                                        <h5 class="mb-0 mt-1">{{ $worker->currentWorkAssignment->workPlace->name }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-start">
                                    <div class="theme-avtar bg-secondary">
                                        <i class="ti ti-briefcase"></i>
                                    </div>
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ __('Position') }}</p>
                                        <h5 class="mb-0 mt-1">
                                            {{ $worker->currentWorkAssignment->position->name ?? __('Not specified') }}
                                        </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-start">
                                    <div class="theme-avtar bg-info">
                                        <i class="ti ti-calendar"></i>
                                    </div>
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ __('Employment Date') }}</p>
                                        <h5 class="mb-0 mt-1">
                                            {{ \Auth::user()->dateFormat($worker->currentWorkAssignment->started_at) }}
                                        </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-start">
                                    <div class="theme-avtar bg-success">
                                        <i class="ti ti-clock"></i>
                                    </div>
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ __('Work Duration') }}</p>
                                        <h5 class="mb-0 mt-1">{{ $workDuration }}</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mt-3">
                                <div class="d-flex align-items-start">
                                    <div class="theme-avtar bg-warning">
                                        <i class="ti ti-user-check"></i>
                                    </div>
                                    <div class="ms-2">
                                        <p class="text-muted text-sm mb-0">{{ __('Assigned By') }}</p>
                                        <h5 class="mb-0 mt-1">{{ $createdBy ? $createdBy->name : '-' }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-briefcase-off" style="font-size: 48px; opacity: 0.3;"></i>
                            <h5 class="mt-3">{{ __('Worker is not employed') }}</h5>
                            <p class="text-muted">{{ __('Click "Assign" to assign a workplace') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Activity Feed --}}
        <div class="col-md-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="ti ti-timeline me-2"></i>{{ __('Worker Activity') }}
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            @if ($recentEvents->count() > 0)
                                <span class="badge bg-primary">{{ $recentEvents->count() }} {{ __('events') }}</span>
                            @endif
                            <a href="{{ route('audit.index', ['worker_id' => $worker->id]) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-list me-1"></i>{{ __('View All Events') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if ($recentEvents->count() > 0)
                        <div class="timeline">
                            @foreach ($recentEvents as $event)
                                <div class="timeline-item mb-3 pb-3 border-bottom">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="timeline-icon rounded-circle flex-shrink-0"
                                            style="width: 40px; height: 40px; background-color: {{ $event->event_color }}; display: flex; align-items: center; justify-content: center;">
                                            <i class="{{ $event->event_icon }} text-white"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">{{ $event->translated_description }}</h6>
                                                    <p class="text-muted small mb-0">
                                                        <i class="ti ti-user me-1"></i>{{ $event->user_name }}
                                                        <span class="mx-2">•</span>
                                                        <i
                                                            class="ti ti-clock me-1"></i>{{ \Auth::user()->timeFormat($event->created_at) }}
                                                    </p>
                                                </div>
                                            </div>
                                            @if (!empty($event->old_values) || !empty($event->new_values))
                                                <button class="btn btn-sm btn-link p-0 mt-2 text-decoration-none"
                                                    type="button" data-bs-toggle="collapse"
                                                    data-bs-target="#event-details-{{ $event->id }}">
                                                    <i class="ti ti-chevron-down me-1"></i>{{ __('Show Details') }}
                                                </button>
                                                <div class="collapse mt-2" id="event-details-{{ $event->id }}">
                                                    <div class="card card-body bg-light p-2 small">
                                                        @if (!empty($event->old_values))
                                                            <div class="mb-2">
                                                                <strong>{{ __('Old Values') }}:</strong>
                                                                <div class="ms-2">
                                                                    @foreach ($event->old_values as $key => $value)
                                                                        <div>{{ ucfirst($key) }}:
                                                                            <code>{{ $value }}</code></div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                        @if (!empty($event->new_values))
                                                            <div>
                                                                <strong>{{ __('New Values') }}:</strong>
                                                                <div class="ms-2">
                                                                    @foreach ($event->new_values as $key => $value)
                                                                        <div>{{ ucfirst($key) }}:
                                                                            <code>{{ $value }}</code></div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-timeline-event-x" style="font-size: 48px; opacity: 0.3;"></i>
                            <h5 class="mt-3">{{ __('No Activity') }}</h5>
                            <p class="text-muted">{{ __('Worker activity history is empty') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>


        {{-- Room Assignment Modal --}}
        <div class="modal fade" id="assign-room-modal" tabindex="-1" role="dialog"
            aria-labelledby="assign-room-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assign-room-modal-label">{{ __('Check In Worker') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('worker.assign.room', $worker->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @if($hotels->isEmpty())
                                <div class="text-center py-4">
                                    <i class="ti ti-building-community" style="font-size: 48px; opacity: 0.3;"></i>
                                    <h5 class="mt-3">{{ __('No Hotels Available') }}</h5>
                                    <p class="text-muted">{{ __('You have no assigned hotels. Contact your manager.') }}</p>
                                </div>
                            @else
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="hotel_id"
                                            class="form-label">{{ __('Hotel') }}</label><x-required></x-required>
                                        <select name="hotel_id" id="hotel_id" class="form-control" required>
                                            <option value="" selected>{{ __('Select Hotel') }}</option>
                                            @foreach ($hotels as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="form-group">
                                        <label for="room_id"
                                            class="form-label">{{ __('Room') }}</label><x-required></x-required>
                                        <select name="room_id" id="room_id" class="form-control" required disabled>
                                            <option value="">{{ __('First select a hotel') }}</option>
                                        </select>
                                        <small class="form-text text-muted" id="room-capacity-info"></small>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="form-group p-3 bg-light rounded">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="worker_pays_checkbox" name="worker_pays" value="1">
                                            <label class="form-check-label fw-bold" for="worker_pays_checkbox">
                                                {{ __('Worker pays for accommodation') }}
                                            </label>
                                        </div>
                                        <div id="payment_amount_wrapper" style="display: none;" class="mt-2">
                                            <label class="form-label">{{ __('Monthly payment amount') }}</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" name="payment_amount" id="payment_amount_input" 
                                                       step="0.01" min="0" placeholder="0.00">
                                                <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                            </div>
                                            <small class="text-muted" id="room_price_hint"></small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Check In') }}</button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Work Assignment Modal --}}
        <div class="modal fade" id="assign-work-modal" tabindex="-1" role="dialog"
            aria-labelledby="assign-work-modal-label" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assign-work-modal-label">{{ __('Assign Worker') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('worker.assign.work', $worker->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @if($workPlaces->isEmpty())
                                <div class="text-center py-4">
                                    <i class="ti ti-briefcase-off" style="font-size: 48px; opacity: 0.3;"></i>
                                    <h5 class="mt-3">{{ __('No Work Places Available') }}</h5>
                                    <p class="text-muted">{{ __('You have no assigned work places. Contact your manager.') }}</p>
                                </div>
                            @else
                            <div class="row">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="work_place_id"
                                            class="form-label">{{ __('Work Place') }}</label><x-required></x-required>
                                        <select name="work_place_id" id="work_place_id" class="form-control" required>
                                            <option value="" selected>{{ __('Select Work Place') }}</option>
                                            @foreach ($workPlaces as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="form-group">
                                        <label for="position_id"
                                            class="form-label">{{ __('Position') }}</label><x-required></x-required>
                                        <select name="position_id" id="position_id" class="form-control" required disabled>
                                            <option value="">{{ __('First select a work place') }}</option>
                                        </select>
                                        <small class="form-text text-muted" id="position-auto-info"></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Assign') }}</button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>

        {{-- Document Generation Modal --}}
        @can('document_generate')
            @include('documents.generate_modal')
        @endcan
    @endsection

    @push('script-page')
        <script>
            $(document).ready(function() {
                $('#hotel_id').on('change', function() {
                    var hotelId = $(this).val();
                    var roomSelect = $('#room_id');
                    var capacityInfo = $('#room-capacity-info');

                    if (hotelId) {
                        $.ajax({
                            url: '/hotel/' + hotelId + '/available-rooms',
                            type: 'GET',
                            success: function(rooms) {
                                roomSelect.empty();
                                roomSelect.prop('disabled', false);

                                if (rooms.length === 0) {
                                    roomSelect.append(
                                        '<option value="">{{ __('No available rooms') }}</option>'
                                    );
                                    roomSelect.prop('disabled', true);
                                    return;
                                }

                                roomSelect.append(
                                    '<option value="">{{ __('Select Room') }}</option>');

                                // Group rooms by occupancy status
                                var emptyRooms = rooms.filter(r => r.occupied === 0);
                                var partialRooms = rooms.filter(r => r.occupied > 0 && !r.is_full);
                                var fullRooms = rooms.filter(r => r.is_full);

                                // Add empty rooms
                                if (emptyRooms.length > 0) {
                                    roomSelect.append('<optgroup label="━━━ {{ __('Empty rooms') }} ━━━">');
                                    emptyRooms.forEach(function(room) {
                                        var optionText = '{{ __('Room') }} ' + room.room_number +
                                            ' (0/' + room.capacity + ')';
                                        roomSelect.append('<option value="' + room.id + '">' + optionText + '</option>');
                                    });
                                    roomSelect.append('</optgroup>');
                                }

                                // Add partially occupied rooms
                                if (partialRooms.length > 0) {
                                    roomSelect.append('<optgroup label="━━━ {{ __('Partially occupied') }} ━━━">');
                                    partialRooms.forEach(function(room) {
                                        var optionText = '{{ __('Room') }} ' + room.room_number +
                                            ' (' + room.occupied + '/' + room.capacity + ')';
                                        roomSelect.append('<option value="' + room.id + '">' + optionText + '</option>');
                                    });
                                    roomSelect.append('</optgroup>');
                                }

                                // Add full rooms (disabled)
                                if (fullRooms.length > 0) {
                                    roomSelect.append('<optgroup label="━━━ {{ __('Full') }} ━━━">');
                                    fullRooms.forEach(function(room) {
                                        var optionText = '{{ __('Room') }} ' + room.room_number +
                                            ' (' + room.occupied + '/' + room.capacity + ') - {{ __('Full') }}';
                                        roomSelect.append('<option value="' + room.id + '" disabled>' + optionText + '</option>');
                                    });
                                    roomSelect.append('</optgroup>');
                                }
                            },
                            error: function() {
                                roomSelect.empty();
                                roomSelect.append(
                                    '<option value="">{{ __('Error loading rooms') }}</option>'
                                );
                                roomSelect.prop('disabled', true);
                            }
                        });
                    } else {
                        roomSelect.empty();
                        roomSelect.append('<option value="">{{ __('First select a hotel') }}</option>');
                        roomSelect.prop('disabled', true);
                        capacityInfo.text('');
                    }
                });

                $('#room_id').on('change', function() {
                    var selectedOption = $(this).find('option:selected');
                    var capacityInfo = $('#room-capacity-info');
                    var roomPriceHint = $('#room_price_hint');
                    var paymentAmountInput = $('#payment_amount_input');

                    if ($(this).val() && !selectedOption.is(':disabled')) {
                        capacityInfo.text('{{ __('Room is available for check-in') }}');
                        capacityInfo.removeClass('text-danger').addClass('text-success');
                        
                        // Load room price
                        var roomId = $(this).val();
                        $.ajax({
                            url: '/room/' + roomId,
                            type: 'GET',
                            headers: { 'Accept': 'application/json' },
                            success: function(room) {
                                if (room && room.monthly_price) {
                                    roomPriceHint.text('{{ __('Room price') }}: ' + room.monthly_price_formatted);
                                    paymentAmountInput.val(room.monthly_price);
                                }
                            }
                        });
                    } else {
                        capacityInfo.text('');
                        roomPriceHint.text('');
                    }
                });

                // Worker pays checkbox toggle
                $('#worker_pays_checkbox').on('change', function() {
                    var paymentWrapper = $('#payment_amount_wrapper');
                    if ($(this).is(':checked')) {
                        paymentWrapper.slideDown();
                    } else {
                        paymentWrapper.slideUp();
                    }
                });

                // Load positions when work place is selected
                $('#work_place_id').on('change', function() {
                    var workPlaceId = $(this).val();
                    var positionSelect = $('#position_id');
                    var positionInfo = $('#position-auto-info');

                    if (workPlaceId) {
                        positionSelect.prop('disabled', true);
                        positionSelect.empty();
                        positionSelect.append('<option value="">{{ __('Loading...') }}</option>');
                        positionInfo.text('');

                        $.ajax({
                            url: '/work-place/' + workPlaceId + '/positions/json',
                            type: 'GET',
                            success: function(positions) {
                                positionSelect.empty();
                                
                                if (positions.length === 0) {
                                    positionSelect.append('<option value="">{{ __('No positions') }}</option>');
                                    positionInfo.text('{{ __('First create positions for this work place') }}');
                                    positionInfo.removeClass('text-success').addClass('text-warning');
                                } else if (positions.length === 1) {
                                    // Auto-select if only one position
                                    positionSelect.append('<option value="' + positions[0].id + '" selected>' + positions[0].name + '</option>');
                                    positionInfo.text('{{ __('Position selected automatically') }}');
                                    positionInfo.removeClass('text-warning').addClass('text-success');
                                    positionSelect.prop('disabled', false);
                                } else {
                                    positionSelect.append('<option value="">{{ __('Select Position') }}</option>');
                                    $.each(positions, function(i, position) {
                                        positionSelect.append('<option value="' + position.id + '">' + position.name + '</option>');
                                    });
                                    positionInfo.text('');
                                    positionSelect.prop('disabled', false);
                                }
                            },
                            error: function() {
                                positionSelect.empty();
                                positionSelect.append('<option value="">{{ __('Loading error') }}</option>');
                                positionInfo.text('');
                            }
                        });
                    } else {
                        positionSelect.empty();
                        positionSelect.append('<option value="">{{ __('First select a work place') }}</option>');
                        positionSelect.prop('disabled', true);
                        positionInfo.text('');
                    }
                });
            });
        </script>
    @endpush
