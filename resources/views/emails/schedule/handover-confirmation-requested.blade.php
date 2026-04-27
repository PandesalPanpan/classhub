@extends('emails.layout')

@section('title', 'Key Handover Confirmation')

@section('content')
    @php
        $previousSchedule = $handover->previousSchedule;
        $nextSchedule = $handover->nextSchedule;
    @endphp

    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Key Handover Confirmation</h1>
    @include('emails.partials.status-badge', ['status' => 'Action Required', 'color' => 'yellow'])

    @if($recipientRole === 'previous')
        <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $previousSchedule->requester?->name ?? 'Class Representative' }}</strong>,</p>
        <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Please confirm whether you handed the room key directly to the next class representative.</p>

        @include('emails.partials.details-table', [
            'rows' => [
                'Your Class' => e($previousSchedule->subject.' ('.$previousSchedule->program_year_section.')').'<br>'.e($previousSchedule->start_time->format('g:i A').' - '.$previousSchedule->end_time->format('g:i A')),
                'Room' => e($previousSchedule->room?->room_number ?? 'Assigned Room'),
                'Next Class Representative' => e($nextSchedule->requester?->name ?? 'N/A'),
                'Next Class Start' => e($nextSchedule->start_time->format('g:i A')),
            ],
        ])
    @else
        <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $nextSchedule->requester?->name ?? 'Class Representative' }}</strong>,</p>
        <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Please confirm whether the previous class representative handed the room key directly to you.</p>

        @include('emails.partials.details-table', [
            'rows' => [
                'Your Class' => e($nextSchedule->subject.' ('.$nextSchedule->program_year_section.')').'<br>'.e($nextSchedule->start_time->format('g:i A').' - '.$nextSchedule->end_time->format('g:i A')),
                'Room' => e($nextSchedule->room?->room_number ?? 'Assigned Room'),
                'Previous Class Representative' => e($previousSchedule->requester?->name ?? 'N/A'),
                'Previous Class End' => e($previousSchedule->end_time->format('g:i A')),
            ],
        ])
    @endif

    @include('emails.partials.callout', [
        'title' => 'Response deadline',
        'content' => 'Please respond by <strong>'.e($handover->resolution_deadline_at->format('g:i A')).'</strong> on '.e($handover->resolution_deadline_at->format('M j, Y')).'. If no confirmation is received and the key is not returned, it will be marked as missing.',
        'accent' => '#d97706',
        'background' => '#fffbeb',
    ])

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 24px 0 12px 0;">
        <tr>
            <td align="center" style="padding: 0 0 12px 0; color: #374151; font-size: 15px;">Did the handover happen?</td>
        </tr>
        <tr>
            <td align="center">
                <a href="{{ $confirmUrl }}" style="display: inline-block; background-color: #16a34a; color: #ffffff; text-decoration: none; border-radius: 6px; padding: 12px 24px; font-size: 14px; font-weight: 700; margin: 0 6px 10px 6px;">Confirm</a>
                <a href="{{ $disputeUrl }}" style="display: inline-block; background-color: #dc2626; color: #ffffff; text-decoration: none; border-radius: 6px; padding: 12px 24px; font-size: 14px; font-weight: 700; margin: 0 6px 10px 6px;">Dispute</a>
            </td>
        </tr>
    </table>
@endsection
