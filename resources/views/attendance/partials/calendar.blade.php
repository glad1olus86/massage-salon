<table class="schedule-table">
    <thead>
        <tr>
            <th class="worker-cell">{{ __('Worker') }}</th>
            @php
                $dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            @endphp
            @foreach($weekDays as $day)
                <th class="{{ $day->isToday() ? 'today' : '' }}">
                    <div>{{ __($dayNames[$day->dayOfWeekIso - 1]) }}</div>
                    <div>{{ $day->format('d') }}</div>
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($scheduleData as $item)
            <tr>
                <td class="worker-cell">
                    <div class="worker-name">
                        {{ $item['name'] }}
                    </div>
                    @if(!empty($item['work_place']))
                        <div class="worker-workplace">
                            {{ $item['work_place'] }}
                        </div>
                    @endif
                </td>
                @foreach($item['days'] as $index => $dayData)
                    @php
                        $day = $weekDays[$index] ?? null;
                        $shift = $dayData['shift'] ?? null;
                    @endphp
                    <td class="shift-cell {{ $day && $day->isToday() ? 'today' : '' }}" 
                        data-worker="{{ $item['id'] }}" 
                        data-date="{{ $dayData['date'] }}">
                        @if($shift)
                            <div class="shift-badge" style="background-color: {{ $shift['color'] ?? '#6c757d' }}">
                                {{ $shift['name'] }}
                            </div>
                            <div class="shift-time">
                                {{ $shift['time_range'] ?? '' }}
                            </div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
