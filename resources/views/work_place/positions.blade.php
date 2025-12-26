@extends('layouts.admin')

@section('page-title')
    {{ __('Positions') }}: {{ $workPlace->name }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a></li>
    <li class="breadcrumb-item"><a href="{{ route('work-place.index') }}">{{ __('Work Places') }}</a></li>
    <li class="breadcrumb-item">{{ $workPlace->name }}</li>
@endsection

@section('action-btn')
    <div class="float-end">
        <a href="#" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createPositionModal">
            <i class="ti ti-plus"></i> {{ __('Create Position') }}
        </a>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>{{ __('Positions in') }} {{ $workPlace->name }}</h5>
                    <small class="text-muted">{{ $workPlace->address }}</small>
                </div>
                <div class="card-body">
                    @if($positions->isEmpty())
                        <div class="text-center py-4">
                            <i class="ti ti-briefcase text-muted" style="font-size: 48px;"></i>
                            <p class="text-muted mt-2">{{ __('No positions created yet') }}</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>{{ __('Position Name') }}</th>
                                        <th class="text-center">{{ __('Workers') }}</th>
                                        <th class="text-end">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($positions as $position)
                                        <tr>
                                            <td>
                                                <a href="#" 
                                                   data-url="{{ route('positions.workers', $position->id) }}"
                                                   data-ajax-popup="true"
                                                   data-title="{{ __('Employees') }}: {{ $position->name }}"
                                                   data-size="lg"
                                                   class="text-primary fw-bold">
                                                    <i class="ti ti-briefcase me-2"></i>
                                                    {{ $position->name }}
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">{{ $position->workers_count }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="#" 
                                                   data-url="{{ route('positions.workers', $position->id) }}"
                                                   data-ajax-popup="true"
                                                   data-title="{{ __('Employees') }}: {{ $position->name }}"
                                                   data-size="lg"
                                                   class="btn btn-sm btn-success">
                                                    <i class="ti ti-user-plus"></i> {{ __('Employ') }}
                                                </a>
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editPositionModal{{ $position->id }}">
                                                    <i class="ti ti-pencil"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deletePositionModal{{ $position->id }}">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Create Position Modal -->
    <div class="modal fade" id="createPositionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('positions.store', $workPlace->id) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Create Position') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">{{ __('Position Name') }}</label>
                            <input type="text" name="name" class="form-control" required 
                                   placeholder="{{ __('For example: Manager') }}">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Create') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Position Modals -->
    @foreach($positions as $position)
        <div class="modal fade" id="editPositionModal{{ $position->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('positions.update', $position->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">{{ __('Edit Position') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label class="form-label">{{ __('Position Name') }}</label>
                                <input type="text" name="name" class="form-control" required 
                                       value="{{ $position->name }}"
                                       placeholder="{{ __('For example: Manager') }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Position Modal -->
        <div class="modal fade" id="deletePositionModal{{ $position->id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="ti ti-alert-triangle me-2"></i>
                            {{ __('Delete Position') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">
                            {{ __('Are you sure you want to delete position') }} <strong>"{{ $position->name }}"</strong>?
                        </p>
                        @if($position->workers_count > 0)
                            <div class="alert alert-warning mb-0">
                                <i class="ti ti-alert-circle me-2"></i>
                                {{ __('All workers will be dismissed from this position', ['count' => $position->workers_count]) }}
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                        <form action="{{ route('positions.destroy', $position->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="ti ti-trash me-1"></i> {{ __('Delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@endsection
