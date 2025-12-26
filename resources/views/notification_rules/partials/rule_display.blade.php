@php
    $entityTypes = \App\Models\NotificationRule::getEntityTypes();
    $entityLabel = $entityTypes[$rule->entity_type] ?? $rule->entity_type;
    $conditions = $rule->conditions ?? [];
@endphp

<div class="d-flex flex-wrap align-items-center gap-1">
    <span class="badge bg-dark">{{ __('IF') }}</span>
    <span class="badge bg-primary">{{ $entityLabel }}</span>
    
    @if(!empty($conditions))
        <span class="badge bg-secondary">(</span>
        @foreach($conditions as $index => $condition)
            @php
                $conditionDefs = \App\Models\NotificationRule::getConditionsForEntity($rule->entity_type);
                $condLabel = $conditionDefs[$condition['field']]['label'] ?? $condition['field'];
                $condType = $conditionDefs[$condition['field']]['type'] ?? 'boolean';
            @endphp
            
            @if($index > 0)
                <span class="badge bg-warning text-dark">{{ __('AND') }}</span>
            @endif
            
            <span class="badge bg-info">
                {{ $condLabel }}
                @if($condType == 'number' && isset($condition['value']))
                    : {{ $condition['value'] }}{{ $conditionDefs[$condition['field']]['suffix'] ?? '' }}
                @endif
            </span>
        @endforeach
        <span class="badge bg-secondary">)</span>
    @endif
</div>
