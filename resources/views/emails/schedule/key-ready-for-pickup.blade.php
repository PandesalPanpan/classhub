<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Key Ready for Pickup - PUP</title>
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
            background-color: #d1fae5;
            color: #065f46;
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
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
            padding: 16px;
            margin: 25px 0;
            border-radius: 0 6px 6px 0;
            font-size: 14px;
        }
        .handover-hint {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 16px;
            margin: 15px 0 0 0;
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
            <h1>Key Ready for Pickup</h1>
            <span class="status-badge">Available</span>
        </div>

        <p>Hello <strong>{{ $schedule->requester?->name ?? 'User' }}</strong>,</p>

        <p>Good news! The key for your upcoming class is <strong>available and ready for pickup</strong> in the lab.</p>

        <table class="details-table">
            <tr>
                <td class="info-label">Room</td>
                <td class="info-value">{{ $schedule->room?->room_number ?? 'Assigned Room' }}</td>
            </tr>
            <tr>
                <td class="info-label">Date</td>
                <td class="info-value">{{ $schedule->start_time->format('l, F j, Y') }}</td>
            </tr>
            <tr>
                <td class="info-label">Time</td>
                <td class="info-value">
                    {{ $schedule->start_time->format('g:i A') }} - {{ $schedule->end_time->format('g:i A') }}
                </td>
            </tr>
            <tr>
                <td class="info-label">Subject/Course</td>
                <td class="info-value">{{ $schedule->subject }}</td>
            </tr>
        </table>

        <div class="note">
            <strong>🔑 What you can do:</strong>
            <ul style="margin: 10px 0 0 0; padding-left: 20px;">
                <li>Head to the lab now to pick up the key.</li>
                <li>After your class, please return the key to the lab.</li>
                @if($nextSchedule)
                <li>Alternatively, you may hand the key directly to <strong>{{ $nextSchedule->requester?->name ?? 'the next user' }}</strong> who has class at <strong>{{ $nextSchedule->start_time->format('g:i A') }}</strong>.</li>
                @endif
            </ul>
        </div>

        @if($nextSchedule)
        <div class="handover-hint">
            <strong>🤝 Handover Available:</strong> The next scheduled class is at <strong>{{ $nextSchedule->start_time->format('g:i A') }}</strong> by <strong>{{ $nextSchedule->requester?->name ?? 'another user' }}</strong>. You can pass the key directly to them instead of returning it to the lab.
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
