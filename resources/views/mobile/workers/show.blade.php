@extends('layouts.mobile')

@php use App\Services\NationalityFlagService; @endphp

@section('content')
    {{-- Header --}}
    <div class="mobile-header">
        <div class="mobile-header-row">
            {{-- Left side: Back + Notifications --}}
            <div class="mobile-header-left">
                <a href="{{ route('mobile.workers.index') }}" class="mobile-header-btn">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#FF0049" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7" />
                    </svg>
                </a>
                <a href="{{ route('notifications.index') }}" class="mobile-header-btn">
                    <img src="{{ asset('fromfigma/bell_mobile.svg') }}" alt=""
                        onerror="this.outerHTML='<svg width=24 height=24 viewBox=\'0 0 24 24\' fill=none stroke=#FF0049 stroke-width=2><path d=\'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\'></path><path d=\'M13.73 21a2 2 0 0 1-3.46 0\'></path></svg>'">
                </a>
            </div>

            {{-- Right side: Language + Logo --}}
            <div class="mobile-header-right">
                <div class="dropdown">
                    <button class="mobile-lang-btn" data-bs-toggle="dropdown">
                        @php $lang = app()->getLocale(); @endphp
                        @if ($lang == 'cs')
                            <img src="{{ asset('fromfigma/czech_flag.svg') }}" alt="CS" class="mobile-flag">
                        @elseif ($lang == 'uk')
                            <img src="{{ asset('fromfigma/ukraine_flag.png') }}" alt="UK" class="mobile-flag">
                        @elseif ($lang == 'ru')
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#666"
                                stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="2" y1="12" x2="22" y2="12"></line>
                                <path
                                    d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z">
                                </path>
                            </svg>
                        @else
                            <img src="{{ asset('fromfigma/uk_flag.png') }}" alt="EN" class="mobile-flag">
                        @endif
                        <span>{{ strtoupper($lang) }}</span>
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="#000">
                            <path d="M7 10l5 5 5-5z" />
                        </svg>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        @foreach (['ru' => 'Русский', 'en' => 'English', 'cs' => 'Čeština', 'uk' => 'Українська'] as $code => $language)
                            <a href="{{ route('change.language', $code) }}"
                                class="dropdown-item {{ $lang == $code ? 'text-primary' : '' }}">{{ $language }}</a>
                        @endforeach
                    </div>
                </div>
                <div class="mobile-logo">
                    <img src="{{ asset('fromfigma/jobsi_mobile.png') }}" alt="JOBSI">
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-content">
        {{-- Worker Photo & Name --}}
        <div class="text-center mb-4">
            <div class="mobile-profile-avatar mx-auto mb-3">
                @if (!empty($worker->photo))
                    <img src="{{ asset('uploads/worker_photos/' . $worker->photo) }}" alt="">
                @else
                    <div class="mobile-avatar-placeholder-lg">
                        {{ strtoupper(substr($worker->first_name, 0, 1)) }}{{ strtoupper(substr($worker->last_name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <h4 class="mb-1">{{ $worker->first_name }} {{ $worker->last_name }}</h4>
            <div class="d-flex justify-content-center gap-2">
                @if ($worker->currentWorkAssignment)
                    <span class="mobile-badge mobile-badge-working">{{ __('Working') }}</span>
                @else
                    <span class="mobile-badge mobile-badge-not-working">{{ __('Not Working') }}</span>
                @endif
                @if ($worker->currentAssignment)
                    <span class="mobile-badge mobile-badge-housed">{{ __('Housed') }}</span>
                @else
                    <span class="mobile-badge mobile-badge-not-housed">{{ __('Not Housed') }}</span>
                @endif
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex gap-2 mb-4">
            @can('edit worker')
                <a href="#" data-url="{{ route('worker.edit', $worker->id) }}" data-ajax-popup="true"
                    data-title="{{ __('Edit Worker') }}" data-size="lg" class="btn btn-outline-primary flex-grow-1">
                    <i class="ti ti-pencil me-1"></i>{{ __('Edit') }}
                </a>
            @endcan
            @can('document_generate')
                <a href="#" class="btn btn-outline-success flex-grow-1" data-bs-toggle="modal"
                    data-bs-target="#generateDocModal">
                    <i class="ti ti-file-text me-1"></i>{{ __('Document') }}
                </a>
            @endcan
        </div>

        {{-- Personal Info Card --}}
        <div class="mobile-info-card mb-3">
            <h6 class="mobile-info-title"><i class="ti ti-user me-2"></i>{{ __('Personal Information') }}</h6>
            <div class="mobile-info-row">
                <span class="mobile-info-label">{{ __('Date of Birth') }}</span>
                <span class="mobile-info-value">{{ \Auth::user()->dateFormat($worker->dob) }}</span>
            </div>
            <div class="mobile-info-row">
                <span class="mobile-info-label">{{ __('Gender') }}</span>
                <span class="mobile-info-value">{{ $worker->gender == 'male' ? __('Male') : __('Female') }}</span>
            </div>
            <div class="mobile-info-row">
                <span class="mobile-info-label">{{ __('Nationality') }}</span>
                <span class="mobile-info-value">{!! NationalityFlagService::getFlagHtml($worker->nationality, 18) !!}{{ $worker->nationality }}</span>
            </div>
            <div class="mobile-info-row">
                <span class="mobile-info-label">{{ __('Registration Date') }}</span>
                <span class="mobile-info-value">{{ \Auth::user()->dateFormat($worker->registration_date) }}</span>
            </div>
            @if ($worker->phone)
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Phone') }}</span>
                    <span class="mobile-info-value"><a href="tel:{{ $worker->phone }}">{{ $worker->phone }}</a></span>
                </div>
            @endif
            @if ($worker->email)
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Email') }}</span>
                    <span class="mobile-info-value"><a href="mailto:{{ $worker->email }}">{{ $worker->email }}</a></span>
                </div>
            @endif
        </div>

        {{-- Accommodation Card --}}
        <div class="mobile-info-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mobile-info-title mb-0"><i class="ti ti-home me-2"></i>{{ __('Accommodation') }}</h6>
                @if ($worker->currentAssignment)
                    <form action="{{ route('worker.unassign.room', $worker->id) }}" method="POST"
                        style="display: inline;">
                        @csrf
                        <input type="hidden" name="redirect_to" value="mobile">
                        <button type="submit" class="btn btn-sm btn-outline-danger"
                            onclick="return confirm('{{ __('Are you sure?') }}')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2">
                                </path>
                                <path d="M7 12h14l-3 -3m0 6l3 -3"></path>
                            </svg>
                        </button>
                    </form>
                @else
                    @can('manage worker')
                        <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#assignRoomModal">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2">
                                <path d="M5 12l-2 0l9 -9l9 9l-2 0"></path>
                                <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"></path>
                                <path d="M10 12h4"></path>
                                <path d="M12 10v4"></path>
                            </svg>
                        </a>
                    @endcan
                @endif
            </div>
            @if ($worker->currentAssignment)
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Hotel') }}</span>
                    <span class="mobile-info-value">{{ $worker->currentAssignment->hotel->name }}</span>
                </div>
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Room') }}</span>
                    <span class="mobile-info-value">{{ $worker->currentAssignment->room->room_number }}</span>
                </div>
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Check-in Date') }}</span>
                    <span
                        class="mobile-info-value">{{ \Auth::user()->dateFormat($worker->currentAssignment->check_in_date) }}</span>
                </div>
            @else
                <div class="text-center py-3 text-muted">
                    <i class="ti ti-home-off" style="font-size: 32px; opacity: 0.5;"></i>
                    <p class="mb-0 mt-2">{{ __('Not housed') }}</p>
                </div>
            @endif
        </div>

        {{-- Employment Card --}}
        <div class="mobile-info-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mobile-info-title mb-0"><i class="ti ti-briefcase me-2"></i>{{ __('Employment') }}</h6>
                @if ($worker->currentWorkAssignment)
                    <form action="{{ route('worker.dismiss', $worker->id) }}" method="POST" style="display: inline;">
                        @csrf
                        <input type="hidden" name="redirect_to" value="mobile">
                        <button type="submit" class="btn btn-sm btn-outline-warning"
                            onclick="return confirm('{{ __('Are you sure?') }}')">
                            <i class="ti ti-user-off"></i>
                        </button>
                    </form>
                @else
                    @can('manage worker')
                        <a href="#" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                            data-bs-target="#assignWorkModal">
                            <i class="ti ti-briefcase"></i>
                        </a>
                    @endcan
                @endif
            </div>
            @if ($worker->currentWorkAssignment)
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Work Place') }}</span>
                    <span class="mobile-info-value">{{ $worker->currentWorkAssignment->workPlace->name }}</span>
                </div>
                @if ($worker->currentWorkAssignment->position)
                    <div class="mobile-info-row">
                        <span class="mobile-info-label">{{ __('Position') }}</span>
                        <span class="mobile-info-value">{{ $worker->currentWorkAssignment->position->name }}</span>
                    </div>
                @endif
                <div class="mobile-info-row">
                    <span class="mobile-info-label">{{ __('Start Date') }}</span>
                    <span
                        class="mobile-info-value">{{ \Auth::user()->dateFormat($worker->currentWorkAssignment->started_at) }}</span>
                </div>
            @else
                <div class="text-center py-3 text-muted">
                    <i class="ti ti-briefcase-off" style="font-size: 32px; opacity: 0.5;"></i>
                    <p class="mb-0 mt-2">{{ __('Not employed') }}</p>
                </div>
            @endif
        </div>

        {{-- Document Photo --}}
        @if (!empty($worker->document_photo))
            <div class="mobile-info-card mb-3">
                <h6 class="mobile-info-title"><i class="ti ti-file me-2"></i>{{ __('Document') }}</h6>
                <a href="{{ asset('uploads/worker_documents/' . $worker->document_photo) }}" target="_blank"
                    class="btn btn-outline-primary w-100">
                    <i class="ti ti-external-link me-1"></i>{{ __('View Document') }}
                </a>
            </div>
        @endif

        {{-- Delete Worker Button --}}
        @can('delete worker')
            <div class="mt-4 mb-3">
                <form action="{{ route('worker.destroy', $worker->id) }}" method="POST" id="delete-worker-form">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="redirect_to" value="mobile">
                    <button type="button" class="btn mobile-btn-danger w-100" onclick="confirmDeleteWorker()">
                        <i class="ti ti-trash me-2"></i>{{ __('Delete Worker') }}
                    </button>
                </form>
            </div>
        @endcan
    </div>

    {{-- Assign Room Modal --}}
    @php
        $hotels = \App\Models\Hotel::where('created_by', Auth::user()->creatorId())->pluck('name', 'id');
    @endphp
    <div class="modal fade" id="assignRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Check In Worker') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('worker.assign.room', $worker->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="redirect_to" value="mobile">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Hotel') }} <span class="text-danger">*</span></label>
                            <select name="hotel_id" id="mobileHotelSelect" class="form-control" required>
                                <option value="">{{ __('Select Hotel') }}</option>
                                @foreach ($hotels as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Room') }} <span class="text-danger">*</span></label>
                            <select name="room_id" id="mobileRoomSelect" class="form-control" required disabled>
                                <option value="">{{ __('First select a hotel') }}</option>
                            </select>
                        </div>
                        <div class="form-group p-3 bg-light rounded">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="mobileWorkerPaysCheckbox" name="worker_pays" value="1">
                                <label class="form-check-label fw-bold" for="mobileWorkerPaysCheckbox">
                                    {{ __('Worker pays for accommodation') }}
                                </label>
                            </div>
                            <div id="mobilePaymentAmountWrapper" style="display: none;" class="mt-2">
                                <label class="form-label">{{ __('Monthly payment amount') }}</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="payment_amount" id="mobilePaymentAmountInput" 
                                           step="0.01" min="0" placeholder="0.00">
                                    <span class="input-group-text">{{ getCashboxCurrencySymbol() }}</span>
                                </div>
                                <small class="text-muted" id="mobileRoomPriceHint"></small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Check In') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Assign Work Modal --}}
    @php
        $workPlaces = \App\Models\WorkPlace::where('created_by', Auth::user()->creatorId())->pluck('name', 'id');
    @endphp
    <div class="modal fade" id="assignWorkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Assign Worker') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('worker.assign.work', $worker->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="redirect_to" value="mobile">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">{{ __('Work Place') }} <span class="text-danger">*</span></label>
                            <select name="work_place_id" id="mobileWorkPlaceSelect" class="form-control" required>
                                <option value="">{{ __('Select Work Place') }}</option>
                                @foreach ($workPlaces as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">{{ __('Position') }} <span class="text-danger">*</span></label>
                            <select name="position_id" id="mobilePositionSelect" class="form-control" required disabled>
                                <option value="">{{ __('First select a work place') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Assign') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Generate Document Modal --}}
    @can('document_generate')
        @php
            $templates = \App\Models\DocumentTemplate::where('created_by', Auth::user()->creatorId())
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        @endphp
        <div class="modal fade" id="generateDocModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Generate Document') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" action="{{ route('worker.bulk.generate-documents') }}">
                        @csrf
                        <input type="hidden" name="single_worker_id" value="{{ $worker->id }}">
                        <div class="modal-body">
                            <div class="form-group mb-3">
                                <label class="form-label">{{ __('Template') }} <span class="text-danger">*</span></label>
                                <select name="template_id" class="form-control" required>
                                    <option value="">{{ __('Select Template') }}</option>
                                    @foreach ($templates as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ __('Format') }}</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" value="pdf"
                                            checked>
                                        <label class="form-check-label">PDF</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="format" value="docx">
                                        <label class="form-check-label">Word</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-success">{{ __('Generate') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <style>
        .mobile-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .mobile-badge-working {
            background: #22B404;
            color: #fff;
        }

        .mobile-badge-not-working {
            background: #999;
            color: #fff;
        }

        .mobile-badge-housed {
            background: #FF0049;
            color: #fff;
        }

        .mobile-badge-not-housed {
            background: #FFE0E6;
            color: #FF0049;
        }

        .mobile-profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid #FF0049;
        }

        .mobile-profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .mobile-avatar-placeholder-lg {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #FF0049, #FF6B6B);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 32px;
        }

        .mobile-info-card {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .mobile-info-title {
            color: #FF0049;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .mobile-info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .mobile-info-row:last-child {
            border-bottom: none;
        }

        .mobile-info-label {
            color: #666;
            font-size: 13px;
        }

        .mobile-info-value {
            font-weight: 500;
            font-size: 13px;
        }

        .mobile-btn-danger {
            background: #FF0049 !important;
            border-color: #FF0049 !important;
            color: #fff !important;
            padding: 12px 20px;
            font-weight: 600;
        }

        .mobile-btn-danger:hover,
        .mobile-btn-danger:focus {
            background: #e00040 !important;
            border-color: #e00040 !important;
            color: #fff !important;
        }
    </style>

    @push('scripts')
        <script>
            // Delete worker confirmation
            function confirmDeleteWorker() {
                if (confirm(
                        '{{ __('Are you sure you want to delete this worker?') }}\n{{ __('This action cannot be undone.') }}'
                    )) {
                    document.getElementById('delete-worker-form').submit();
                }
            }

            // Hotel -> Room selection
            $('#mobileHotelSelect').on('change', function() {
                var hotelId = $(this).val();
                var roomSelect = $('#mobileRoomSelect');

                if (hotelId) {
                    roomSelect.prop('disabled', false);
                    $.get('/hotel/' + hotelId + '/available-rooms', function(data) {
                        roomSelect.html('<option value="">{{ __('Select Room') }}</option>');
                        if (data && data.length > 0) {
                            // Group rooms by occupancy status
                            var emptyRooms = data.filter(r => r.occupied === 0);
                            var partialRooms = data.filter(r => r.occupied > 0 && !r.is_full);

                            // Add empty rooms
                            if (emptyRooms.length > 0) {
                                roomSelect.append('<optgroup label="━━━ {{ __('Empty rooms') }} ━━━">');
                                $.each(emptyRooms, function(index, room) {
                                    var label = '{{ __('Room') }} ' + room.room_number + ' (0/' + room.capacity + ')';
                                    roomSelect.append('<option value="' + room.id + '">' + label + '</option>');
                                });
                                roomSelect.append('</optgroup>');
                            }

                            // Add partially occupied rooms
                            if (partialRooms.length > 0) {
                                roomSelect.append('<optgroup label="━━━ {{ __('Partially occupied') }} ━━━">');
                                $.each(partialRooms, function(index, room) {
                                    var label = '{{ __('Room') }} ' + room.room_number + ' (' + room.occupied + '/' + room.capacity + ')';
                                    roomSelect.append('<option value="' + room.id + '">' + label + '</option>');
                                });
                                roomSelect.append('</optgroup>');
                            }

                            if (emptyRooms.length === 0 && partialRooms.length === 0) {
                                roomSelect.html('<option value="">{{ __('No rooms available') }}</option>');
                            }
                        } else {
                            roomSelect.html('<option value="">{{ __('No rooms available') }}</option>');
                        }
                    }).fail(function() {
                        roomSelect.html('<option value="">{{ __('No rooms available') }}</option>');
                    });
                } else {
                    roomSelect.prop('disabled', true).html(
                        '<option value="">{{ __('First select a hotel') }}</option>');
                }
            });

            // Room selection - load price
            $('#mobileRoomSelect').on('change', function() {
                var roomId = $(this).val();
                var priceHint = $('#mobileRoomPriceHint');
                var paymentInput = $('#mobilePaymentAmountInput');

                if (roomId) {
                    $.ajax({
                        url: '/room/' + roomId,
                        type: 'GET',
                        headers: { 'Accept': 'application/json' },
                        success: function(room) {
                            if (room && room.monthly_price) {
                                priceHint.text('{{ __('Room price') }}: ' + room.monthly_price_formatted);
                                paymentInput.val(room.monthly_price);
                            }
                        }
                    });
                } else {
                    priceHint.text('');
                }
            });

            // Worker pays checkbox toggle
            $('#mobileWorkerPaysCheckbox').on('change', function() {
                var paymentWrapper = $('#mobilePaymentAmountWrapper');
                if ($(this).is(':checked')) {
                    paymentWrapper.slideDown();
                } else {
                    paymentWrapper.slideUp();
                }
            });

            // WorkPlace -> Position selection
            $('#mobileWorkPlaceSelect').on('change', function() {
                var workPlaceId = $(this).val();
                var positionSelect = $('#mobilePositionSelect');

                if (workPlaceId) {
                    positionSelect.prop('disabled', false);
                    $.get('/work-place/' + workPlaceId + '/positions/json', function(data) {
                        positionSelect.html('<option value="">{{ __('Select Position') }}</option>');
                        if (data && data.length > 0) {
                            $.each(data, function(index, position) {
                                positionSelect.append('<option value="' + position.id + '">' + position
                                    .name + '</option>');
                            });
                        } else {
                            positionSelect.html(
                                '<option value="">{{ __('No positions available') }}</option>');
                        }
                    }).fail(function() {
                        positionSelect.html('<option value="">{{ __('No positions available') }}</option>');
                    });
                } else {
                    positionSelect.prop('disabled', true).html(
                        '<option value="">{{ __('First select a work place') }}</option>');
                }
            });
        </script>
    @endpush
@endsection
