<div class="modal-body">
    <div class="mb-4">
        <h6 class="mb-2">{{ __('Manager') }}: <strong>{{ $manager->name }}</strong></h6>
        <p class="text-muted mb-0">{{ $manager->email }}</p>
    </div>

    {{-- Assigned Curators --}}
    <div class="mb-4">
        <h6 class="mb-3">{{ __('Assigned Curators') }}</h6>
        @if($curators->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th width="100">{{ __('Action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($curators as $curator)
                            <tr>
                                <td>{{ $curator->name }}</td>
                                <td>{{ $curator->email }}</td>
                                <td>
                                    <form action="{{ route('manager.curators.destroy', [$manager->id, $curator->id]) }}" 
                                          method="POST" class="d-inline" 
                                          onsubmit="return confirm('{{ __('Are you sure you want to remove this curator?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" data-bs-toggle="tooltip" title="{{ __('Remove') }}">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-3">
                <i class="ti ti-users-group" style="font-size: 32px; opacity: 0.3;"></i>
                <p class="text-muted mt-2 mb-0">{{ __('No curators assigned to this manager') }}</p>
            </div>
        @endif
    </div>

    {{-- Add Curator Form --}}
    @if($availableCurators->count() > 0)
        <div class="border-top pt-4">
            <h6 class="mb-3">{{ __('Assign Curator') }}</h6>
            <form action="{{ route('manager.curators.store', $manager->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-8">
                        <select name="curator_id" class="form-control" required>
                            <option value="">{{ __('Select Curator') }}</option>
                            @foreach($availableCurators as $curator)
                                <option value="{{ $curator->id }}">{{ $curator->name }} ({{ $curator->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-plus me-1"></i>{{ __('Assign') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @else
        <div class="border-top pt-4">
            <div class="text-center py-2">
                <p class="text-muted mb-0">{{ __('All curators are already assigned to this manager') }}</p>
            </div>
        </div>
    @endif
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
</div>
