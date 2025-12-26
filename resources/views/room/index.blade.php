@extends('layouts.admin')

@section('page-title')
    {{ __('Room Management') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item">{{ __('Rooms') }}</li>
@endsection

@section('action-btn')
@endsection

@section('content')
    <div class="row">

        <div class="col-12">
            <div class="my-3 d-flex justify-content-end">
                @can('create hotel')
                    <a href="#" data-url="{{ route('room.create') }}" data-ajax-popup="true"
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
                            <div class="table-responsive">
                                <table class="table datatable">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Room Number') }}</th>
                                            <th>{{ __('Hotel') }}</th>
                                            <th>{{ __('Capacity') }}</th>
                                            <th>{{ __('Price/month') }}</th>
                                            <th>{{ __('Residents') }}</th>
                                            <th width="200px">{{ __('Action') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="font-style">
                                        @foreach ($rooms as $room)
                                            <tr>
                                                <td>{{ $room->room_number }}</td>
                                                <td>{{ !empty($room->hotel) ? $room->hotel->name : '-' }}</td>
                                                <td>{{ $room->capacity }}</td>
                                                <td>{{ formatCashboxCurrency($room->monthly_price) }}</td>
                                                <td>
                                                    {{ $room->currentAssignments->count() }} / {{ $room->capacity }}
                                                </td>

                                                <td class="Action">
                                                    <span>
                                                        @can('edit hotel')
                                                            <div class="action-btn me-2">
                                                                <a href="#"
                                                                    data-url="{{ URL::to('room/' . $room->id . '/edit') }}"
                                                                    data-ajax-popup="true"
                                                                    data-title="{{ __('Edit Room') }}"
                                                                    class="mx-3 btn btn-sm align-items-center bg-info"
                                                                    data-bs-toggle="tooltip" title="{{ __('Edit') }}"
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
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
