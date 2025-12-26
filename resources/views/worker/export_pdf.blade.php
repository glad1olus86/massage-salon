<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('Workers List') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .header .date {
            font-size: 10px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            border: 1px solid #333;
            padding: 4px 3px;
            text-align: left;
            vertical-align: top;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
        }
        
        td {
            font-size: 8px;
        }
        
        tr:nth-child(even) {
            background-color: #fafafa;
        }
        
        .text-center {
            text-align: center;
        }
        
        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('Workers List') }}</h1>
        <div class="date">{{ __('Generated:') }} {{ $generatedAt }}</div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>{{ __('First Name') }}</th>
                <th>{{ __('Last Name') }}</th>
                <th>{{ __('DOB') }}</th>
                <th>{{ __('Age') }}</th>
                <th>{{ __('Gender') }}</th>
                <th>{{ __('Nat.') }}</th>
                <th>{{ __('Reg. Date') }}</th>
                <th>{{ __('Hotel') }}</th>
                <th>{{ __('Room') }}</th>
                <th>{{ __('Check-in') }}</th>
                <th>{{ __('By') }}</th>
                <th>{{ __('Work Place') }}</th>
                <th>{{ __('Empl. Date') }}</th>
                <th>{{ __('Duration') }}</th>
                <th>{{ __('By') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($workers as $worker)
                <tr>
                    <td>{{ $worker['first_name'] }}</td>
                    <td>{{ $worker['last_name'] }}</td>
                    <td class="nowrap">{{ $worker['dob'] }}</td>
                    <td class="text-center">{{ $worker['age'] }}</td>
                    <td>{{ $worker['gender'] }}</td>
                    <td>{{ $worker['nationality'] }}</td>
                    <td class="nowrap">{{ $worker['registration_date'] }}</td>
                    <td>{{ $worker['hotel'] }}</td>
                    <td class="text-center">{{ $worker['room'] }}</td>
                    <td class="nowrap">{{ $worker['check_in_date'] }}</td>
                    <td>{{ $worker['checked_in_by'] }}</td>
                    <td>{{ $worker['work_place'] }}</td>
                    <td class="nowrap">{{ $worker['work_started_at'] }}</td>
                    <td class="nowrap">{{ $worker['work_duration'] }}</td>
                    <td>{{ $worker['work_assigned_by'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
