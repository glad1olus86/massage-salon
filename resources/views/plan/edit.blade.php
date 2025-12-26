    {{Form::model($plan, array('route' => array('plans.update', $plan->id), 'method' => 'PUT', 'enctype' => "multipart/form-data", 'class'=>'needs-validation', 'novalidate')) }}
    <div class="modal-body">
        {{-- start for ai module--}}
        @php
            $settings = \App\Models\Utility::settings();
        @endphp
        @if(!empty($settings['chat_gpt_key']))
        <div class="text-end">
            <a href="#" data-size="md" class="btn  btn-primary btn-icon btn-sm" data-ajax-popup-over="true" data-url="{{ route('generate',['plan']) }}"
               data-bs-placement="top" data-title="{{ __('Generate content with AI') }}">
                <i class="fas fa-robot"></i> <span>{{__('Generate with AI')}}</span>
            </a>
        </div>
        @endif
        {{-- end for ai module--}}

    <div class="row">
        <div class="form-group col-md-6">
            {{Form::label('name',__('Name'),['class'=>'form-label'])}}<x-required></x-required>
            {{Form::text('name',null,array('class'=>'form-control font-style','placeholder'=>__('Enter Plan Name'),'required'=>'required'))}}
        </div>
        @if($plan->id != 1)
            <div class="form-group col-md-6">
                {{Form::label('price',__('Price'),['class'=>'form-label'])}}<x-required></x-required>
                {{Form::number('price',null,array('class'=>'form-control','placeholder'=>__('Enter Plan Price'),'required'=>'required' ,'step' => '0.01'))}}
            </div>
            <div class="form-group col-md-6">
                {{ Form::label('duration', __('Duration'),['class'=>'form-label']) }}<x-required></x-required>
                {!! Form::select('duration', $arrDuration, null,array('class' => 'form-control select','required'=>'required')) !!}
            </div>
        @endif
        <div class="form-group col-md-6">
            {{Form::label('max_users',__('Maximum Users'),['class'=>'form-label'])}}<x-required></x-required>
            {{Form::number('max_users',null,array('class'=>'form-control','required'=>'required', 'placeholder' => __('Enter Maximum Users')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{Form::label('max_customers',__('Maximum Customers'),['class'=>'form-label'])}}<x-required></x-required>
            {{Form::number('max_customers',null,array('class'=>'form-control','required'=>'required', 'placeholder' => __('Enter Maximum Customers')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{Form::label('max_venders',__('Maximum Venders'),['class'=>'form-label'])}}<x-required></x-required>
            {{Form::number('max_venders',null,array('class'=>'form-control','required'=>'required', 'placeholder' => __('Enter Maximum Vendors')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{Form::label('max_clients',__('Maximum Clients'),['class'=>'form-label'])}}<x-required></x-required>
            {{Form::number('max_clients',null,array('class'=>'form-control','required'=>'required', 'placeholder' => __('Enter Maximum Clients')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{ Form::label('storage_limit', __('Storage limit'), ['class' => 'form-label']) }}<x-required></x-required>
            <div class="input-group">
                {{ Form::number('storage_limit', null,array('class'=>'form-control','required'=>'required', 'placeholder' => __('Maximum Storage Limit'))) }}
                <div class="input-group-append">
                <span class="input-group-text"
                      id="basic-addon2">{{__('MB')}}</span>
                </div>
            </div>
        </div>


        <div class="form-group col-md-12">
            {{ Form::label('description', __('Description'),['class'=>'form-label']) }}
            {!! Form::textarea('description', null, ['class'=>'form-control','rows'=>'2', 'placeholder' => __('Enter Description')]) !!}
        </div>
        @if($plan->id != 1)
        <div class="col-md-6">
            <label class="form-check-label" for="trial"></label>
            <div class="form-group">
                <label for="trial" class="form-label">{{ __('Trial is enable(on/off)') }}</label>
                <div class="form-check form-switch custom-switch-v1 float-end">
                    <input type="checkbox" name="trial" class="form-check-input input-primary pointer" value="1" id="trial"  {{ $plan['trial'] == 1 ? 'checked="checked"' : '' }}>
                    <label class="form-check-label" for="trial"></label>
                </div>
            </div>
        </div>
        <div class="col-md-6 ">
            <div class="form-group plan_div  {{ $plan['trial'] == 1 ? 'd-block' : 'd-none' }}">
                {{ Form::label('trial_days', __('Trial Days'), ['class' => 'form-label']) }}
                {{ Form::number('trial_days',null, ['class' => 'form-control trial_days','placeholder' => __('Enter Trial days'),'step' => '1','min'=>'1']) }}
            </div>
        </div>
        @endif
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch ">
                <input type="checkbox" class="form-check-input" name="enable_crm" id="enable_crm" {{ $plan['crm'] == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="enable_crm">{{__('CRM')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="enable_project" id="enable_project" {{ $plan['project'] == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="enable_project">{{__('Project')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="enable_hrm" id="enable_hrm" {{ $plan['hrm'] == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="enable_hrm">{{__('HRM')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="enable_account" id="enable_account" {{ $plan['account'] == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="enable_account">{{__('Account')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="enable_pos" id="enable_pos" {{ $plan['pos'] == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="enable_pos">{{__('POS')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="enable_chatgpt" id="enable_chatgpt" {{ $plan['chatgpt'] == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="enable_chatgpt">{{__('Chat GPT')}}</label>
            </div>
        </div>

        {{-- JOBSI Modules Section --}}
        <div class="col-12 mt-4">
            <h6 class="text-primary">{{ __('JOBSI Modules') }}</h6>
            <hr class="mt-1">
        </div>
        
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_workers" id="module_workers" {{ ($plan['module_workers'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_workers">{{__('Workers')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_workplaces" id="module_workplaces" {{ ($plan['module_workplaces'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_workplaces">{{__('Workplaces')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_hotels" id="module_hotels" {{ ($plan['module_hotels'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_hotels">{{__('Hotels & Rooms')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_vehicles" id="module_vehicles" {{ ($plan['module_vehicles'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_vehicles">{{__('Vehicles')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_documents" id="module_documents" {{ ($plan['module_documents'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_documents">{{__('Documents')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_cashbox" id="module_cashbox" {{ ($plan['module_cashbox'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_cashbox">{{__('Cashbox')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_calendar" id="module_calendar" {{ ($plan['module_calendar'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_calendar">{{__('Calendar')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_notifications" id="module_notifications" {{ ($plan['module_notifications'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_notifications">{{__('Notifications')}}</label>
            </div>
        </div>
        <div class="form-group col-md-3 mt-2">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" name="module_attendance" id="module_attendance" {{ ($plan['module_attendance'] ?? 1) == 1 ? 'checked="checked"' : '' }}>
                <label class="custom-control-label form-label" for="module_attendance">{{__('Attendance')}}</label>
            </div>
        </div>

        {{-- User Pricing Section --}}
        <div class="col-12 mt-4">
            <h6 class="text-primary">{{ __('User Pricing') }}</h6>
            <hr class="mt-1">
        </div>
        
        <div class="form-group col-md-4">
            {{Form::label('base_users_limit',__('Base Users Limit'),['class'=>'form-label'])}}<x-required></x-required>
            {{Form::number('base_users_limit', $plan['base_users_limit'] ?? 3, array('class'=>'form-control', 'required'=>'required', 'placeholder' => __('Users included in base price')))}}
            <span class="small">{{__('Number of manager/curator users included in base price')}}</span>
        </div>
        <div class="form-group col-md-4">
            {{Form::label('manager_price',__('Manager Price'),['class'=>'form-label'])}}<x-required></x-required>
            <div class="input-group">
                <span class="input-group-text">$</span>
                {{Form::number('manager_price', $plan['manager_price'] ?? 50.00, array('class'=>'form-control', 'required'=>'required', 'step'=>'0.01', 'placeholder' => __('Price per manager')))}}
            </div>
            <span class="small">{{__('Additional cost per manager over limit')}}</span>
        </div>
        <div class="form-group col-md-4">
            {{Form::label('curator_price',__('Curator Price'),['class'=>'form-label'])}}<x-required></x-required>
            <div class="input-group">
                <span class="input-group-text">$</span>
                {{Form::number('curator_price', $plan['curator_price'] ?? 30.00, array('class'=>'form-control', 'required'=>'required', 'step'=>'0.01', 'placeholder' => __('Price per curator')))}}
            </div>
            <span class="small">{{__('Additional cost per curator over limit')}}</span>
        </div>

        {{-- JOBSI Limits Section --}}
        <div class="col-12 mt-4">
            <h6 class="text-primary">{{ __('JOBSI Limits') }}</h6>
            <hr class="mt-1">
        </div>
        
        <div class="form-group col-md-6">
            {{Form::label('max_workers',__('Maximum Workers'),['class'=>'form-label'])}}
            {{Form::number('max_workers', $plan['max_workers'] ?? -1, array('class'=>'form-control', 'placeholder' => __('Enter Maximum Workers')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{Form::label('max_roles',__('Maximum Roles'),['class'=>'form-label'])}}
            {{Form::number('max_roles', $plan['max_roles'] ?? -1, array('class'=>'form-control', 'placeholder' => __('Enter Maximum Roles')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{Form::label('max_vehicles',__('Maximum Vehicles'),['class'=>'form-label'])}}
            {{Form::number('max_vehicles', $plan['max_vehicles'] ?? -1, array('class'=>'form-control', 'placeholder' => __('Enter Maximum Vehicles')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{Form::label('max_hotels',__('Maximum Hotels'),['class'=>'form-label'])}}
            {{Form::number('max_hotels', $plan['max_hotels'] ?? -1, array('class'=>'form-control', 'placeholder' => __('Enter Maximum Hotels')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{Form::label('max_workplaces',__('Maximum Workplaces'),['class'=>'form-label'])}}
            {{Form::number('max_workplaces', $plan['max_workplaces'] ?? -1, array('class'=>'form-control', 'placeholder' => __('Enter Maximum Workplaces')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>
        <div class="form-group col-md-6">
            {{Form::label('max_document_templates',__('Maximum Document Templates'),['class'=>'form-label'])}}
            {{Form::number('max_document_templates', $plan['max_document_templates'] ?? -1, array('class'=>'form-control', 'placeholder' => __('Enter Maximum Document Templates')))}}
            <span class="small">{{__('Note: "-1" for Unlimited')}}</span>
        </div>

    </div>
    </div>

    <div class="modal-footer">
    <input type="button" value="{{__('Cancel')}}" class="btn  btn-secondary" data-bs-dismiss="modal">
    <input type="submit" value="{{__('Update')}}" class="btn  btn-primary">
</div>
    {{ Form::close() }}

