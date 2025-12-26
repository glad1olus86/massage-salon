@extends('layouts.admin')

@section('page-title')
    {{ __('Cashbox Audit') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('cashbox.index') }}">{{ __('Cashbox') }}</a></li>
    <li class="breadcrumb-item">{{ __('Audit') }}</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    {{-- Filters --}}
                    <form action="{{ route('cashbox.audit') }}" method="GET" class="mb-4">
                        <div class="row align-items-end">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="start_date" class="form-label">{{ __('Date From') }}</label>
                                    <input type="date" class="form-control" name="start_date" id="start_date"
                                        value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="end_date" class="form-label">{{ __('Date To') }}</label>
                                    <input type="date" class="form-control" name="end_date" id="end_date"
                                        value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="period_id" class="form-label">{{ __('Period') }}</label>
                                    <select name="period_id" id="period_id" class="form-control select2">
                                        <option value="">{{ __('All Periods') }}</option>
                                        @foreach ($periods as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ request('period_id') == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="user_id" class="form-label">{{ __('User') }}</label>
                                    <select name="user_id" id="user_id" class="form-control select2">
                                        <option value="">{{ __('All Users') }}</option>
                                        @foreach ($users as $id => $name)
                                            <option value="{{ $id }}"
                                                {{ request('user_id') == $id ? 'selected' : '' }}>
                                                {{ $name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="event_type" class="form-label">{{ __('Operation Type') }}</label>
                                    <select name="event_type" id="event_type" class="form-control select2">
                                        <option value="">{{ __('All Operations') }}</option>
                                        @foreach ($eventTypes as $key => $label)
                                            <option value="{{ $key }}"
                                                {{ request('event_type') == $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group d-flex gap-2" style="margin-top: 28px;">
                                    <button type="submit" class="btn btn-primary flex-grow-1">
                                        <i class="ti ti-filter"></i> {{ __('Apply') }}
                                    </button>
                                    <a href="{{ route('cashbox.audit') }}" class="btn btn-secondary">
                                        <i class="ti ti-refresh"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Table --}}
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Date/Time') }}</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Operation') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $log)
                                    <tr>
                                        <td>
                                            {{ \Auth::user()->dateFormat($log->created_at) }}
                                            <br>
                                            <small class="text-muted">{{ \Auth::user()->timeFormat($log->created_at) }}</small>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm rounded-circle me-2 bg-primary text-white d-flex align-items-center justify-content-center"
                                                    style="width: 30px; height: 30px; font-size: 12px;">
                                                    {{ substr($log->user_name, 0, 2) }}
                                                </div>
                                                <span>{{ $log->user_name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill" style="background-color: {{ $log->event_color }}; color: white;">
                                                <i class="{{ $log->event_icon }} me-1"></i>
                                                {{ $eventTypes[$log->event_type] ?? $log->event_type }}
                                            </span>
                                        </td>
                                        <td>
                                            {{ $log->description }}
                                        </td>
                                        <td>
                                            @if (!empty($log->old_values) || !empty($log->new_values))
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                    data-bs-target="#audit-details-{{ $log->id }}">
                                                    <i class="ti ti-eye"></i>
                                                </button>

                                                {{-- Modal Details --}}
                                                <div class="modal fade" id="audit-details-{{ $log->id }}" tabindex="-1"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">{{ __('Operation Details') }}</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                                    aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    @if (!empty($log->old_values))
                                                                        <div class="col-md-6">
                                                                            <h6 class="text-danger">{{ __('Old Values') }}</h6>
                                                                            <pre class="bg-light p-2 rounded">@json($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                                                        </div>
                                                                    @endif
                                                                    @if (!empty($log->new_values))
                                                                        <div class="col-md-{{ empty($log->old_values) ? '12' : '6' }}">
                                                                            <h6 class="text-success">{{ __('Operation Data') }}</h6>
                                                                            <pre class="bg-light p-2 rounded">@json($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                <hr>
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <small class="text-muted">{{ __('IP Address:') }}</small>
                                                                        <p>{{ $log->ip_address ?? '-' }}</p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <small class="text-muted">{{ __('User Agent:') }}</small>
                                                                        <p class="text-truncate" title="{{ $log->user_agent }}">{{ $log->user_agent ?? '-' }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="ti ti-search" style="font-size: 48px; opacity: 0.5;"></i>
                                                <p class="mt-2">{{ __('No operations found') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $auditLogs->appends(request()->query())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection