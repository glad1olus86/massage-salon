@extends('layouts.admin')

@section('page-title')
    {{ __('Notification Builder') }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('settings') }}">{{ __('Settings') }}</a></li>
    <li class="breadcrumb-item">{{ __('Notification Builder') }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" data-url="{{ route('notification-rules.create') }}" data-ajax-popup="true"
            data-title="{{ __('Create Notification Rule') }}" data-size="lg"
            class="btn btn-sm btn-primary">
            <i class="ti ti-plus"></i> {{ __('Create Rule') }}
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>{{ __('Notification Rules') }}</h5>
                <small class="text-muted">{{ __('Configure automatic notifications for your company') }}</small>
            </div>
            <div class="card-body">
                @if($rules->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Rule') }}</th>
                                    <th>{{ __('Period') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rules as $rule)
                                    <tr class="{{ !$rule->is_active ? 'text-muted' : '' }}">
                                        <td>
                                            <form action="{{ route('notification-rules.toggle', $rule->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm {{ $rule->is_active ? 'btn-success' : 'btn-secondary' }}" 
                                                    data-bs-toggle="tooltip" title="{{ $rule->is_active ? __('Disable') : __('Enable') }}">
                                                    <i class="ti {{ $rule->is_active ? 'ti-check' : 'ti-x' }}"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>
                                            <strong>{{ $rule->name }}</strong>
                                            @if($rule->is_grouped)
                                                <span class="badge bg-secondary ms-1" data-bs-toggle="tooltip" title="{{ __('Grouping enabled') }}">
                                                    <i class="ti ti-stack"></i>
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            @include('notification_rules.partials.rule_display', ['rule' => $rule])
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $rule->period_text }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $rule->severity_info['color'] }}">
                                                <i class="ti {{ $rule->severity_info['icon'] }} me-1"></i>
                                                {{ $rule->severity_info['label'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <a href="#" data-url="{{ route('notification-rules.edit', $rule->id) }}" 
                                                    data-ajax-popup="true" data-title="{{ __('Edit Rule') }}" data-size="lg"
                                                    class="btn btn-sm btn-info" data-bs-toggle="tooltip" title="{{ __('Edit') }}">
                                                    <i class="ti ti-pencil text-white"></i>
                                                </a>
                                                {!! Form::open(['method' => 'DELETE', 'route' => ['notification-rules.destroy', $rule->id], 'class' => 'd-inline']) !!}
                                                <a href="#" class="btn btn-sm btn-danger bs-pass-para" 
                                                    data-bs-toggle="tooltip" title="{{ __('Delete') }}"
                                                    data-confirm="{{ __('Are you sure?') . '|' . __('This action will delete the notification rule.') }}"
                                                    data-confirm-yes="$(this).closest('form').submit();">
                                                    <i class="ti ti-trash text-white"></i>
                                                </a>
                                                {!! Form::close() !!}
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="ti ti-bell-off" style="font-size: 48px; color: #ccc;"></i>
                        <p class="text-muted mt-3">{{ __('No notification rules configured') }}</p>
                        <a href="#" data-url="{{ route('notification-rules.create') }}" data-ajax-popup="true"
                            data-title="{{ __('Create Notification Rule') }}" data-size="lg"
                            class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i>{{ __('Create First Rule') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
