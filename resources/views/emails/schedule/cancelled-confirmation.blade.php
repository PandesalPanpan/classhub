@extends('emails.layout')

@section('title', 'Schedule Cancelled')

@section('content')
    @php
        $rows = [
            'Room' => e($schedule->room?->room_number ?? 'Room'),
            'Date' => e($schedule->start_time->format('l, F j, Y')),
            'Time' => e($schedule->start_time->format('g:i A').' - '.$schedule->end_time->format('g:i A')),
            'Subject/Course' => e($schedule->subject),
            'Cancelled At' => e(now()->format('M j, Y g:i A')),
        ];
    @endphp

    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Schedule Cancelled</h1>
    @include('emails.partials.status-badge', ['status' => 'Cancelled', 'color' => 'gray'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $schedule->requester?->name ?? 'User' }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">This confirms that your schedule request has been cancelled.</p>

    @include('emails.partials.details-table', ['rows' => $rows])

    @include('emails.partials.callout', [
        'title' => 'Note',
        'content' => 'This cancellation is final and cannot be undone. If you still need the room, please submit a new request.',
        'accent' => '#6b7280',
        'background' => '#f9fafb',
    ])

    @include('emails.partials.button', [
        'label' => 'Book a New Schedule',
        'url' => config('app.url').'/portal/request-schedule',
    ])
@endsection
