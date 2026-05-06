@extends('emails.layout')

@section('title', 'Key Missing Alert')

@section('content')
    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #b91c1c; font-size: 22px; line-height: 1.25;">Key Missing Alert</h1>
    @include('emails.partials.status-badge', ['status' => 'Action Required', 'color' => 'red'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $recipient->name ?? 'Administrator' }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">A room key has been marked missing and needs immediate administrative action.</p>

    @include('emails.partials.details-table', [
        'rows' => [
            'Room' => e($lastSchedule->room?->room_number ?? 'Unknown'),
            'Slot Number' => e($lastSchedule->room?->key?->slot_number ?? 'Unknown'),
            'Last Scheduled Time' => e($lastSchedule->start_time->format('M j, Y g:i A').' - '.$lastSchedule->end_time->format('g:i A')),
            'Subject/Course' => e($lastSchedule->subject ?? 'N/A'),
            'Requester' => e($lastSchedule->requester?->name ?? 'Unknown'),
        ],
    ])

    @include('emails.partials.callout', [
        'title' => 'Situation',
        'content' => 'The post-class check did not detect key return after the scheduled session. Key status is now marked as <strong>Missing</strong>.',
        'accent' => '#dc2626',
        'background' => '#fef2f2',
    ])

    @include('emails.partials.callout', [
        'title' => 'Action required',
        'content' => '<ul style="margin: 0; padding-left: 20px;">'
            .'<li>Investigate and locate the key immediately.</li>'
            .'<li>Coordinate with the last requester if needed.</li>'
            .'<li>Update key status after resolution.</li>'
            .'</ul>',
        'accent' => '#f97316',
        'background' => '#fff7ed',
    ])

    @include('emails.partials.button', [
        'label' => 'Manage Rooms and Keys',
        'url' => config('app.url').'/admin/rooms',
    ])
@endsection
