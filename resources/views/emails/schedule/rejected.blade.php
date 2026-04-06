<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Request Rejected - PUP</title>
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
            background-color: #fee2e2;
            color: #991b1b;
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
        .reason-box {
            background-color: #fef2f2;
            border-left: 4px solid #ef4444;
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
            <h1>Schedule Request Rejected</h1>
            <span class="status-badge">Rejected</span>
        </div>

        <p>Hello <strong>{{ $schedule->requester?->name ?? 'User' }}</strong>,</p>

        <p>Unfortunately, your schedule request has been <strong>rejected</strong>. Here are the details of your request:</p>

        <table class="details-table">
            <tr>
                <td class="info-label">Room</td>
                <td class="info-value">{{ $schedule->room?->room_number ?? 'Requested Room' }}</td>
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

        @if($schedule->remarks)
        <div class="reason-box">
            <strong>📝 Reason for Rejection:</strong>
            <p style="margin: 10px 0 0 0;">{{ $schedule->remarks }}</p>
        </div>
        @endif

        <div class="btn-container">
            <a href="{{ config('app.url') }}/portal/request-schedule" class="btn">Book a New Schedule</a>
        </div>

        <div class="footer">
            <p>This is an automated message from <strong>ClassHub - PUP</strong>.</p>
            <p>Need help? Contact support for assistance.</p>
        </div>
    </div>
</body>
</html>
