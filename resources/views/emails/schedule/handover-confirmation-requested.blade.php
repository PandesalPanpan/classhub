<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Key Handover Confirmation Required - PUP</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f3f4f6;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border-top: 6px solid #800000;
        }
        .university-header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #FFDF00;
        }
        .university-header h2 {
            margin: 0;
            color: #800000;
            font-size: 18px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .university-header p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }
        .header h1 {
            color: #111827;
            margin: 0 0 8px 0;
            font-size: 22px;
        }
        .status-badge {
            display: inline-block;
            background-color: #fef3c7;
            color: #92400e;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        .details-table td {
            padding: 10px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 15px;
        }
        .details-table tr:last-child td {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            width: 35%;
            vertical-align: top;
        }
        .info-value {
            color: #111827;
            font-weight: 500;
        }
        .person-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            margin: 15px 0;
        }
        .person-card .person-name {
            font-weight: 700;
            color: #111827;
            font-size: 16px;
            margin: 0;
        }
        .person-card .person-detail {
            color: #6b7280;
            font-size: 14px;
            margin: 4px 0 0 0;
        }
        .deadline-notice {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 14px 16px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
            font-size: 14px;
        }
        .action-block {
            margin: 28px 0 10px 0;
            text-align: center;
        }
        .action-block p {
            font-size: 15px;
            color: #374151;
            margin-bottom: 16px;
        }
        .btn-row {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-confirm {
            display: inline-block;
            background-color: #16a34a;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
        }
        .btn-dispute {
            display: inline-block;
            background-color: #dc2626;
            color: #ffffff !important;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 15px;
        }
        .footer {
            margin-top: 35px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 13px;
            color: #9ca3af;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="university-header">
            <h2>Polytechnic University of the Philippines</h2>
            <p>ClassHub Scheduling System</p>
        </div>

        <div class="header">
            <h1>Key Handover Confirmation</h1>
            <span class="status-badge">Action Required</span>
        </div>

        @php
            $previousSchedule = $handover->previousSchedule;
            $nextSchedule = $handover->nextSchedule;
        @endphp

        @if($recipientRole === 'previous')
            <p>Hello <strong>{{ $previousSchedule->requester?->name ?? 'Class Representative' }}</strong>,</p>

            <p>Your class in <strong>{{ $previousSchedule->room?->room_number ?? 'the assigned room' }}</strong> has ended and the key has not yet been returned to the lab. The system detected a scheduled class immediately after yours in the same room.</p>

            <p><strong>Please confirm whether you handed the key directly to the next class representative.</strong></p>

            <table class="details-table">
                <tr>
                    <td class="info-label">Your Class</td>
                    <td class="info-value">
                        {{ $previousSchedule->subject }} ({{ $previousSchedule->program_year_section }})<br>
                        {{ $previousSchedule->start_time->format('g:i A') }} – {{ $previousSchedule->end_time->format('g:i A') }}
                    </td>
                </tr>
                <tr>
                    <td class="info-label">Room</td>
                    <td class="info-value">{{ $previousSchedule->room?->room_number ?? 'Assigned Room' }}</td>
                </tr>
            </table>

            @if($nextSchedule->requester)
                <div class="person-card">
                    <p class="person-name">Next class representative: {{ $nextSchedule->requester->name }}</p>
                    @if($nextSchedule->program_year_section)
                        <p class="person-detail">{{ $nextSchedule->subject }} — {{ $nextSchedule->program_year_section }}</p>
                    @endif
                    <p class="person-detail">Their class starts at <strong>{{ $nextSchedule->start_time->format('g:i A') }}</strong></p>
                </div>
            @endif

        @else
            <p>Hello <strong>{{ $nextSchedule->requester?->name ?? 'Class Representative' }}</strong>,</p>

            <p>The previous class in <strong>{{ $nextSchedule->room?->room_number ?? 'the assigned room' }}</strong> has ended. The system detected that the room key was not returned to the lab.</p>

            <p><strong>Please confirm whether the previous class representative handed the key directly to you.</strong></p>

            <table class="details-table">
                <tr>
                    <td class="info-label">Your Class</td>
                    <td class="info-value">
                        {{ $nextSchedule->subject }} ({{ $nextSchedule->program_year_section }})<br>
                        {{ $nextSchedule->start_time->format('g:i A') }} – {{ $nextSchedule->end_time->format('g:i A') }}
                    </td>
                </tr>
                <tr>
                    <td class="info-label">Room</td>
                    <td class="info-value">{{ $nextSchedule->room?->room_number ?? 'Assigned Room' }}</td>
                </tr>
            </table>

            @if($previousSchedule->requester)
                <div class="person-card">
                    <p class="person-name">Previous class representative: {{ $previousSchedule->requester->name }}</p>
                    @if($previousSchedule->program_year_section)
                        <p class="person-detail">{{ $previousSchedule->subject }} — {{ $previousSchedule->program_year_section }}</p>
                    @endif
                    <p class="person-detail">Their class ended at <strong>{{ $previousSchedule->end_time->format('g:i A') }}</strong></p>
                </div>
            @endif
        @endif

        <div class="deadline-notice">
            <strong>⏱ Please respond by {{ $handover->resolution_deadline_at->format('g:i A') }}.</strong>
            You may confirm, dispute, or return the key to the storage box before this deadline.
            If no confirmation is received and the key is not returned, it will be marked as <strong>missing</strong>.
        </div>

        <div class="action-block">
            <p>Did the handover happen?</p>
            <div class="btn-row">
                <a href="{{ $confirmUrl }}" class="btn-confirm">Yes, I Confirm the Handover</a>
                <a href="{{ $disputeUrl }}" class="btn-dispute">No, Dispute This</a>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated message from <strong>ClassHub - PUP</strong>.</p>
            <p>These confirmation links expire at {{ $handover->resolution_deadline_at->format('g:i A') }} on {{ $handover->resolution_deadline_at->format('M j, Y') }}.</p>
        </div>
    </div>
</body>
</html>
