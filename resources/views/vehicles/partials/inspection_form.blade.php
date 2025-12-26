{{-- Add Technical Inspection Modal --}}
<div class="modal fade" id="addInspectionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-clipboard-check me-2"></i>{{ __('Add Technical Inspection') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            {{ Form::open(['route' => ['inspections.store', $vehicle->id], 'method' => 'POST']) }}
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {{ Form::label('inspection_date', __('Inspection Date'), ['class' => 'form-label']) }}
                            <x-required></x-required>
                            {{ Form::date('inspection_date', date('Y-m-d'), ['class' => 'form-control', 'required' => true]) }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {{ Form::label('next_inspection_date', __('Next Inspection'), ['class' => 'form-label']) }}
                            <x-required></x-required>
                            {{ Form::date('next_inspection_date', date('Y-m-d', strtotime('+1 year')), ['class' => 'form-control', 'required' => true]) }}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            {{ Form::label('mileage', __('Mileage (km)'), ['class' => 'form-label']) }}
                            {{ Form::number('mileage', null, ['class' => 'form-control', 'min' => 0, 'placeholder' => '50000']) }}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            {{ Form::label('cost', __('Cost'), ['class' => 'form-label']) }}
                            {{ Form::number('cost', null, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'placeholder' => '1500.00']) }}
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    {{ Form::label('service_station', __('Service Station Name'), ['class' => 'form-label']) }}
                    {{ Form::text('service_station', null, ['class' => 'form-control', 'placeholder' => __('For example: AutoService Praha')]) }}
                </div>

                <div class="form-group">
                    {{ Form::label('description', __('Work Description'), ['class' => 'form-label']) }}
                    {{ Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('Work performed, replaced parts...')]) }}
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
