<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URGENT: Return Room Key - PUP</title>
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
            border-top: 6px solid #dc2626;
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
            color: #dc2626;
            margin: 0 0 8px 0;
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
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .urgent-box {
            background-color: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 16px;
            margin: 20px 0;
            border-radius: 0 6px 6px 0;
            font-size: 15px;
        }
        .urgent-box ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .urgent-box li {
            margin-bottom: 6px;
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
            <h1>URGENT: Return the Room Key Now</h1>
            <span class="status-badge">Key Missing</span>
        </div>

        <p>Hello <strong>{{ $schedule->requester?->name ?? 'Class Representative' }}</strong>,</p>

        <p>The room key for <strong>{{ $schedule->room?->room_number ?? 'your assigned room' }}</strong> has been marked as <strong>missing</strong> because it was not returned to the lab after your class and no confirmed handover was recorded.</p>

        <table class="details-table">
            <tr>
                <td class="info-label">Room</td>
                <td class="info-value">{{ $schedule->room?->room_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Your Class</td>
                <td class="info-value">
                    {{ $schedule->subject }} ({{ $schedule->program_year_section }})<br>
                    {{ $schedule->start_time->format('g:i A') }} – {{ $schedule->end_time->format('g:i A') }} on {{ $schedule->start_time->format('M j, Y') }}
                </td>
            </tr>
        </table>

        <div class="urgent-box">
            <strong>Please take immediate action:</strong>
            <ul>
                <li><strong>Return the key to the lab immediately</strong> if you still have it.</li>
                <li><strong>Report to lab staff</strong> and inform them of the situation.</li>
                <li>If you have already returned the key or handed it to someone, please <strong>contact the lab admin</strong> to clarify the discrepancy.</li>
            </ul>
        </div>

        <p>Lab administrators have been notified. Failure to return the key promptly may result in disciplinary action per university policy.</p>

        <div class="footer">
            <p>This is an automated message from <strong>ClassHub - PUP</strong>.</p>
            <p>If this is a mistake, please contact your lab administrator immediately.</p>
        </div>
    </div>
</body>
</html>
