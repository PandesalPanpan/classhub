@extends('emails.layout')

@section('title', 'Schedule Request Submitted')

@section('content')
    @php
        $rows = [
            'Room' => e($schedule->room?->room_number ?? 'To be assigned'),
            'Date' => e($schedule->start_time->format('l, F j, Y')),
            'Time' => e($schedule->start_time->format('g:i A').' - '.$schedule->end_time->format('g:i A')),
            'Subject/Course' => e($schedule->subject),
            'Program/Section' => e($schedule->program_year_section),
            'Instructor' => e($schedule->instructor ?? 'Not specified'),
        ];
    @endphp

    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Schedule Request Submitted</h1>
    @include('emails.partials.status-badge', ['status' => 'Pending', 'color' => 'yellow'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $schedule->requester?->name ?? 'User' }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Your schedule request has been submitted successfully and is waiting for admin review.</p>

    @include('emails.partials.details-table', ['rows' => $rows])

    @include('emails.partials.callout', [
        'title' => 'What happens next',
        'content' => 'You will receive another email once your request has been approved or rejected.',
        'accent' => '#2563eb',
        'background' => '#eff6ff',
    ])

    @include('emails.partials.button', [
        'label' => 'View My Requests',
        'url' => config('app.url').'/portal/request-schedule',
    ])
@endsection
