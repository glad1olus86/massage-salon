<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Export Work Places') }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        h1 {
            font-size: 16px;
            text-align: center;
            margin-bottom: 5px;
        }
        .generated-at {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 9px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        .workers-cell {
            max-width: 300px;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <h1>{{ __('Work Places') }}</h1>
    <div class="generated-at">{{ __('Generated:') }} {{ $generatedAt }}</div>
    
    <table>
        <thead>
            <tr>
                <th>{{ __('Name') }}</th>
                <th>{{ __('Address') }}</th>
                <th>{{ __('Count') }}</th>
                <th>{{ __('Employees') }}</th>
                <th>{{ __('Phone') }}</th>
                <th>{{ __('Email') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($workPlaces as $workPlace)
                <tr>
                    <td>{{ $workPlace['name'] }}</td>
                    <td>{{ $workPlace['address'] }}</td>
                    <td>{{ $workPlace['workers_count'] }}</td>
                    <td class="workers-cell">{{ $workPlace['workers'] }}</td>
                    <td>{{ $workPlace['phone'] }}</td>
                    <td>{{ $workPlace['email'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
