@extends('emails.layout')

@section('title', 'Schedule Request Rejected')

@section('content')
    @php
        $rows = [
            'Room' => e($schedule->room?->room_number ?? 'Requested Room'),
            'Date' => e($schedule->start_time->format('l, F j, Y')),
            'Time' => e($schedule->start_time->format('g:i A').' - '.$schedule->end_time->format('g:i A')),
            'Subject/Course' => e($schedule->subject),
        ];
    @endphp

    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Schedule Rejected</h1>
    @include('emails.partials.status-badge', ['status' => 'Rejected', 'color' => 'red'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $schedule->requester?->name ?? 'User' }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Your request could not be approved at this time. Please review the details below.</p>

    @include('emails.partials.details-table', ['rows' => $rows])

    @if($schedule->remarks)
        @include('emails.partials.callout', [
            'title' => 'Admin Remarks',
            'content' => e($schedule->remarks),
            'accent' => '#dc2626',
            'background' => '#fef2f2',
        ])
    @endif

    @include('emails.partials.callout', [
        'title' => 'Need assistance?',
        'content' => 'If you need clarification, please contact the admin office before submitting a new request.',
        'accent' => '#6b7280',
        'background' => '#f9fafb',
    ])

    @include('emails.partials.button', [
        'label' => 'Submit a New Request',
        'url' => config('app.url').'/portal/request-schedule',
    ])
@endsection
