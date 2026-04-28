@extends('emails.layout')

@section('title', 'Handover Assumed')

@section('content')
    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Handover Assumed</h1>
    @include('emails.partials.status-badge', ['status' => 'Action May Be Needed', 'color' => 'orange'])

    @if($recipientRole === 'previous')
        <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $previousSchedule->requester?->name ?? 'User' }}</strong>,</p>
        <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">The system assumes you handed the key directly to the next class because no return event was recorded after your session.</p>

        @include('emails.partials.details-table', [
            'rows' => [
                'Your Class Time' => e($previousSchedule->start_time->format('g:i A').' - '.$previousSchedule->end_time->format('g:i A')),
                'Room' => e($nextSchedule->room?->room_number ?? 'Assigned Room'),
                'Expected Recipient' => e($nextSchedule->requester?->name ?? 'N/A'),
            ],
        ])

        @include('emails.partials.callout', [
            'title' => 'Required action',
            'content' => '<ul style="margin: 0; padding-left: 20px;">'
                .'<li>If you still have the key, return it to the lab or pass it to '.e($nextSchedule->requester?->name ?? 'the next class representative').'.</li>'
                .'<li>If you already handed it over, no additional action is needed.</li>'
                .'</ul>',
            'accent' => '#f97316',
            'background' => '#fff7ed',
        ])
    @else
        <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $nextSchedule->requester?->name ?? 'User' }}</strong>,</p>
        <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">The system assumes the key was handed to you by the previous class because no key return was recorded.</p>

        @include('emails.partials.details-table', [
            'rows' => [
                'Your Class Time' => e($nextSchedule->start_time->format('g:i A').' - '.$nextSchedule->end_time->format('g:i A')),
                'Room' => e($nextSchedule->room?->room_number ?? 'Assigned Room'),
                'Previous Class Representative' => e($previousSchedule->requester?->name ?? 'N/A'),
            ],
        ])

        @include('emails.partials.callout', [
            'title' => 'Required action',
            'content' => '<ul style="margin: 0; padding-left: 20px;">'
                .'<li>If you already have the key, return it to the lab after class.</li>'
                .'<li>If you do not have the key, coordinate with '.e($previousSchedule->requester?->name ?? 'the previous class representative').' or notify lab staff.</li>'
                .'</ul>',
            'accent' => '#f97316',
            'background' => '#fff7ed',
        ])
    @endif

    @include('emails.partials.button', [
        'label' => 'View My Schedule',
        'url' => config('app.url').'/portal/request-schedule',
    ])
@endsection
