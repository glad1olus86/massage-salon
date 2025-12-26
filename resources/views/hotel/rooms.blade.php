@extends('layouts.admin')

@section('page-title')
    {{ __('Hotel Rooms') }} - {{ $hotel->name }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('hotel.index') }}">{{ __('Hotels') }}</a></li>
    <li class="breadcrumb-item">{{ $hotel->name }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            {{-- Hotel Info Card --}}
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-2">{{ $hotel->name }}</h5>
                            <p class="text-muted mb-1"><i class="ti ti-map-pin"></i> {{ $hotel->address }}</p>
                            @if($hotel->phone)
                                <p class="text-muted mb-1"><i class="ti ti-phone"></i> {{ $hotel->phone }}</p>
                            @endif
                            @if($hotel->email)
                                <p class="text-muted mb-0"><i class="ti ti-mail"></i> {{ $hotel->email }}</p>
                            @endif
                        </div>
                        <div class="col-md-6 text-md-end">
                            @if($hotel->responsible)
                                <p class="mb-1">
                                    <span class="text-muted">{{ __('Responsible') }}:</span>
                                    <strong>{{ $hotel->responsible->name }}</strong>
                                </p>
                            @endif
                            <p class="mb-0">
                                <span class="text-muted">{{ __('Total Rooms') }}:</span>
                                <strong>{{ $rooms->count() }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="my-3 d-flex justify-content-end">
                @can('create hotel')
                    <a href="#" data-url="{{ route('room.create', ['hotel_id' => $hotel->id]) }}" data-ajax-popup="true"
                        data-title="{{ __('Create New Room') }}" data-bs-toggle="tooltip" title="{{ __('Create') }}"
                        class="btn btn-sm btn-primary">
                        <i class="ti ti-plus"></i>
                    </a>
                @endcan
            </div>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body table-border-style">
                            @if ($rooms->count() > 0)
                                <div class="table-responsive">
                                    <table class="table datatable">
                                        <thead>
                                            <tr>
                                                <th>{{ __('Room Number') }}</th>
                                                <th>{{ __('Capacity') }}</th>
                                                <th>{{ __('Price/month') }}</th>
                                                <th>{{ __('Residents Payment') }}</th>
                                                <th width="200px">{{ __('Action') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="font-style">
                                            @foreach ($rooms as $room)
                                                <tr>
                                                    <td>
                                                        <a href="#"
                                                            data-url="{{ route('room.show', $room->id) }}"
                                                            data-ajax-popup="true"
                                                            data-title="{{ __('Room Residents') }} {{ $room->room_number }}"
                                                            data-size="lg"
                                                            class="text-primary fw-medium">
                                                            {{ $room->room_number }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $room->currentAssignments->count() }} / {{ $room->capacity }}
                                                    </td>
                                                    <td>{{ formatCashboxCurrency($room->monthly_price) }}</td>
                                                    <td>
                                                        @if($room->currentAssignments->count() > 0)
                                                            @foreach($room->currentAssignments as $assignment)
                                                                <div class="mb-1">
                                                                    <small>{{ $assignment->worker->first_name }}:</small>
                                                                    @if($assignment->payment_type === 'worker')
                                                                        <span class="badge" style="background-color: #FF0049;">{{ formatCashboxCurrency($assignment->payment_amount ?? 0) }}</span>
                                                                    @else
                                                                        <span class="badge bg-success">{{ __('Agency') }}</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>

                                                    <td class="Action">
                                                        <span>
                                                            @can('manage hotel')
                                                                <div class="action-btn me-2">
                                                                    <a href="#"
                                                                        data-url="{{ route('room.show', $room->id) }}"
                                                                        data-ajax-popup="true"
                                                                        data-title="{{ __('Room Residents') }}"
                                                                        data-size="lg"
                                                                        class="mx-3 btn btn-sm align-items-center bg-warning"
                                                                        data-bs-toggle="tooltip"
                                                                        title="{{ __('View Residents') }}"
                                                                        data-original-title="{{ __('View') }}">
                                                                        <i class="ti ti-eye text-white"></i>
                                                                    </a>
                                                                </div>
                                                            @endcan
                                                            @can('edit hotel')
                                                                <div class="action-btn me-2">

                                                                    <a href="#"
                                                                        data-url="{{ URL::to('room/' . $room->id . '/edit') }}"
                                                                        data-ajax-popup="true"
                                                                        data-title="{{ __('Edit Room') }}"
                                                                        class="mx-3 btn btn-sm align-items-center bg-info"
                                                                        data-bs-toggle="tooltip"
                                                                        title="{{ __('Edit') }}"
                                                                        data-original-title="{{ __('Edit') }}">
                                                                        <i class="ti ti-pencil text-white"></i></a>
                                                                </div>
                                                            @endcan
                                                            @can('delete hotel')
                                                                <div class="action-btn ">
                                                                    {!! Form::open([
                                                                        'method' => 'DELETE',
                                                                        'route' => ['room.destroy', $room->id],
                                                                        'id' => 'delete-form-' . $room->id,
                                                                    ]) !!}


                                                                    <a href="#"
                                                                        class="mx-3 btn btn-sm  align-items-center bs-pass-para bg-danger"
                                                                        data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                                        data-original-title="{{ __('Delete') }}"
                                                                        data-confirm="{{ __('Are you sure?') . '|' . __('This action cannot be undone. Do you want to continue?') }}"
                                                                        data-confirm-yes="document.getElementById('delete-form-{{ $room->id }}').submit();"><i
                                                                            class="ti ti-trash text-white"></i></a>
                                                                    {!! Form::close() !!}
                                                                </div>
                                                            @endcan
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="ti ti-bed" style="font-size: 48px; opacity: 0.3;"></i>
                                    <h5 class="mt-3">{{ __('No Rooms') }}</h5>
                                    <p class="text-muted">{{ __('No rooms have been created for this hotel yet') }}</p>
                                    @can('create hotel')
                                        <a href="#" data-url="{{ route('room.create', ['hotel_id' => $hotel->id]) }}"
                                            data-ajax-popup="true" data-title="{{ __('Create New Room') }}"
                                            class="btn btn-primary mt-2">
                                            <i class="ti ti-plus"></i> {{ __('Create Room') }}
                                        </a>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
