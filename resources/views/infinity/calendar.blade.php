@extends('layouts.infinity')

@section('page-title')
    {{ __('Календарь') }}
@endsection

@section('content')
<section class="page-section">
    <div class="card">
        <div class="block-header">
            <div class="block-title">{{ __('Расписание') }}</div>
        </div>
        <div style="padding: 40px; text-align: center; color: #888;">
            <p>{{ __('Раздел в разработке') }}</p>
            <p style="font-size: 14px; margin-top: 10px;">{{ __('Здесь будет календарь записей и расписание сотрудников') }}</p>
        </div>
    </div>
</section>
@endsection
