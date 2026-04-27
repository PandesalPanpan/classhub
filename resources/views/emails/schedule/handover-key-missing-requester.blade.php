@extends('emails.layout')

@section('title', 'Urgent: Key Not Returned')

@section('content')
    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #b91c1c; font-size: 22px; line-height: 1.25;">URGENT: Key Not Returned</h1>
    @include('emails.partials.status-badge', ['status' => 'Key Missing', 'color' => 'red'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $schedule->requester?->name ?? 'Class Representative' }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">The room key for <strong>{{ $schedule->room?->room_number ?? 'your assigned room' }}</strong> is marked missing because no return or confirmed handover was recorded.</p>

    @include('emails.partials.details-table', [
        'rows' => [
            'Room' => e($schedule->room?->room_number ?? 'N/A'),
            'Your Class' => e($schedule->subject.' ('.$schedule->program_year_section.')').'<br>'.e($schedule->start_time->format('g:i A').' - '.$schedule->end_time->format('g:i A').' on '.$schedule->start_time->format('M j, Y')),
        ],
    ])

    @include('emails.partials.callout', [
        'title' => 'Immediate action required',
        'content' => '<ul style="margin: 0; padding-left: 20px;">'
            .'<li>Return the key to the lab immediately if you still have it.</li>'
            .'<li>Report to lab staff and provide details.</li>'
            .'<li>If you already handed off or returned the key, contact lab admin to resolve the discrepancy.</li>'
            .'</ul>',
        'accent' => '#dc2626',
        'background' => '#fee2e2',
    ])

    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #374151;">Lab administrators have already been notified.</p>
@endsection
