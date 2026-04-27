@extends('emails.layout')

@section('title', 'Override Request Submitted')

@section('content')
    @php
        $rows = [
            'Room' => e($schedule->room?->room_number ?? 'Requested Room'),
            'Date' => e($schedule->start_time->format('l, F j, Y')),
            'Time' => e($schedule->start_time->format('g:i A').' - '.$schedule->end_time->format('g:i A')),
            'Subject/Course' => e($schedule->subject),
            'Program/Section' => e($schedule->program_year_section),
            'Instructor' => e($schedule->instructor ?? 'Not specified'),
        ];
    @endphp

    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Override Request Submitted</h1>
    @include('emails.partials.status-badge', ['status' => 'Pending', 'color' => 'yellow'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $schedule->requester?->name ?? 'User' }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Your prioritized override request has been submitted for admin review.</p>

    @include('emails.partials.details-table', ['rows' => $rows])

    @include('emails.partials.callout', [
        'title' => 'Priority Request',
        'content' => 'This request was marked as an override and will be reviewed as a priority item.',
        'accent' => '#d97706',
        'background' => '#fffbeb',
    ])

    @include('emails.partials.callout', [
        'title' => 'What happens next',
        'content' => 'You will receive another message once the request has been approved or rejected.',
        'accent' => '#2563eb',
        'background' => '#eff6ff',
    ])

    @include('emails.partials.button', [
        'label' => 'View My Requests',
        'url' => config('app.url').'/portal/request-schedule',
    ])
@endsection
