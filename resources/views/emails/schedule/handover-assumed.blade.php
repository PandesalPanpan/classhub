<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Key Handover Assumed - PUP</title>
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
        .header {
            margin-bottom: 20px;
        }
        .header h1 {
            color: #111827;
            margin: 0;
            font-size: 22px;
        }
        .status-badge {
            display: inline-block;
            background-color: #fff7ed;
            color: #c2410c;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .details-table td {
            padding: 12px 0;
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
        .note {
            background-color: #fff7ed;
            border-left: 4px solid #f97316;
            padding: 16px;
            margin: 25px 0;
            border-radius: 0 6px 6px 0;
            font-size: 14px;
        }
        .person-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 16px;
            margin: 15px 0 0 0;
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
        .action-required {
            background-color: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 16px;
            margin: 25px 0;
            border-radius: 0 6px 6px 0;
            font-size: 14px;
        }
        .btn-container {
            text-align: center;
            margin: 30px 0 10px 0;
        }
        .btn {
            display: inline-block;
            background-color: #800000;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
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
            <h1>Key Handover Assumed</h1>
            <span class="status-badge">Action May Be Needed</span>
        </div>

        @if($recipientRole === 'previous')
        <p>Hello <strong>{{ $previousSchedule->requester?->name ?? 'User' }}</strong>,</p>

        <p>The system detected that <strong>you did not return the key</strong> to the lab after your class in <strong>{{ $previousSchedule->room?->room_number ?? 'Room' }}</strong>. Since another class has a schedule in the same room shortly after yours, the system <strong>assumes you handed the key directly</strong> to the next class representative.</p>

        <table class="details-table">
            <tr>
                <td class="info-label">Your Class</td>
                <td class="info-value">
                    {{ $previousSchedule->start_time->format('g:i A') }} - {{ $previousSchedule->end_time->format('g:i A') }}
                </td>
            </tr>
            <tr>
                <td class="info-label">Room</td>
                <td class="info-value">{{ $nextSchedule->room?->room_number ?? 'Assigned Room' }}</td>
            </tr>
        </table>

        @if($nextSchedule->requester)
        <div class="person-card">
            <p class="person-name">Expected recipient: {{ $nextSchedule->requester->name }}</p>
            @if($nextSchedule->requester->email)
            <p class="person-detail">📧 {{ $nextSchedule->requester->email }}</p>
            @endif
            @if($nextSchedule->program_year_section)
            <p class="person-detail">📚 {{ $nextSchedule->program_year_section }}</p>
            @endif
            <p class="person-detail">🕐 Their class starts at <strong>{{ $nextSchedule->start_time->format('g:i A') }}</strong></p>
        </div>
        @endif

        <div class="note">
            <strong>⚠️ What you should do:</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>If you <strong>still have the key</strong>, please either pass it directly to <strong>{{ $nextSchedule->requester?->name ?? 'the next class representative' }}</strong> or <strong>return it to the lab</strong> so they can grab it.</li>
                <li>If you <strong>already handed it off</strong>, no action is needed — the system will continue tracking.</li>
            </ul>
        </div>

        @else
        <p>Hello <strong>{{ $nextSchedule->requester?->name ?? 'User' }}</strong>,</p>

        <p>The previous class in <strong>{{ $nextSchedule->room?->room_number ?? 'the assigned room' }}</strong> did not return the key to the lab. The system <strong>assumes the key was handed directly to you</strong> by the previous class representative.</p>

        <table class="details-table">
            <tr>
                <td class="info-label">Your Class</td>
                <td class="info-value">
                    {{ $nextSchedule->start_time->format('g:i A') }} - {{ $nextSchedule->end_time->format('g:i A') }}
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
            @if($previousSchedule->requester->email)
            <p class="person-detail">📧 {{ $previousSchedule->requester->email }}</p>
            @endif
            @if($previousSchedule->program_year_section)
            <p class="person-detail">📚 {{ $previousSchedule->program_year_section }}</p>
            @endif
            <p class="person-detail">🕐 Their class ended at <strong>{{ $previousSchedule->end_time->format('g:i A') }}</strong></p>
        </div>
        @endif

        <div class="note">
            <strong>⚠️ What you should do:</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>If you <strong>already have the key</strong>, no action is needed — just return it to the lab after your class.</li>
                <li>If you <strong>do not have the key</strong>, try to <strong>find {{ $previousSchedule->requester?->name ?? 'the previous class representative' }}</strong> to get it from them.</li>
                <li>If you cannot reach them, <strong>inform the lab admin or staff</strong> so they can help locate the key.</li>
            </ul>
        </div>
        @endif

        <div class="btn-container">
            <a href="{{ config('app.url') }}/portal/request-schedule" class="btn">View My Schedule</a>
        </div>

        <div class="footer">
            <p>This is an automated message from <strong>ClassHub - PUP</strong>.</p>
            <p>Need help? Contact support for assistance.</p>
        </div>
    </div>
</body>
</html>
