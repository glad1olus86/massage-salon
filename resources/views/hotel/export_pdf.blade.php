<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Export Hotels') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin: 15px;
        }
        h1, h2 {
            font-size: 14px;
            text-align: center;
            margin-bottom: 5px;
        }
        h2 {
            font-size: 12px;
            margin-top: 25px;
        }
        .generated-at {
            text-align: center;
            color: #666;
            margin-bottom: 15px;
            font-size: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 8px;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .workers-cell {
            max-width: 200px;
            word-wrap: break-word;
            font-size: 8px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <h1>{{ __('Hotels') }}</h1>
    <div class="generated-at">{{ __('Generated:') }} {{ $generatedAt }}</div>
    
    <table>
        <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Address') }}</th>
                <th>{{ __('Occupied') }}</th>
                <th>{{ __('Capacity') }}</th>
                <th>{{ __('Rooms') }}</th>
                <th>{{ __('Full') }}</th>
                <th>{{ __('Partial') }}</th>
                <th>{{ __('Free') }}</th>
                <th>{{ __('Workers') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($hotels as $hotel)
                <tr>
                    <td>{{ $hotel['name'] }}</td>
                    <td>{{ $hotel['address'] }}</td>
                    <td>{{ $hotel['current_occupancy'] }}</td>
                    <td>{{ $hotel['total_capacity'] }}</td>
                    <td>{{ $hotel['total_rooms'] }}</td>
                    <td>{{ $hotel['fully_occupied'] }}</td>
                    <td>{{ $hotel['partially_occupied'] }}</td>
                    <td>{{ $hotel['free_rooms'] }}</td>
                    <td class="workers-cell">{{ $hotel['workers'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

    <h2>{{ __('Rooms') }}</h2>
    
    <table>
        <thead>
            <tr>
                <th>{{ __('Hotel') }}</th>
                <th>{{ __('Number') }}</th>
                <th>{{ __('Occupied') }}</th>
                <th>{{ __('Cap.') }}</th>
                <th>{{ __('Full') }}</th>
                <th>{{ __('Partial') }}</th>
                <th>{{ __('Free') }}</th>
                <th>{{ __('Price') }}</th>
                <th>{{ __('Pays') }}</th>
                <th>{{ __('Amount') }}</th>
                <th>{{ __('Workers') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rooms as $room)
                <tr>
                    <td>{{ $room['hotel'] }}</td>
                    <td>{{ $room['room_number'] }}</td>
                    <td>{{ $room['current_occupancy'] }}</td>
                    <td>{{ $room['capacity'] }}</td>
                    <td>{{ $room['is_full'] }}</td>
                    <td>{{ $room['is_partial'] }}</td>
                    <td>{{ $room['is_empty'] }}</td>
                    <td>{{ $room['monthly_price'] }}</td>
                    <td>{{ $room['payment_type'] }}</td>
                    <td>{{ $room['partial_amount'] }}</td>
                    <td class="workers-cell">{{ $room['workers'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
