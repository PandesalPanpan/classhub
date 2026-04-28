@extends('emails.layout')

@section('title', 'Schedule Expired')

@section('content')
    @php
        $rows = [
            'Room' => e($schedule->room?->room_number ?? 'Room'),
            'Date' => e($schedule->start_time->format('l, F j, Y')),
            'Scheduled Time' => e($schedule->start_time->format('g:i A').' - '.$schedule->end_time->format('g:i A')),
            'Subject/Course' => e($schedule->subject),
        ];
    @endphp

    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Schedule Expired</h1>
    @include('emails.partials.status-badge', ['status' => 'Expired', 'color' => 'orange'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $schedule->requester?->name ?? 'User' }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Your approved schedule has expired because the key was not retrieved during the scheduled period.</p>

    @include('emails.partials.details-table', ['rows' => $rows])

    @include('emails.partials.callout', [
        'title' => 'What happened',
        'content' => 'The system did not detect key pickup from the key box for this class slot, so the schedule was automatically marked as expired.',
        'accent' => '#d97706',
        'background' => '#fffbeb',
    ])

    @include('emails.partials.callout', [
        'title' => 'Next steps',
        'content' => '<ul style="margin: 0; padding-left: 20px;">'
            .'<li>If you still need the room, submit a new schedule request.</li>'
            .'<li>If this was caused by a system issue, contact an administrator.</li>'
            .'<li>For future schedules, retrieve the key at the start of your slot.</li>'
            .'</ul>',
        'accent' => '#2563eb',
        'background' => '#eff6ff',
    ])

    @include('emails.partials.button', [
        'label' => 'Book a New Schedule',
        'url' => config('app.url').'/portal/request-schedule',
    ])
@endsection
