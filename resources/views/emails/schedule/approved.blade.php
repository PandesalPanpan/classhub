@extends('emails.layout')

@section('title', 'Schedule Approved')

@section('content')
    @php
        $rows = [
            'Room' => e($schedule->room?->room_number ?? 'Assigned Room'),
            'Date' => e($schedule->start_time->format('l, F j, Y')),
            'Time' => e($schedule->start_time->format('g:i A').' - '.$schedule->end_time->format('g:i A')),
            'Subject/Course' => e($schedule->subject),
            'Program/Section' => e($schedule->program_year_section),
            'Instructor' => e($schedule->instructor ?? 'Not specified'),
        ];

        $contact = [];
        if ($schedule->approver?->mobile_number) {
            $contact[] = 'Mobile: '.e($schedule->approver->mobile_number);
        }
        if ($schedule->approver?->messenger_link) {
            $contact[] = 'Messenger: <a href="'.e($schedule->approver->messenger_link).'" style="color: #800000;">Open chat</a>';
        }
    @endphp

    <h1 class="email-heading" style="margin: 0 0 10px 0; color: #800000; font-size: 22px; line-height: 1.25;">Schedule Approved</h1>
    @include('emails.partials.status-badge', ['status' => 'Approved', 'color' => 'green'])

    <p style="margin: 20px 0 0 0; font-size: 15px; line-height: 1.6;">Hello <strong>{{ $schedule->requester?->name ?? 'User' }}</strong>,</p>
    <p style="margin: 12px 0 0 0; font-size: 15px; line-height: 1.6;">Your request has been approved. Please review the confirmed schedule details below.</p>

    @include('emails.partials.details-table', ['rows' => $rows])

    @if($schedule->remarks)
        @include('emails.partials.callout', [
            'title' => 'Admin Remarks',
            'content' => e($schedule->remarks),
            'accent' => '#d97706',
            'background' => '#fffbeb',
        ])
    @endif

    @include('emails.partials.callout', [
        'title' => 'Important Instructions',
        'content' => '<ul style="margin: 0; padding-left: 20px;">'
            .'<li>Please arrive at least 5 minutes before your scheduled time.</li>'
            .'<li>Pick up the room key from the laboratory before your class starts.</li>'
            .($nextSchedule ? '<li>If the previous schedule holder offers a handover, you may receive the key directly from them.</li>' : '')
            .(! empty($contact)
                ? '<li>For assistance, you may contact the approving admin ('.e($schedule->approver?->name ?? 'Admin').'): '.implode(' | ', $contact).'</li>'
                : '<li>Contact the admin office if you need help with key pickup or handover coordination.</li>')
            .'<li>Remember to return the key after your session.</li>'
            .'</ul>',
        'accent' => '#16a34a',
        'background' => '#f0fdf4',
    ])

    @if($nextSchedule)
        @include('emails.partials.callout', [
            'title' => 'Handover Available',
            'content' => 'There is another class scheduled right after yours at <strong>'.e($nextSchedule->start_time->format('g:i A')).'</strong> by <strong>'.e($nextSchedule->requester?->name ?? 'another user').'</strong>. You may pass the key directly to them instead of returning it to the lab.',
            'accent' => '#d97706',
            'background' => '#fffbeb',
        ])
    @endif

    @include('emails.partials.button', [
        'label' => 'View My Schedule',
        'url' => config('app.url').'/portal/request-schedule',
    ])
@endsection
